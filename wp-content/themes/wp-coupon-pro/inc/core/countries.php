<?php
/**
 * Country helper functions.
 *
 * Multi-country support:
 * - Step A: data model + admin fields (wpc_country taxonomy, per-store/
 *   per-coupon country assignment).
 * - Step B: header country switcher + per-country logo.
 * - Step C: content filtering by country (bottom of this file) — narrows
 *   coupon/store listings to the active country, but only ever for a
 *   visitor who has explicitly switched away from the default country.
 *
 * @package WP Coupon/inc/core
 * @since 7.1.0
 */

/**
 * Path-prefix country routing (offercarnival.com/ae/...) — SEO needs a
 * real, crawlable, indexable URL per country; a cookie-only switch is
 * invisible to a crawler, so Google only ever sees whichever country
 * happens to be the default. Runs as plain top-level code (this file
 * loads during setup_theme, well before WP parses the request) rather
 * than a hook, because it has to rewrite REQUEST_URI *before* WP's own
 * rewrite-rule matching ever runs — every existing rewrite rule (store/
 * coupon/category archives, pagination, etc.) then keeps working
 * completely unchanged against whatever's left after the "/ae" prefix is
 * stripped, with no new rewrite rules to add or flush.
 *
 * Only ever matches a path that is exactly "/ae" or starts with "/ae/" —
 * never wp-admin, never a hypothetical real page slug that merely starts
 * with "ae" (e.g. "/aero/").
 */
$wpc_request_path = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
$wpc_is_ae_path    = $wpc_request_path && preg_match( '#^/ae(/|$)#', $wpc_request_path );

if ( $wpc_is_ae_path ) {
	define( 'WPC_URL_COUNTRY', 'ae' );

	$wpc_stripped_path = substr( $wpc_request_path, 3 ); // drop the leading "/ae"
	if ( '' === $wpc_stripped_path ) {
		$wpc_stripped_path = '/';
	}
	$_SERVER['REQUEST_URI'] = $wpc_stripped_path . substr( $_SERVER['REQUEST_URI'], strlen( $wpc_request_path ) );
}

/**
 * Keeps the wpc_country cookie mirroring the URL's own country rather than
 * the other way around: the point of the prefix is that the bare domain
 * always renders India and "/ae" always renders UAE for *every* visitor,
 * including a crawler with no cookie at all — a stale cookie must never be
 * able to override that on a bare URL, or the SEO ambiguity this is meant
 * to fix comes right back. The cookie's only remaining job is letting
 * admin-ajax.php requests (built with admin_url(), which never carries the
 * "/ae" prefix no matter which page triggered them — see
 * wpcoupon_get_current_country() below) still resolve to whichever country
 * the *page* that triggered them was actually on. Reset on every request
 * rather than left to persist, so visiting a bare page after an "/ae" one
 * doesn't leave that bare page's own AJAX calls still thinking they're UAE.
 */
if ( ! headers_sent() ) {
	$wpc_cookie_target = $wpc_is_ae_path ? 'ae' : 'in';
	if ( ( isset( $_COOKIE['wpc_country'] ) ? $_COOKIE['wpc_country'] : '' ) !== $wpc_cookie_target ) {
		setcookie( 'wpc_country', $wpc_cookie_target, time() + DAY_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN );
		$_COOKIE['wpc_country'] = $wpc_cookie_target;
	}
}

/**
 * Every internal link built on home_url() — permalinks, term links,
 * pagination, etc., which covers most of WordPress's own linking API —
 * comes back out prefixed with "/ae" again for the rest of this request,
 * so navigating a UAE page keeps you on UAE pages instead of silently
 * dropping the prefix on the next click. wp-admin's own links use
 * site_url()/admin_url(), not home_url(), so this never touches them.
 *
 * redirect_canonical() is disabled for the same requests: it independently
 * rebuilds what it thinks the "correct" URL for the resolved page is
 * (itself via home_url()) and 301s there if that disagrees with the
 * now-stripped REQUEST_URI — i.e. it would see "/" vs. this filter's own
 * "/ae/" and redirect right back to "/ae/", forever. Safe to drop entirely
 * here: the "/ae" stripping above already *is* the canonical resolution
 * for these requests, there's nothing left for WordPress to correct.
 */
if ( defined( 'WPC_URL_COUNTRY' ) ) {
	add_filter( 'home_url', function ( $url ) {
		return preg_replace( '#^(https?://[^/]+)#', '$1/ae', $url, 1 );
	} );
	add_filter( 'redirect_canonical', '__return_false' );
}

/**
 * Get all registered countries (wpc_country terms).
 *
 * @param array $args get_terms() args override.
 * @return array WP_Term[]
 */
function wpcoupon_get_countries( $args = array() ) {
	$default = array(
		'taxonomy'   => 'wpc_country',
		'hide_empty' => false,
		'orderby'    => 'name',
		'order'      => 'ASC',
	);

	$terms = get_terms( wp_parse_args( $args, $default ) );

	return is_wp_error( $terms ) ? array() : $terms;
}

/**
 * Get the slug of the default country (falls back to 'in').
 *
 * A country is "default" when its term meta `_wpc_is_default` is set.
 * If none is flagged yet (e.g. before the migration below has run),
 * we fall back to 'in' so existing behaviour never changes silently.
 *
 * @return string country term slug, e.g. 'in'
 */
function wpcoupon_get_default_country() {
	static $default_slug = null;

	if ( null !== $default_slug ) {
		return $default_slug;
	}

	$terms = wpcoupon_get_countries(
		array(
			'meta_key'   => '_wpc_is_default',
			'meta_value' => 'on',
		)
	);

	$default_slug = ! empty( $terms ) ? $terms[0]->slug : 'in';

	return $default_slug;
}

/**
 * Get the countries a store (coupon_store term) operates in.
 *
 * @param int $store_term_id coupon_store term_id.
 * @return string[] country slugs, e.g. array( 'in', 'ae' )
 */
function wpcoupon_get_store_countries( $store_term_id ) {
	$countries = get_term_meta( $store_term_id, '_wpc_store_countries', true );

	if ( empty( $countries ) || ! is_array( $countries ) ) {
		// Not migrated / not set yet: treat as default-country-only so
		// nothing disappears from the site before the backfill runs.
		return array( wpcoupon_get_default_country() );
	}

	return $countries;
}

/**
 * Get the countries a coupon post is valid in (wpc_country terms).
 *
 * @param int $coupon_id coupon post ID.
 * @return string[] country slugs
 */
function wpcoupon_get_coupon_countries( $coupon_id ) {
	$terms = wp_get_object_terms( $coupon_id, 'wpc_country', array( 'fields' => 'slugs' ) );

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return array( wpcoupon_get_default_country() );
	}

	return $terms;
}

/**
 * Build a `term_id => "Name"` options list for the store multicheck field,
 * used in inc/config/metabox-config.php.
 *
 * @return array
 */
function wpcoupon_get_country_field_options() {
	$options = array();

	foreach ( wpcoupon_get_countries() as $term ) {
		$options[ $term->slug ] = $term->name;
	}

	return $options;
}

/**
 * Resolve the flag icon URL for a country term.
 *
 * Prefers a crisp, purpose-built flag SVG bundled with the theme
 * (assets/flags/{slug}.svg — from the MIT-licensed "flag-icons" project,
 * the same kind of vector flag set most sites use) over anything else, so
 * every country looks right out of the box with zero admin setup. The
 * uploaded "Flag" field in Coupons → Countries is only an override, for
 * the rare case a country needs a non-standard variant — it's never
 * required, and a raster image is never generated for this automatically.
 *
 * @param WP_Term $term
 * @return string flag URL, or '' if neither exists
 */
function wpcoupon_get_country_flag_url( $term ) {
	$bundled_path = get_template_directory() . '/assets/flags/' . $term->slug . '.svg';

	if ( file_exists( $bundled_path ) ) {
		return get_template_directory_uri() . '/assets/flags/' . $term->slug . '.svg';
	}

	$custom = get_term_meta( $term->term_id, '_wpc_flag_image', true );

	return $custom ? $custom : '';
}

