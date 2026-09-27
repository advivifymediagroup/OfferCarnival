<?php
/**
 * UAE language switch (English / Arabic) — country + language expansion.
 *
 * Must be a must-use plugin, not a theme functions.php hook: the theme's
 * functions.php isn't included until wp-settings.php has already called
 * load_default_textdomain() and instantiated WP_Locale (both need the final
 * locale to already be known — that's what makes WordPress load the right
 * core .mo file and set is_rtl() correctly). mu-plugins are the earliest
 * point a filter can be registered, so this is the only place a
 * `determine_locale` filter reliably lands in time for the very first
 * locale resolution of the request.
 *
 * The switch only ever fires when BOTH the country cookie is 'ae' AND the
 * language cookie is 'ar' — India (and UAE-in-English) always resolve to
 * the site's normal default locale, completely untouched.
 */
add_filter( 'determine_locale', function ( $locale ) {
	// Never touch wp-admin's own language (an admin logged in with the
	// cookies set shouldn't have their dashboard flip to Arabic) — same
	// is_admin()-with-ajax-exemption pattern used by the country content
	// filters in inc/core/countries.php, so the theme's own frontend ajax
	// (load more, search suggestions) still gets the right locale.
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $locale;
	}

	$country = isset( $_COOKIE['wpc_country'] ) ? sanitize_key( $_COOKIE['wpc_country'] ) : '';
	$lang    = isset( $_COOKIE['wpc_lang'] ) ? sanitize_key( $_COOKIE['wpc_lang'] ) : '';

	if ( 'ae' === $country && 'ar' === $lang ) {
		return 'ar';
	}

	return $locale;
} );
