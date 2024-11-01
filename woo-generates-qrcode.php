<?php
	
/*
Plugin Name: Woo Generates QR code
Description: Generates the QR code to WordPress users to integrate with WooCommerce API
Author: hellodev.us
Version: 1.1
Author URI: http://hellodev.us

License: GPLv3

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/



function woo_gqc_rand_hash() {
	if ( function_exists( 'openssl_random_pseudo_bytes' ) ) {
		return bin2hex( openssl_random_pseudo_bytes( 20 ) );
	} else {
		return sha1( wp_rand() );
	}
}

function woo_gqc_api_hash( $data ) {
	return hash_hmac( 'sha256', $data, 'wc-api' );
}


add_action( 'show_user_profile', 'woo_generates_qrcode_fields' );
add_action( 'edit_user_profile', 'woo_generates_qrcode_fields' );

function woo_generates_qrcode_fields( $user ) { 

	echo '<h3>HelloCommerce App</h3>';

	echo '<table class="form-table">';
	echo '<tr>';
	echo '<td colspan="2">QR Code for HelloCommerce App</td></tr>';

	if (get_the_author_meta('generateqrcode', $user->ID) != "on") {
		echo '<tr><td>';
		echo '<input type="hidden" name="generate-qrcode" value="0" />
	        <input type="checkbox" name="generate-qrcode" <br />';
		echo '	<span class="description">Check if you want to generate a QR Code.</span>';
		echo '</td>';
		echo '</tr>';
	}
			
	if (get_the_author_meta('generateqrcode', $user->ID) == "on") {
		
		global $wpdb;
		$string = "select consumer_secret from " . $wpdb->prefix . "woocommerce_api_keys where user_id = " . $user->ID;
		$data = $wpdb->get_results($string);
		$cs_key = get_the_author_meta('hellocommerce_csk', $user->ID);
		$css_key = $data[0]->consumer_secret;
		$site_url = str_replace("http","https",get_bloginfo('url') . '/wc-api/v3');
	
		$string = $cs_key . '|' . $css_key . '|' . $site_url; 
		
		echo '<tr>';
		echo '<td><img src="https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl=' . $string . '" /></tr>';
	}

	echo '</table>';

}

add_action( 'personal_options_update', 'save_woo_generates_qrcode_fields' );
add_action( 'edit_user_profile_update', 'save_woo_generates_qrcode_fields' );

function save_woo_generates_qrcode_fields( $user_id ) {

	if ( !current_user_can( 'edit_user', $user_id ) )
		return false;
		
		
	$status          = 2;
	$consumer_key    = 'ck_' . woo_gqc_rand_hash();
	$consumer_secret = 'cs_' . woo_gqc_rand_hash();
	
	update_usermeta($user_id, 'hellocommerce_csk', $consumer_key);
	
	$data = array(
		'user_id'         => get_current_user_id(),
		'description'     => $consumer_key,
		'permissions'     => 'read_write',
		'consumer_key'    => woo_gqc_api_hash( $consumer_key ),
		'consumer_secret' => $consumer_secret,
		'truncated_key'   => substr( $consumer_key, -7 )
	);
	
	global $wpdb;
	$wpdb->insert(
		$wpdb->prefix . 'woocommerce_api_keys',
		$data,
		array(
			'%d',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s'
		)
	);
	
	$qrcode = intval( $_POST['my-zipcode'] );
	if ( ! $safe_zipcode ) {
	  $safe_zipcode = '';
	}
	
	update_usermeta($user_id, 'generate-qrcode', sanitize_text_field($_POST['generate-qrcode']));
}

?>