/**
 * Get the visitor's currently active country slug.
 *
 * The URL always wins when a "/ae" prefix is present (WPC_URL_COUNTRY, set
 * at the top of this file) — that's what makes the bare domain and "/ae"
 * each render consistently for every visitor, crawler included. The one
 * exception is an admin-ajax.php request: those are built with
 * admin_url(), which never carries the "/ae" prefix no matter which page
 * triggered them, so the wpc_country cookie (kept in sync with the URL at
 * the top of this file) is the only way a "load more coupons" click on an
 * "/ae" page can still know it's in UAE context. Anything else — a bare
 * page load with no prefix, regardless of cookie — is the default country.
 *
 * @return string country term slug, e.g. 'in' or 'ae'
 */
function wpcoupon_get_current_country() {
	static $current = null;

	if ( null !== $current ) {
		return $current;
	}

	if ( defined( 'WPC_URL_COUNTRY' ) ) {
		$current = WPC_URL_COUNTRY;
		return $current;
	}

	if ( wp_doing_ajax() && taxonomy_exists( 'wpc_country' ) ) {
		$cookie = isset( $_COOKIE['wpc_country'] ) ? sanitize_key( wp_unslash( $_COOKIE['wpc_country'] ) ) : '';
		$valid_slugs = wp_list_pluck( wpcoupon_get_countries(), 'slug' );
		if ( $cookie && in_array( $cookie, $valid_slugs, true ) ) {
			$current = $cookie;
			return $current;
		}
	}

	$current = wpcoupon_get_default_country();
	return $current;
}

/**
 * Legacy-link compat: the switcher itself now links straight to "/ae/..."
 * (a real URL — see wpcoupon_country_switcher() below), but an old
 * bookmarked or shared "?wpc_country=ae" link should still land somewhere
 * correct rather than 404 or silently do nothing. Sends it to that
 * country's homepage (the cookie gets set correctly on that next request
 * by the top-of-file logic — no need to set it here too).
 */
function wpcoupon_handle_country_switch() {
	if ( empty( $_GET['wpc_country'] ) ) {
		return;
	}

	$slug        = sanitize_key( wp_unslash( $_GET['wpc_country'] ) );
	$valid_slugs = wp_list_pluck( wpcoupon_get_countries(), 'slug' );

	if ( ! in_array( $slug, $valid_slugs, true ) ) {
		return;
	}

	$default = wpcoupon_get_default_country();
	wp_safe_redirect( $slug === $default ? home_url( '/' ) : home_url( '/' . $slug . '/' ) );
	exit;
}
add_action( 'init', 'wpcoupon_handle_country_switch' );

/**
 * Resolve which logo URL to show for a given country.
 *
 * Falls back to the existing global Theme Options logo (today's exact
 * behaviour) whenever the country is the default one, or has no
 * country-specific logo uploaded yet. This is the guarantee that India
 * keeps looking exactly as it does today unless someone explicitly
 * uploads a different logo for a non-default country.
 *
 * @param string $country_slug
 * @return string logo URL, possibly empty (theme falls back to site title)
 */
function wpcoupon_get_logo_for_country( $country_slug ) {
	if ( $country_slug && $country_slug !== wpcoupon_get_default_country() ) {
		$term = get_term_by( 'slug', $country_slug, 'wpc_country' );
		if ( $term ) {
			$logo = get_term_meta( $term->term_id, '_wpc_country_logo', true );
			if ( $logo ) {
				return $logo;
			}
		}
	}

	return wpcoupon_get_option( 'site_logo', false, 'url' );
}

/**
 * Which sidebar id to render for one of the three frontpage regions
 * ('frontpage-before-main', 'frontpage-main', 'frontpage-after-main').
 * Non-default countries get their own registered "-{country}" counterpart
 * of each (e.g. 'frontpage-main-ae') so content curated with specific
 * India post/term IDs — Popular Stores, Daily Deals — never has to be
 * shared with or filtered out for another country. Falls back to the
 * original India sidebar id whenever a country has no dedicated one
 * registered, or its sidebar has no widgets assigned (nothing published
 * for it yet in wp-admin) — which is *by design* for
 * 'frontpage-after-main-ae' today (see the comment on its registration in
 * functions.php): that region's shared content is made bilingual in place
 * instead of forked, so it's meant to always fall through to the shared
 * sidebar. Either way, a new country never renders a blank homepage
 * section.
 *
 * @param string $base_id 'frontpage-before-main' | 'frontpage-main' | 'frontpage-after-main'
 */
function wpcoupon_get_country_sidebar_id( $base_id ) {
	$country = wpcoupon_get_current_country();
	$default = wpcoupon_get_default_country();

	if ( $country === $default ) {
		return $base_id;
	}

	$country_sidebar_id = $base_id . '-' . $country;

	if ( is_active_sidebar( $country_sidebar_id ) ) {
		return $country_sidebar_id;
	}

	return $base_id;
}

/**
 * Render the header country switcher (flag dropdown).
 *
 * Deliberately NOT built on the theme's Semantic UI `.dropdown` component:
 * assets/js/global.js auto-initializes *every* `.dropdown` element site-wide
 * with `$('.dropdown').dropdown()`, which expects Semantic's own markup
 * conventions and will rewrite/empty anything that doesn't match. To avoid
 * fighting that (and to avoid any risk of it affecting other dropdowns on
 * the site), this is a tiny, self-contained, dependency-free widget: plain
 * links (so it still works with JS disabled) plus a few lines of scoped
 * vanilla JS just to toggle the menu open/closed.
 *
 * Renders nothing if fewer than 2 countries exist, so an un-configured
 * site shows nothing extra.
 */
