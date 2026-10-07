<?php
/**
 * Shortcodes for the Categories page, so the page itself is plain editor
 * content (headings/text editable by the content team) and only the live
 * category grids are generated.
 *
 *   [wpc_categories view="popular"]   categories ticked "Popular" (falls back
 *                                     to the busiest `limit` if none ticked)
 *   [wpc_categories view="all"]       every top-level category, with A-Z filter
 *   [wpc_category_count type="all"]   number of categories (type="popular" too)
 */

function wpcoupon_categories_list( $popular_only = false, $limit = 6 ) {
	$cats = get_terms( array(
		'taxonomy'   => 'coupon_category',
		'hide_empty' => false,
		'parent'     => 0,
		'orderby'    => 'name',
	) );
	if ( is_wp_error( $cats ) ) {
		return array();
	}
	if ( ! $popular_only ) {
		return $cats;
	}
	$flagged = array_values( array_filter( $cats, function ( $c ) {
		return 'on' === get_term_meta( $c->term_id, '_wpc_cat_popular', true );
	} ) );
	if ( $flagged ) {
		return $flagged;
	}
	usort( $cats, function ( $a, $b ) {
		return $b->count - $a->count;
	} );
	return array_slice( $cats, 0, $limit );
}

// Uploaded image first, icon font second (same priority as the homepage category grid).
function wpcoupon_category_media( $term_id ) {
	$image_id = get_term_meta( $term_id, '_wpc_cat_image_id', true );
	if ( $image_id > 0 ) {
		$image = wp_get_attachment_image_src( $image_id, 'medium' );
		if ( $image ) {
			return '<img src="' . esc_url( $image[0] ) . '" alt="" loading="lazy" />';
		}
	}
	$icon = trim( (string) get_term_meta( $term_id, '_wpc_icon', true ) );
	return '<i class="' . esc_attr( $icon ?: 'tag icon' ) . '"></i>';
}

add_shortcode( 'wpc_category_count', function ( $atts ) {
	$atts = shortcode_atts( array( 'type' => 'all' ), $atts );
	return (string) count( wpcoupon_categories_list( 'popular' === $atts['type'] ) );
} );

add_shortcode( 'wpc_categories', function ( $atts ) {
	$atts = shortcode_atts( array( 'view' => 'all', 'limit' => 6 ), $atts );
	$all  = 'all' === $atts['view'];
	$cats = wpcoupon_categories_list( ! $all, (int) $atts['limit'] );
	if ( ! $cats ) {
		return '';
	}

	$letters = array();
	$tiles   = '';
	foreach ( $cats as $c ) {
		$name      = html_entity_decode( $c->name, ENT_QUOTES, 'UTF-8' );
		$letter    = strtoupper( mb_substr( $name, 0, 1 ) );
		$letters[] = $letter;
		$tiles    .= sprintf(
			'<a class="wpc-cat" data-letter="%s" href="%s"><span class="wpc-cat__media">%s</span><span class="wpc-cat__name">%s</span></a>',
			esc_attr( $letter ),
			esc_url( get_term_link( $c ) ),
			wpcoupon_category_media( $c->term_id ),
			esc_html( $name )
		);
	}

	$out = '<div class="wpc-cats' . ( $all ? ' wpc-cats--all' : '' ) . '">';
	if ( $all ) {
		$out .= '<div class="wpc-cats__letters"><button type="button" class="is-active" data-letter="">' . esc_html__( 'ALL', 'wp-coupon-pro' ) . '</button>';
		foreach ( range( 'A', 'Z' ) as $l ) {
			$out .= '<button type="button" data-letter="' . $l . '"' . ( in_array( $l, $letters, true ) ? '' : ' disabled' ) . '>' . $l . '</button>';
		}
		$out .= '</div>';
		$out .= '<p class="wpc-cats__showing">' . sprintf( esc_html__( 'Showing %1$s of %2$d Categories', 'wp-coupon-pro' ), '<b class="wpc-cats__shown">' . count( $cats ) . '</b>', count( $cats ) ) . '</p>';
	}
	$out .= '<div class="wpc-cats__grid">' . $tiles . '</div></div>';

	if ( $all ) {
		$out .= <<<'JS'
<script>
document.querySelectorAll('.wpc-cats--all').forEach(function (box) {
	var tiles = box.querySelectorAll('.wpc-cat'), shown = box.querySelector('.wpc-cats__shown');
	box.querySelectorAll('.wpc-cats__letters button').forEach(function (btn) {
		btn.addEventListener('click', function () {
			box.querySelectorAll('.wpc-cats__letters button').forEach(function (b) { b.classList.toggle('is-active', b === btn); });
			var n = 0;
			tiles.forEach(function (t) {
				var ok = !btn.dataset.letter || t.dataset.letter === btn.dataset.letter;
				t.hidden = !ok;
				if (ok) n++;
			});
			shown.textContent = n;
		});
	});
});
</script>
JS;
	}
	return $out;
} );
