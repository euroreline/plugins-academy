<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/*
 * Αν θέλεις πλήρη διαγραφή δεδομένων κατά το uninstall,
 * αποσχολίασε το παρακάτω block.
 */

/*
$partners = get_posts(array(
	'post_type'      => 'partners',
	'posts_per_page' => -1,
	'post_status'    => 'any',
	'fields'         => 'ids',
));

if ( ! empty( $partners ) ) {
	foreach ( $partners as $partner_id ) {
		wp_delete_post( $partner_id, true );
	}
}

delete_option('pmp_google_maps_api_key');
*/