function wpcoupon_country_switcher() {
	$countries = wpcoupon_get_countries();

	if ( count( $countries ) < 2 ) {
		return;
	}

	$current_slug = wpcoupon_get_current_country();
	$current_term = null;

	foreach ( $countries as $term ) {
		if ( $term->slug === $current_slug ) {
			$current_term = $term;
			break;
		}
	}

	if ( ! $current_term ) {
		return;
	}

	$current_flag = wpcoupon_get_country_flag_url( $current_term );
	?>
	<div class="wpc-country-switcher">
		<button type="button" class="wpc-country-switcher__toggle" aria-haspopup="true" aria-expanded="false">
			<?php if ( $current_flag ) : ?>
				<img class="wpc-country-switcher__flag" src="<?php echo esc_url( $current_flag ); ?>" alt="" />
			<?php endif; ?>
			<span><?php echo esc_html( strtoupper( $current_term->slug ) ); ?></span>
			<span class="wpc-country-switcher__caret" aria-hidden="true">&#9662;</span>
		</button>
		<div class="wpc-country-switcher__menu">
			<?php
			// Built from get_option('home') directly rather than home_url() —
			// home_url() is filtered (above) to prepend "/ae" when the
			// *current* request is already under that prefix, and building an
			// "/ae" link by concatenating onto an already-"/ae"-prefixed
			// home_url() would double it up into "/ae/ae/...". Using the raw,
			// unfiltered site root sidesteps that: both links below are built
			// explicitly, from the same starting point.
			$wpc_site_root     = untrailingslashit( get_option( 'home' ) );
			$wpc_current_path  = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
			foreach ( $countries as $term ) :
				$flag = wpcoupon_get_country_flag_url( $term );
				$url  = esc_url( $wpc_site_root . ( $term->slug === wpcoupon_get_default_country() ? '' : '/' . $term->slug ) . $wpc_current_path );
				?>
				<a class="wpc-country-switcher__item<?php echo ( $term->slug === $current_slug ) ? ' is-active' : ''; ?>" href="<?php echo $url; ?>" title="<?php echo esc_attr( $term->name ); ?>">
					<?php if ( $flag ) : ?>
						<img class="wpc-country-switcher__flag wpc-country-switcher__flag--lg" src="<?php echo esc_url( $flag ); ?>" alt="" />
					<?php endif; ?>
					<span><?php echo esc_html( strtoupper( $term->slug ) ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
	<script>
	( function() {
		var wrap = document.currentScript.previousElementSibling;
		if ( ! wrap || ! wrap.classList.contains( 'wpc-country-switcher' ) ) {
			return;
		}
		var toggle = wrap.querySelector( '.wpc-country-switcher__toggle' );
		toggle.addEventListener( 'click', function( e ) {
			e.stopPropagation();
			var open = wrap.classList.toggle( 'is-open' );
			toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		} );
		document.addEventListener( 'click', function() {
			wrap.classList.remove( 'is-open' );
			toggle.setAttribute( 'aria-expanded', 'false' );
		} );
		document.addEventListener( 'keydown', function( e ) {
			if ( e.key === 'Escape' ) {
				wrap.classList.remove( 'is-open' );
				toggle.setAttribute( 'aria-expanded', 'false' );
			}
		} );
	} )();
	</script>
	<?php
}

/**
 * Data for seed_ae_stores_batch2() — 20 additional real, well-known
 * UAE-market stores, each with a couple of coupons. A plain function
 * (rather than inline in the CLI method) so a one-off script can reuse the
 * exact same data without going through WP_CLI.
 */
function wpcoupon_ae_stores_batch2_data() {
	return array(
		array(
			'name' => 'Amazon.ae', 'slug' => 'amazon-ae', 'url' => 'https://www.amazon.ae/', 'category' => 'ecommerce',
			'coupons' => array(
				array( 'title' => 'Amazon.ae – Up to 40% Off Electronics Sale', 'title_ar' => 'أمازون الإمارات – خصم يصل إلى 40% على الإلكترونيات', 'type' => 'sale', 'save' => '40% OFF', 'desc_en' => 'Save up to 40% across laptops, phones and accessories in this week\'s Amazon.ae electronics sale.', 'desc_ar' => 'وفّر حتى 40% على أجهزة الكمبيوتر المحمولة والهواتف والإكسسوارات في تخفيضات أمازون الإمارات لهذا الأسبوع.' ),
				array( 'title' => 'Amazon.ae – 15% Off First Prime Order with Code PRIME15', 'title_ar' => 'أمازون الإمارات – خصم 15% على أول طلب برايم برمز PRIME15', 'type' => 'code', 'code' => 'PRIME15', 'save' => '15% OFF', 'desc_en' => 'New Prime members get 15% off their first order sitewide.', 'desc_ar' => 'يحصل أعضاء برايم الجدد على خصم 15% على أول طلب لهم في جميع أنحاء الموقع.' ),
			),
		),
		array(
			'name' => '6thStreet', 'slug' => '6thstreet', 'url' => 'https://www.6thstreet.com/ae-en/', 'category' => 'fashion',
			'coupons' => array(
				array( 'title' => '6thStreet – Up to 50% Off Sneakers & Sportswear', 'title_ar' => '6thStreet – خصم يصل إلى 50% على الأحذية الرياضية والملابس الرياضية', 'type' => 'sale', 'save' => '50% OFF', 'desc_en' => 'Discounts across leading sneaker and sportswear brands.', 'desc_ar' => 'تخفيضات على أبرز ماركات الأحذية والملابس الرياضية.' ),
				array( 'title' => '6thStreet – Extra 10% Off with Code STREET10', 'title_ar' => '6thStreet – خصم إضافي 10% برمز STREET10', 'type' => 'code', 'code' => 'STREET10', 'save' => '10% OFF', 'desc_en' => 'Stack an extra 10% off already-discounted items at checkout.', 'desc_ar' => 'احصل على خصم إضافي 10% على المنتجات المخفّضة بالفعل عند الدفع.' ),
			),
		),
		array(
			'name' => 'Sivvi', 'slug' => 'sivvi', 'url' => 'https://www.sivvi.com/en-ae', 'category' => 'fashion',
			'coupons' => array(
				array( 'title' => 'Sivvi – Up to 70% Off End of Season Sale', 'title_ar' => 'سيفي – خصم يصل إلى 70% في تخفيضات نهاية الموسم', 'type' => 'sale', 'save' => '70% OFF', 'desc_en' => 'Deep discounts on fashion and accessories as the season closes out.', 'desc_ar' => 'خصومات كبيرة على الأزياء والإكسسوارات مع نهاية الموسم.' ),
				array( 'title' => 'Sivvi – 20% Off New Arrivals with Code SIVVI20', 'title_ar' => 'سيفي – خصم 20% على المنتجات الجديدة برمز SIVVI20', 'type' => 'code', 'code' => 'SIVVI20', 'save' => '20% OFF', 'desc_en' => '20% off the newest arrivals for a limited time.', 'desc_ar' => 'خصم 20% على أحدث المنتجات لفترة محدودة.' ),
			),
		),
		array(
			'name' => 'Styli', 'slug' => 'styli-uae', 'url' => 'https://www.styli.com/ae-en', 'category' => 'fashion',
			'coupons' => array(
				array( 'title' => 'Styli – Up to 60% Off Everything', 'title_ar' => 'ستايلي – خصم يصل إلى 60% على كل شيء', 'type' => 'sale', 'save' => '60% OFF', 'desc_en' => 'Storewide markdowns on the latest affordable fashion.', 'desc_ar' => 'تخفيضات شاملة على أحدث صيحات الأزياء بأسعار مناسبة.' ),
				array( 'title' => 'Styli – Free Shipping on Orders Over AED 100', 'title_ar' => 'ستايلي – شحن مجاني للطلبات فوق 100 درهم', 'type' => 'sale', 'save' => 'FREE DELIVERY', 'desc_en' => 'No delivery charge on orders above AED 100.', 'desc_ar' => 'بدون رسوم توصيل للطلبات التي تزيد عن 100 درهم.' ),
			),
		),
		array(
			'name' => 'Virgin Megastore', 'slug' => 'virgin-megastore-uae', 'url' => 'https://www.virginmegastore.ae/', 'category' => 'arts-and-entertainment',
			'coupons' => array(
				array( 'title' => 'Virgin Megastore – 20% Off Headphones & Audio', 'title_ar' => 'فيرجن ميجاستور – خصم 20% على السماعات والصوتيات', 'type' => 'sale', 'save' => '20% OFF', 'desc_en' => '20% off a selection of headphones, speakers and audio gear.', 'desc_ar' => 'خصم 20% على تشكيلة من السماعات ومكبرات الصوت وأجهزة الصوت.' ),
				array( 'title' => 'Virgin Megastore – 10% Off with Code VIRGIN10', 'title_ar' => 'فيرجن ميجاستور – خصم 10% برمز VIRGIN10', 'type' => 'code', 'code' => 'VIRGIN10', 'save' => '10% OFF', 'desc_en' => '10% off books, games and gadgets storewide.', 'desc_ar' => 'خصم 10% على الكتب والألعاب والأجهزة في جميع أنحاء المتجر.' ),
			),
		),
		array(
			'name' => 'Lulu Hypermarket', 'slug' => 'lulu-hypermarket', 'url' => 'https://www.luluhypermarket.com/en-ae', 'category' => 'food-beverages-and-tobacco',
			'coupons' => array(
				array( 'title' => 'Lulu Hypermarket – Weekly Grocery Offers', 'title_ar' => 'لولو هايبرماركت – عروض البقالة الأسبوعية', 'type' => 'sale', 'save' => 'UP TO 30% OFF', 'desc_en' => 'Fresh weekly savings on groceries and household essentials.', 'desc_ar' => 'وفورات أسبوعية جديدة على البقالة ومستلزمات المنزل.' ),
				array( 'title' => 'Lulu Hypermarket – AED 15 Off Online Orders Over AED 100', 'title_ar' => 'لولو هايبرماركت – خصم 15 درهمًا على الطلبات الإلكترونية فوق 100 درهم', 'type' => 'code', 'code' => 'LULU15', 'save' => 'AED 15 OFF', 'desc_en' => 'AED 15 off your online grocery order over AED 100.', 'desc_ar' => 'خصم 15 درهمًا على طلب البقالة الإلكتروني الذي يتجاوز 100 درهم.' ),
			),
		),
		array(
			'name' => 'Danube Home', 'slug' => 'danube-home', 'url' => 'https://danubehome.com/uae/', 'category' => 'home-and-garden',
			'coupons' => array(
				array( 'title' => 'Danube Home – Up to 50% Off Furniture', 'title_ar' => 'دانوب هوم – خصم يصل إلى 50% على الأثاث', 'type' => 'sale', 'save' => '50% OFF', 'desc_en' => 'Save on furniture and home decor across every room.', 'desc_ar' => 'وفّر على الأثاث وديكور المنزل لكل غرفة.' ),
				array( 'title' => 'Danube Home – Free Delivery on Orders Over AED 500', 'title_ar' => 'دانوب هوم – توصيل مجاني للطلبات فوق 500 درهم', 'type' => 'sale', 'save' => 'FREE DELIVERY', 'desc_en' => 'Free delivery when you spend AED 500 or more.', 'desc_ar' => 'توصيل مجاني عند الإنفاق بقيمة 500 درهم أو أكثر.' ),
			),
		),
		array(
			'name' => 'IKEA UAE', 'slug' => 'ikea-uae', 'url' => 'https://www.ikea.com/ae/en/', 'category' => 'home-and-garden',
			'coupons' => array(
				array( 'title' => 'IKEA UAE – Family Member Exclusive Offers', 'title_ar' => 'ايكيا الإمارات – عروض حصرية لأعضاء IKEA Family', 'type' => 'sale', 'save' => 'MEMBER DEALS', 'desc_en' => 'Exclusive prices for IKEA Family members this month.', 'desc_ar' => 'أسعار حصرية لأعضاء IKEA Family هذا الشهر.' ),
				array( 'title' => 'IKEA UAE – 10% Off Kitchen Essentials with Code IKEA10', 'title_ar' => 'ايكيا الإمارات – خصم 10% على مستلزمات المطبخ برمز IKEA10', 'type' => 'code', 'code' => 'IKEA10', 'save' => '10% OFF', 'desc_en' => '10% off kitchenware and organizers.', 'desc_ar' => 'خصم 10% على أدوات المطبخ وحلول التنظيم.' ),
			),
		),
		array(
			'name' => 'Talabat', 'slug' => 'talabat', 'url' => 'https://www.talabat.com/uae', 'category' => 'restaurant-and-dining',
			'coupons' => array(
				array( 'title' => 'Talabat – 25% Off Your First Order with Code TAL25', 'title_ar' => 'طلبات – خصم 25% على أول طلب برمز TAL25', 'type' => 'code', 'code' => 'TAL25', 'save' => '25% OFF', 'desc_en' => '25% off your first food order through the app.', 'desc_ar' => 'خصم 25% على أول طلب طعام عبر التطبيق.' ),
				array( 'title' => 'Talabat – Free Delivery on Orders Over AED 30', 'title_ar' => 'طلبات – توصيل مجاني للطلبات فوق 30 درهمًا', 'type' => 'sale', 'save' => 'FREE DELIVERY', 'desc_en' => 'No delivery fee on qualifying orders over AED 30.', 'desc_ar' => 'بدون رسوم توصيل للطلبات المؤهلة التي تزيد عن 30 درهمًا.' ),
			),
		),
		array(
			'name' => 'Careem', 'slug' => 'careem', 'url' => 'https://www.careem.com/en-ae/', 'category' => 'travel',
			'coupons' => array(
				array( 'title' => 'Careem – 30% Off Your Next 3 Rides with Code RIDE30', 'title_ar' => 'كريم – خصم 30% على رحلاتك الثلاث القادمة برمز RIDE30', 'type' => 'code', 'code' => 'RIDE30', 'save' => '30% OFF', 'desc_en' => '30% off your next three Careem rides.', 'desc_ar' => 'خصم 30% على رحلاتك الثلاث القادمة مع كريم.' ),
				array( 'title' => 'Careem – Free Delivery on Careem Now First Order', 'title_ar' => 'كريم – توصيل مجاني على أول طلب من Careem Now', 'type' => 'sale', 'save' => 'FREE DELIVERY', 'desc_en' => 'No delivery charge on your first Careem Now food or grocery order.', 'desc_ar' => 'بدون رسوم توصيل على أول طلب طعام أو بقالة عبر Careem Now.' ),
			),
		),
		array(
			'name' => 'Emirates Holidays', 'slug' => 'emirates-holidays', 'url' => 'https://www.emiratesholidays.com/', 'category' => 'travel',
			'coupons' => array(
				array( 'title' => 'Emirates Holidays – Up to AED 500 Off Package Bookings', 'title_ar' => 'إمارات هوليدايز – خصم يصل إلى 500 درهم على باقات السفر', 'type' => 'sale', 'save' => 'UP TO AED 500 OFF', 'desc_en' => 'Save on flight-and-hotel package bookings for a limited time.', 'desc_ar' => 'وفّر على حجوزات باقات الطيران والفندق لفترة محدودة.' ),
				array( 'title' => 'Emirates Holidays – 10% Off with Code EHOL10', 'title_ar' => 'إمارات هوليدايز – خصم 10% برمز EHOL10', 'type' => 'code', 'code' => 'EHOL10', 'save' => '10% OFF', 'desc_en' => '10% off selected holiday packages booked online.', 'desc_ar' => 'خصم 10% على باقات عطلات مختارة عند الحجز عبر الإنترنت.' ),
			),
		),
		array(
			'name' => 'Cleartrip UAE', 'slug' => 'cleartrip-uae', 'url' => 'https://www.cleartrip.com/ae/', 'category' => 'travel',
			'coupons' => array(
				array( 'title' => 'Cleartrip UAE – AED 100 Off Flight Bookings with Code FLY100', 'title_ar' => 'كليرتريب الإمارات – خصم 100 درهم على حجوزات الطيران برمز FLY100', 'type' => 'code', 'code' => 'FLY100', 'save' => 'AED 100 OFF', 'desc_en' => 'AED 100 off domestic and international flight bookings.', 'desc_ar' => 'خصم 100 درهم على حجوزات الرحلات الداخلية والدولية.' ),
				array( 'title' => 'Cleartrip UAE – Up to 20% Off Hotel Stays', 'title_ar' => 'كليرتريب الإمارات – خصم يصل إلى 20% على الإقامة الفندقية', 'type' => 'sale', 'save' => '20% OFF', 'desc_en' => 'Save up to 20% on hotel bookings across the UAE and abroad.', 'desc_ar' => 'وفّر حتى 20% على حجوزات الفنادق داخل الإمارات وخارجها.' ),
			),
		),
		array(
			'name' => 'Awok', 'slug' => 'awok', 'url' => 'https://www.awok.com/', 'category' => 'electronics',
			'coupons' => array(
				array( 'title' => 'Awok – Up to 45% Off Home Electronics', 'title_ar' => 'أووك – خصم يصل إلى 45% على الأجهزة الإلكترونية المنزلية', 'type' => 'sale', 'save' => '45% OFF', 'desc_en' => 'Discounts across small appliances and home electronics.', 'desc_ar' => 'خصومات على الأجهزة المنزلية الصغيرة والإلكترونيات المنزلية.' ),
				array( 'title' => 'Awok – Extra 10% Off with Code AWOK10', 'title_ar' => 'أووك – خصم إضافي 10% برمز AWOK10', 'type' => 'code', 'code' => 'AWOK10', 'save' => '10% OFF', 'desc_en' => 'An extra 10% off already discounted electronics.', 'desc_ar' => 'خصم إضافي 10% على الإلكترونيات المخفّضة بالفعل.' ),
			),
		),
		array(
			'name' => 'Jumbo Electronics', 'slug' => 'jumbo-electronics', 'url' => 'https://www.jumbo.ae/', 'category' => 'electronics',
			'coupons' => array(
				array( 'title' => 'Jumbo Electronics – Up to 35% Off TVs & Laptops', 'title_ar' => 'جمبو للإلكترونيات – خصم يصل إلى 35% على التلفزيونات وأجهزة الكمبيوتر المحمولة', 'type' => 'sale', 'save' => '35% OFF', 'desc_en' => 'Markdowns on major TV and laptop brands.', 'desc_ar' => 'تخفيضات على أبرز ماركات التلفزيونات وأجهزة الكمبيوتر المحمولة.' ),
				array( 'title' => 'Jumbo Electronics – Free Installation with Code JUMBOSETUP', 'title_ar' => 'جمبو للإلكترونيات – تركيب مجاني برمز JUMBOSETUP', 'type' => 'code', 'code' => 'JUMBOSETUP', 'save' => 'FREE INSTALLATION', 'desc_en' => 'Free installation on qualifying large appliance purchases.', 'desc_ar' => 'تركيب مجاني عند شراء الأجهزة الكبيرة المؤهلة.' ),
			),
		),
		array(
			'name' => 'Homebox', 'slug' => 'homebox-uae', 'url' => 'https://www.homebox.com/en-ae/', 'category' => 'furniture',
			'coupons' => array(
				array( 'title' => 'Homebox – Up to 50% Off Clearance', 'title_ar' => 'هوم بوكس – خصم يصل إلى 50% على تصفية المخزون', 'type' => 'sale', 'save' => '50% OFF', 'desc_en' => 'Clearance prices on furniture and home accessories.', 'desc_ar' => 'أسعار تصفية على الأثاث وإكسسوارات المنزل.' ),
				array( 'title' => 'Homebox – 15% Off with Code HOMEBOX15', 'title_ar' => 'هوم بوكس – خصم 15% برمز HOMEBOX15', 'type' => 'code', 'code' => 'HOMEBOX15', 'save' => '15% OFF', 'desc_en' => '15% off new-season furniture collections.', 'desc_ar' => 'خصم 15% على تشكيلات الأثاث للموسم الجديد.' ),
			),
		),
		array(
			'name' => 'Boots UAE', 'slug' => 'boots-uae', 'url' => 'https://www.boots.ae/', 'category' => 'beauty-and-cosmetics',
			'coupons' => array(
				array( 'title' => 'Boots UAE – Up to 40% Off Skincare & Makeup', 'title_ar' => 'بوتس الإمارات – خصم يصل إلى 40% على العناية بالبشرة والمكياج', 'type' => 'sale', 'save' => '40% OFF', 'desc_en' => 'Save across leading skincare and makeup brands.', 'desc_ar' => 'وفّر على أبرز ماركات العناية بالبشرة والمكياج.' ),
				array( 'title' => 'Boots UAE – 3 for 2 on Selected Vitamins', 'title_ar' => 'بوتس الإمارات – اشترِ اثنين واحصل على الثالث مجانًا على فيتامينات مختارة', 'type' => 'sale', 'save' => '3 FOR 2', 'desc_en' => 'Buy two, get one free across selected vitamin ranges.', 'desc_ar' => 'اشترِ اثنين واحصل على الثالث مجانًا من تشكيلات فيتامينات مختارة.' ),
			),
		),
		array(
			'name' => 'Farfetch UAE', 'slug' => 'farfetch-uae', 'url' => 'https://www.farfetch.com/ae/', 'category' => 'fashion',
			'coupons' => array(
				array( 'title' => 'Farfetch UAE – Up to 50% Off Designer Sale', 'title_ar' => 'فارفيتش الإمارات – خصم يصل إلى 50% على تخفيضات المصممين', 'type' => 'sale', 'save' => '50% OFF', 'desc_en' => 'Designer fashion markdowns across menswear and womenswear.', 'desc_ar' => 'تخفيضات على أزياء المصممين للرجال والنساء.' ),
				array( 'title' => 'Farfetch UAE – 10% Off New Customers with Code FFNEW10', 'title_ar' => 'فارفيتش الإمارات – خصم 10% للعملاء الجدد برمز FFNEW10', 'type' => 'code', 'code' => 'FFNEW10', 'save' => '10% OFF', 'desc_en' => '10% off your first Farfetch order.', 'desc_ar' => 'خصم 10% على أول طلب لك من فارفيتش.' ),
			),
		),
		array(
			'name' => 'Level Shoes', 'slug' => 'level-shoes', 'url' => 'https://www.levelshoes.com/en-ae/', 'category' => 'apparel-and-accessories',
			'coupons' => array(
				array( 'title' => 'Level Shoes – Up to 60% Off Season Sale', 'title_ar' => 'ليفل شوز – خصم يصل إلى 60% في تخفيضات الموسم', 'type' => 'sale', 'save' => '60% OFF', 'desc_en' => 'Luxury footwear and accessories at season-sale prices.', 'desc_ar' => 'أحذية وإكسسوارات فاخرة بأسعار تخفيضات الموسم.' ),
				array( 'title' => 'Level Shoes – Free Shipping Sitewide', 'title_ar' => 'ليفل شوز – شحن مجاني على كامل الموقع', 'type' => 'sale', 'save' => 'FREE DELIVERY', 'desc_en' => 'Free shipping on every order, no minimum spend.', 'desc_ar' => 'شحن مجاني على كل طلب، بدون حد أدنى للإنفاق.' ),
			),
		),
		array(
			'name' => 'Etisalat', 'slug' => 'etisalat', 'url' => 'https://www.etisalat.ae/', 'category' => 'web-services',
			'coupons' => array(
				array( 'title' => 'Etisalat – Discounted eLife Home Bundles', 'title_ar' => 'اتصالات – باقات eLife المنزلية بأسعار مخفضة', 'type' => 'sale', 'save' => 'BUNDLE DEALS', 'desc_en' => 'Special pricing on eLife internet and TV bundles for new subscribers.', 'desc_ar' => 'أسعار خاصة على باقات إنترنت وتلفزيون eLife للمشتركين الجدد.' ),
				array( 'title' => 'Etisalat – AED 50 Bill Credit with Code ETISALAT50', 'title_ar' => 'اتصالات – رصيد فاتورة 50 درهمًا برمز ETISALAT50', 'type' => 'code', 'code' => 'ETISALAT50', 'save' => 'AED 50 CREDIT', 'desc_en' => 'AED 50 bill credit when you switch your mobile plan online.', 'desc_ar' => 'رصيد فاتورة بقيمة 50 درهمًا عند تحويل باقتك عبر الإنترنت.' ),
			),
		),
		array(
			'name' => 'du', 'slug' => 'du-telecom', 'url' => 'https://www.du.ae/', 'category' => 'web-services',
			'coupons' => array(
				array( 'title' => 'du – Up to 20% Off Postpaid Plans', 'title_ar' => 'دو – خصم يصل إلى 20% على باقات الفوترة اللاحقة', 'type' => 'sale', 'save' => '20% OFF', 'desc_en' => 'Discounted rates on selected postpaid mobile plans.', 'desc_ar' => 'أسعار مخفضة على باقات مختارة من الفوترة اللاحقة.' ),
				array( 'title' => 'du – Free Router with Home Internet Sign-Up', 'title_ar' => 'دو – راوتر مجاني عند الاشتراك في إنترنت المنزل', 'type' => 'sale', 'save' => 'FREE ROUTER', 'desc_en' => 'A free router included with new home internet subscriptions.', 'desc_ar' => 'راوتر مجاني مع كل اشتراك جديد في إنترنت المنزل.' ),
			),
		),
	);
}

/**
 * One-time backfill: creates IN / AE country terms and assigns every
 * existing store + coupon to IN so nothing loses visibility.
 *
 * Deliberately NOT hooked to run automatically on a web request (that could
 * mean thousands of wp_set_object_terms() calls blocking a real page load).
 * Run it explicitly once, from WP-CLI, after deploying this code:
 *
 *   wp wpcoupon migrate-countries
 *
 * Safe to re-run: it only fills in what's missing.
 */
class WPCoupon_Countries_CLI_Command {

	/**
	 * Create the default countries (if missing) and backfill every
	 * existing store/coupon to the default country (IN).
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Report what would change without writing anything.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wpcoupon migrate-countries
	 *     wp wpcoupon migrate-countries --dry-run
	 *
	 * @subcommand migrate-countries
	 * @when after_wp_load
	 */
	public function migrate_countries( $args, $assoc_args ) {
		$dry_run = ! empty( $assoc_args['dry-run'] );

		$countries = array(
			'in' => array(
				'name'      => 'India',
				'currency'  => '₹',
				'is_default'=> true,
			),
			'ae' => array(
				'name'      => 'United Arab Emirates',
				'currency'  => 'AED',
				'is_default'=> false,
			),
		);

		$term_ids = array();

		foreach ( $countries as $slug => $data ) {
			$term = get_term_by( 'slug', $slug, 'wpc_country' );

			if ( ! $term ) {
				WP_CLI::log( sprintf( 'Creating country term: %s (%s)', $data['name'], $slug ) );
				if ( ! $dry_run ) {
					$result = wp_insert_term( $data['name'], 'wpc_country', array( 'slug' => $slug ) );
					if ( is_wp_error( $result ) ) {
						WP_CLI::warning( $result->get_error_message() );
						continue;
					}
					$term = get_term( $result['term_id'], 'wpc_country' );
				}
			} else {
				WP_CLI::log( sprintf( 'Country term already exists: %s (%s)', $data['name'], $slug ) );
			}

			if ( $term && ! $dry_run ) {
				$term_ids[ $slug ] = $term->term_id;
				update_term_meta( $term->term_id, '_wpc_currency_symbol', $data['currency'] );
				if ( $data['is_default'] ) {
					update_term_meta( $term->term_id, '_wpc_is_default', 'on' );
				}
			}
		}

		if ( $dry_run ) {
			WP_CLI::success( 'Dry run complete. No data was changed.' );
			return;
		}

		$default_term_id = isset( $term_ids['in'] ) ? $term_ids['in'] : 0;

		if ( ! $default_term_id ) {
			WP_CLI::error( 'Could not resolve the IN country term id, aborting backfill.' );
			return;
		}

		// Backfill stores (coupon_store terms) missing _wpc_store_countries.
		$store_terms = get_terms(
			array(
				'taxonomy'   => 'coupon_store',
				'hide_empty' => false,
				'fields'     => 'ids',
			)
		);

		$stores_updated = 0;
		foreach ( $store_terms as $store_term_id ) {
			$existing = get_term_meta( $store_term_id, '_wpc_store_countries', true );
			if ( empty( $existing ) ) {
				update_term_meta( $store_term_id, '_wpc_store_countries', array( 'in' ) );
				$stores_updated++;
			}
		}
		WP_CLI::log( sprintf( 'Stores backfilled to IN: %d (of %d total)', $stores_updated, count( $store_terms ) ) );

		// Backfill coupons missing any wpc_country term.
		$coupon_ids = get_posts(
			array(
				'post_type'      => 'coupon',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'tax_query'      => array(
					array(
						'taxonomy' => 'wpc_country',
						'operator' => 'NOT EXISTS',
					),
				),
			)
		);

		$coupons_updated = 0;
		foreach ( $coupon_ids as $coupon_id ) {
			wp_set_object_terms( $coupon_id, array( $default_term_id ), 'wpc_country', false );
			$coupons_updated++;
		}
		WP_CLI::log( sprintf( 'Coupons backfilled to IN: %d', $coupons_updated ) );

		WP_CLI::success( 'Country migration complete.' );
	}

	/**
	 * Create a handful of real, well-known UAE-market stores with sample
	 * coupons, all tagged for the AE country — so the AE side of the site
	 * has real content to show instead of an empty state.
	 *
	 * Store domains are real; the coupon codes/offers are placeholder
	 * content (the same way this site's real India stores start life
	 * before an editor plugs in the actual current affiliate offers) —
	 * replace them with real, current offers before this goes live.
	 *
	 * Idempotent: matches stores by slug and coupons by title, so
	 * re-running only fills in whatever's missing.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Report what would be created without writing anything.
	 *
	 * @subcommand seed-ae-stores
	 * @when after_wp_load
	 */
	public function seed_ae_stores( $args, $assoc_args ) {
		$dry_run = ! empty( $assoc_args['dry-run'] );

		$ae_term = get_term_by( 'slug', 'ae', 'wpc_country' );
		if ( ! $ae_term ) {
			WP_CLI::error( 'The AE country term does not exist yet — run `wp wpcoupon migrate-countries` first.' );
			return;
		}

		$author_id = 0;
		$admins    = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
		if ( $admins ) {
			$author_id = (int) $admins[0];
		}

		$stores = array(
			array(
				'name'     => 'Noon',
				'slug'     => 'noon-ae',
				'url'      => 'https://www.noon.com/uae-en/',
				'category' => 'electronics',
				'coupons'  => array(
					array( 'title' => 'Noon UAE – Up to 50% Off Electronics', 'type' => 'sale', 'save' => '50% OFF' ),
					array( 'title' => 'Noon UAE – Extra 10% Off Sitewide with Code NOON10', 'type' => 'code', 'code' => 'NOON10', 'save' => '10% OFF' ),
				),
			),
			array(
				'name'     => 'Namshi',
				'slug'     => 'namshi',
				'url'      => 'https://www.namshi.com/uae-en/',
				'category' => 'fashion',
				'coupons'  => array(
					array( 'title' => 'Namshi – Up to 70% Off Fashion Clearance', 'type' => 'sale', 'save' => '70% OFF' ),
					array( 'title' => 'Namshi – 15% Off Your First Order with Code NAMSHI15', 'type' => 'code', 'code' => 'NAMSHI15', 'save' => '15% OFF' ),
				),
			),
			array(
				'name'     => 'Sharaf DG',
				'slug'     => 'sharaf-dg',
				'url'      => 'https://www.sharafdg.com/uae/',
				'category' => 'electronics',
				'coupons'  => array(
					array( 'title' => 'Sharaf DG – Extra 10% Off Electronics with Code SHARAF10', 'type' => 'code', 'code' => 'SHARAF10', 'save' => '10% OFF' ),
					array( 'title' => 'Sharaf DG – Free Delivery on Orders Above AED 200', 'type' => 'sale', 'save' => 'FREE DELIVERY' ),
				),
			),
			array(
				'name'     => 'Carrefour UAE',
				'slug'     => 'carrefour-uae',
				'url'      => 'https://www.carrefouruae.com/mafuae/en/',
				'category' => 'food-beverages-and-tobacco',
				'coupons'  => array(
					array( 'title' => 'Carrefour UAE – Up to 30% Off Weekly Offers', 'type' => 'sale', 'save' => '30% OFF' ),
					array( 'title' => 'Carrefour UAE – AED 20 Off Orders Over AED 150 with Code CARREFOUR20', 'type' => 'code', 'code' => 'CARREFOUR20', 'save' => 'AED 20 OFF' ),
				),
			),
			array(
				'name'     => 'Ounass',
				'slug'     => 'ounass',
				'url'      => 'https://www.ounass.ae/',
				'category' => 'fashion',
				'coupons'  => array(
					array( 'title' => 'Ounass – Up to 60% Off Luxury Fashion Sale', 'type' => 'sale', 'save' => '60% OFF' ),
					array( 'title' => 'Ounass – 10% Off New Arrivals with Code OUNASS10', 'type' => 'code', 'code' => 'OUNASS10', 'save' => '10% OFF' ),
				),
			),
		);

		$stores_created  = 0;
		$coupons_created = 0;

		foreach ( $stores as $store ) {
			$term = get_term_by( 'slug', $store['slug'], 'coupon_store' );

			if ( ! $term ) {
				WP_CLI::log( sprintf( 'Creating store: %s', $store['name'] ) );
				if ( $dry_run ) {
					$stores_created++;
				} else {
					$result = wp_insert_term( $store['name'], 'coupon_store', array( 'slug' => $store['slug'] ) );
					if ( is_wp_error( $result ) ) {
						WP_CLI::warning( $store['name'] . ': ' . $result->get_error_message() );
						continue;
					}
					$term = get_term( $result['term_id'], 'coupon_store' );
					update_term_meta( $term->term_id, '_wpc_store_url', $store['url'] );
					update_term_meta( $term->term_id, '_wpc_store_aff_url', $store['url'] );
					update_term_meta( $term->term_id, '_wpc_store_heading', '%store_name% Coupon Codes & Deals' );
					update_term_meta( $term->term_id, '_wpc_store_countries', array( 'ae' ) );
					$stores_created++;
				}
			} else {
				WP_CLI::log( sprintf( 'Store already exists: %s', $store['name'] ) );
				if ( ! $dry_run ) {
					// Make sure AE is in its country list without dropping any
					// other country it may already be tagged for.
					$countries = get_term_meta( $term->term_id, '_wpc_store_countries', true );
					$countries = is_array( $countries ) ? $countries : array();
					if ( ! in_array( 'ae', $countries, true ) ) {
						$countries[] = 'ae';
						update_term_meta( $term->term_id, '_wpc_store_countries', $countries );
					}
				}
			}

			if ( $dry_run || ! $term ) {
				foreach ( $store['coupons'] as $coupon ) {
					if ( ! get_page_by_title( $coupon['title'], OBJECT, 'coupon' ) ) {
						WP_CLI::log( '  Would create coupon: ' . $coupon['title'] );
						$coupons_created++;
					}
				}
				continue;
			}

			foreach ( $store['coupons'] as $coupon ) {
				$existing = get_page_by_title( $coupon['title'], OBJECT, 'coupon' );
				if ( $existing ) {
					WP_CLI::log( '  Coupon already exists: ' . $coupon['title'] );
					continue;
				}

				$post_id = wp_insert_post(
					array(
						'post_title'  => $coupon['title'],
						'post_type'   => 'coupon',
						'post_status' => 'publish',
						'post_author' => $author_id,
					),
					true
				);

				if ( is_wp_error( $post_id ) ) {
					WP_CLI::warning( $coupon['title'] . ': ' . $post_id->get_error_message() );
					continue;
				}

				wp_set_object_terms( $post_id, array( $term->term_id ), 'coupon_store', false );
				wp_set_object_terms( $post_id, array( $ae_term->term_id ), 'wpc_country', false );

				$category_term = get_term_by( 'slug', $store['category'], 'coupon_category' );
				if ( $category_term ) {
					wp_set_object_terms( $post_id, array( $category_term->term_id ), 'coupon_category', false );
				}

				update_post_meta( $post_id, '_wpc_coupon_type', $coupon['type'] );
				update_post_meta( $post_id, '_wpc_coupon_type_code', isset( $coupon['code'] ) ? $coupon['code'] : '' );
				update_post_meta( $post_id, '_wpc_store', array( $term->term_id ) );
				update_post_meta( $post_id, '_wpc_coupon_save', $coupon['save'] );
				update_post_meta( $post_id, '_wpc_expires', strtotime( '+90 days' ) );
				update_post_meta( $post_id, '_wpc_used', 0 );
				update_post_meta( $post_id, '_wpc_vote_up', 0 );
				update_post_meta( $post_id, '_wpc_vote_down', 0 );

				WP_CLI::log( '  Created coupon: ' . $coupon['title'] );
				$coupons_created++;
			}
		}

		if ( $dry_run ) {
			WP_CLI::success( sprintf( 'Dry run: would create %d store(s) and %d coupon(s).', $stores_created, $coupons_created ) );
		} else {
			WP_CLI::success( sprintf( 'Done: %d store(s) created, %d coupon(s) created (existing ones were left untouched).', $stores_created, $coupons_created ) );
		}
	}

	/**
	 * A second, larger batch of real, well-known UAE-market stores — same
	 * shape and same idempotency guarantees as seed_ae_stores() above, kept
	 * as a separate command so re-running it never touches the first batch.
	 *
	 * Every coupon here also gets `_wpc_title_ar` (an Arabic title) and
	 * `_wpc_desc_ar` (an Arabic description) — swapped in for the English
	 * post_title/post_content only when a visitor has Arabic selected, by
	 * the `the_title` / `the_posts` filters in this file. Both need to be
	 * *separate meta fields* rather than the `.i18n-en`/`.i18n-ar` CSS-toggle
	 * span pair used elsewhere on the homepage: a coupon's description goes
	 * through wp_trim_words() for its listing excerpt, which strips all HTML
	 * (deleting the very spans the toggle needs) before trimming, so the
	 * language swap has to happen on the raw text instead. Store *names* are
	 * deliberately left untranslated — they're brand names, not UI text.
	 *
	 * @subcommand seed-ae-stores-batch2
	 * @when after_wp_load
	 */
	public function seed_ae_stores_batch2( $args, $assoc_args ) {
		$dry_run = ! empty( $assoc_args['dry-run'] );

		$ae_term = get_term_by( 'slug', 'ae', 'wpc_country' );
		if ( ! $ae_term ) {
			WP_CLI::error( 'The AE country term does not exist yet — run `wp wpcoupon migrate-countries` first.' );
			return;
		}

		$author_id = 0;
		$admins    = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
		if ( $admins ) {
			$author_id = (int) $admins[0];
		}

		$stores = wpcoupon_ae_stores_batch2_data();

		$stores_created  = 0;
		$coupons_created = 0;

		foreach ( $stores as $store ) {
			$term = get_term_by( 'slug', $store['slug'], 'coupon_store' );

			if ( ! $term ) {
				WP_CLI::log( sprintf( 'Creating store: %s', $store['name'] ) );
				if ( $dry_run ) {
					$stores_created++;
				} else {
					$result = wp_insert_term( $store['name'], 'coupon_store', array( 'slug' => $store['slug'] ) );
					if ( is_wp_error( $result ) ) {
						WP_CLI::warning( $store['name'] . ': ' . $result->get_error_message() );
						continue;
					}
					$term = get_term( $result['term_id'], 'coupon_store' );
					update_term_meta( $term->term_id, '_wpc_store_url', $store['url'] );
					update_term_meta( $term->term_id, '_wpc_store_aff_url', $store['url'] );
					update_term_meta( $term->term_id, '_wpc_store_heading', '%store_name% Coupon Codes & Deals' );
					update_term_meta( $term->term_id, '_wpc_store_countries', array( 'ae' ) );
					$stores_created++;
				}
			} elseif ( ! $dry_run ) {
				$countries = get_term_meta( $term->term_id, '_wpc_store_countries', true );
				$countries = is_array( $countries ) ? $countries : array();
				if ( ! in_array( 'ae', $countries, true ) ) {
					$countries[] = 'ae';
					update_term_meta( $term->term_id, '_wpc_store_countries', $countries );
				}
			}

			if ( $dry_run || ! $term ) {
				foreach ( $store['coupons'] as $coupon ) {
					if ( ! get_page_by_title( $coupon['title'], OBJECT, 'coupon' ) ) {
						$coupons_created++;
					}
				}
				continue;
			}

			foreach ( $store['coupons'] as $coupon ) {
				if ( get_page_by_title( $coupon['title'], OBJECT, 'coupon' ) ) {
					continue;
				}

				$post_id = wp_insert_post(
					array(
						'post_title'   => $coupon['title'],
						'post_content' => $coupon['desc_en'],
						'post_type'    => 'coupon',
						'post_status'  => 'publish',
						'post_author'  => $author_id,
					),
					true
				);

				if ( is_wp_error( $post_id ) ) {
					WP_CLI::warning( $coupon['title'] . ': ' . $post_id->get_error_message() );
					continue;
				}

				wp_set_object_terms( $post_id, array( $term->term_id ), 'coupon_store', false );
				wp_set_object_terms( $post_id, array( $ae_term->term_id ), 'wpc_country', false );

				$category_term = get_term_by( 'slug', $store['category'], 'coupon_category' );
				if ( $category_term ) {
					wp_set_object_terms( $post_id, array( $category_term->term_id ), 'coupon_category', false );
				}

				update_post_meta( $post_id, '_wpc_coupon_type', $coupon['type'] );
				update_post_meta( $post_id, '_wpc_coupon_type_code', isset( $coupon['code'] ) ? $coupon['code'] : '' );
				update_post_meta( $post_id, '_wpc_store', array( $term->term_id ) );
				update_post_meta( $post_id, '_wpc_coupon_save', $coupon['save'] );
				update_post_meta( $post_id, '_wpc_expires', strtotime( '+90 days' ) );
				update_post_meta( $post_id, '_wpc_used', 0 );
				update_post_meta( $post_id, '_wpc_vote_up', 0 );
				update_post_meta( $post_id, '_wpc_vote_down', 0 );
				update_post_meta( $post_id, '_wpc_title_ar', $coupon['title_ar'] );
				update_post_meta( $post_id, '_wpc_desc_ar', $coupon['desc_ar'] );

				$coupons_created++;
			}
		}

		if ( $dry_run ) {
			WP_CLI::success( sprintf( 'Dry run: would create %d store(s) and %d coupon(s).', $stores_created, $coupons_created ) );
		} else {
			WP_CLI::success( sprintf( 'Done: %d store(s) created, %d coupon(s) created (existing ones were left untouched).', $stores_created, $coupons_created ) );
		}
	}
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'wpcoupon', 'WPCoupon_Countries_CLI_Command' );
}

