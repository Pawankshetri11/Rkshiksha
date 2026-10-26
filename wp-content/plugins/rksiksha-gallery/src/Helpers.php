<?php
/**
 * Public helpers for the theme.
 */
declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return ordered array of photo records used by the theme.
 *
 * Each record:
 *   [
 *     'id'    => attachment ID,
 *     'url'   => full-size image URL,
 *     'thumb' => medium-sized URL (fallback to full),
 *     'alt'   => alt text,
 *     'w'     => width,
 *     'h'     => height,
 *   ]
 *
 * @return array<int, array<string, mixed>>
 */
function rks_gallery_get_photos(): array {
	$ids = (array) get_option( RKSG_OPTION, [] );
	$out = [];
	foreach ( $ids as $id ) {
		$id = (int) $id;
		if ( $id <= 0 ) {
			continue;
		}
		$full = wp_get_attachment_image_src( $id, 'full' );
		if ( ! $full ) {
			continue;
		}
		$mid  = wp_get_attachment_image_src( $id, 'large' );
		$alt  = (string) get_post_meta( $id, '_wp_attachment_image_alt', true );
		$out[] = [
			'id'    => $id,
			'url'   => $full[0],
			'thumb' => $mid ? $mid[0] : $full[0],
			'alt'   => $alt !== '' ? $alt : get_the_title( $id ),
			'w'     => (int) $full[1],
			'h'     => (int) $full[2],
		];
	}
	return $out;
}