/**
 * ---------------------------------------------------------------------
 * Content filtering by country (Step C)
 * ---------------------------------------------------------------------
 *
 * Both hooks below share the same guarantee: they return/no-op immediately
 * whenever the active country is the default one (India, today), which is
 * every visitor who has never touched the switcher. That is the "no
 * functional changes for India" contract for this step — the moment a
 * request's country isn't the default, and only then, listings narrow to
 * that country's tagged content.
 *
 * Both also deliberately skip: (a) real wp-admin screens (an admin's own
 * browser might carry a wpc_country cookie from testing the frontend
 * switcher — that must never filter what they see in the dashboard), and
 * (b) direct single-item lookups (a specific coupon permalink, or a
 * specific store's page by slug) — a bookmarked/shared link must always
 * keep working even if that item isn't tagged for the visitor's country;
 * only *listings* (archives, search, homepage widgets, "load more") are
 * narrowed.
 */

/**
 * Narrow every `coupon` post-type listing query to the active country.
 *
 * Fires for every WP_Query/get_posts() call, not just the main query, so
 * this is the single place that covers archives, search, category/tag/
 * store archives, and the theme's homepage widgets alike.
 *
 * Priority 20: the theme's own WPCoupon_Search::init() (inc/core/search.php)
 * runs at the default priority 10 and sets post_type to 'coupon' on search
 * requests — this must run after that, so the post_type check below sees
 * the final value.
 */
function wpcoupon_filter_coupons_by_country( $query ) {
	// Never touch a real wp-admin screen. wp_doing_ajax() is deliberately
	// exempted from this skip: admin-ajax.php sets is_admin() to true even
	// for the theme's own frontend "load more"/search-suggestion requests,
	// and those must stay consistent with the page that triggered them.
	if ( is_admin() && ! wp_doing_ajax() ) {
		return;
	}

	// Two ways a query can be "about coupons": it explicitly asks for the
	// coupon post type (widgets, get_posts() calls, search after
	// WPCoupon_Search has set it), OR it's a taxonomy archive for one of
	// the coupon-only taxonomies (store/category/tag) — WordPress does NOT
	// populate query_vars['post_type'] for those at this point (it's
	// resolved later, internally, from the taxonomy's registered object
	// types), so checking post_type alone misses every store/category/tag
	// archive page. is_tax() is reliable here: it's set during
	// parse_query(), which always runs before pre_get_posts.
	$post_type       = $query->get( 'post_type' );
	$is_coupon_ptype = ( 'coupon' === $post_type ) || ( is_array( $post_type ) && in_array( 'coupon', $post_type, true ) );
	$is_coupon_tax   = $query->is_tax( array( 'coupon_store', 'coupon_category', 'coupon_tag' ) );

	if ( ! $is_coupon_ptype && ! $is_coupon_tax ) {
		return;
	}

	// Direct single-item lookups (permalink, preview, ?p=123, admin-ajax
	// "get this one coupon" calls) are never filtered — only listings are.
	if ( $query->is_singular() || $query->get( 'p' ) || $query->get( 'page_id' ) || $query->get( 'name' ) ) {
		return;
	}

	$country = wpcoupon_get_current_country();
	$default = wpcoupon_get_default_country();

	$country_clause = array(
		'relation' => 'OR',
		array(
			'taxonomy' => 'wpc_country',
			'field'    => 'slug',
			'terms'    => array( $country ),
		),
	);

	// On the default country only, also allow coupons with NO wpc_country
	// term at all — matching wpcoupon_get_coupon_countries()'s own fallback
	// ("untagged = default country"). Every coupon that existed before this
	// feature shipped got explicitly tagged 'in' by the Step A migration,
	// so this branch is a pure safety net for anything created without
	// using the Countries field — it's what keeps this a genuine no-op for
	// all of today's content while still stopping new country-specific
	// content (e.g. AE-only stores) from leaking into India's listings.
	if ( $country === $default ) {
		$country_clause[] = array(
			'taxonomy' => 'wpc_country',
			'operator' => 'NOT EXISTS',
		);
	}

	$tax_query = $query->get( 'tax_query' );
	if ( ! is_array( $tax_query ) ) {
		$tax_query = array();
	}

	$tax_query[] = $country_clause;

	// Preserve whatever relation the query already had (e.g. a store or
	// category tax_query already present) and AND our clause onto it.
	if ( count( $tax_query ) > 1 && empty( $tax_query['relation'] ) ) {
		$tax_query['relation'] = 'AND';
	}

	$query->set( 'tax_query', $tax_query );
}
add_action( 'pre_get_posts', 'wpcoupon_filter_coupons_by_country', 20 );

/**
 * Narrow "browse many stores" queries (coupon_store terms) to stores that
 * operate in the active country.
 *
 * Deliberately skips single-term lookups (get_term_by( 'slug', ... ), the
 * kind of call that resolves /store/amazon/'s own page) via the slug/
 * term_taxonomy_id/single-id-include check below, so a store's page never
 * 404s for a visitor just because that store isn't tagged for their
 * country — only the *listing* of stores narrows. A curated multi-ID
 * `include` (e.g. an admin's hand-picked "Popular Stores" widget) is still
 * a listing, not a single lookup, so only a *single-ID* include is treated
 * as "this resolves one specific term" and skipped.
 */
function wpcoupon_filter_stores_by_country( $args, $taxonomies ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $args;
	}

	if ( ! in_array( 'coupon_store', (array) $taxonomies, true ) ) {
		return $args;
	}

	$is_single_include = ! empty( $args['include'] ) && 1 === count( (array) $args['include'] );

	if ( ! empty( $args['slug'] ) || ! empty( $args['term_taxonomy_id'] ) || $is_single_include ) {
		return $args;
	}

	$country = wpcoupon_get_current_country();
	$default = wpcoupon_get_default_country();

	$country_clause = array(
		'relation' => 'OR',
		array(
			'key'     => '_wpc_store_countries',
			'value'   => '"' . $country . '"',
			'compare' => 'LIKE',
		),
	);

	// Same safety net as the coupon filter above: on the default country,
	// also allow stores with no _wpc_store_countries meta at all, matching
	// wpcoupon_get_store_countries()'s own fallback. Every store that
	// existed before this feature shipped got explicitly tagged 'in' by
	// the Step A migration, so in practice this is a no-op for existing
	// stores — what it actually does is stop a new country-specific store
	// (e.g. an AE-only one) from also showing up in India's listings.
	if ( $country === $default ) {
		$country_clause[] = array(
			'key'     => '_wpc_store_countries',
			'compare' => 'NOT EXISTS',
		);
	}

	$meta_query = ( ! empty( $args['meta_query'] ) && is_array( $args['meta_query'] ) ) ? $args['meta_query'] : array();

	$meta_query[] = $country_clause;

	if ( count( $meta_query ) > 1 && empty( $meta_query['relation'] ) ) {
		$meta_query['relation'] = 'AND';
	}

	$args['meta_query'] = $meta_query;

	return $args;
}
add_filter( 'get_terms_args', 'wpcoupon_filter_stores_by_country', 20, 2 );
