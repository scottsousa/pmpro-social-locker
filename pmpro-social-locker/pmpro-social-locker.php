<?php
/**
 * Plugin Name: PMPro Social Locker
 * Description: Integrate PMPro with the Social Locker plugin from OnePress (http://wordpress.org/support/plugin/social-locker). The goal is to give a user a free membership if they interact with Social Locker.
 * Version: 0.1
 * Author URI: http://www.slocumstudio.com/
 * Author: Scott Sousa (Slocum Studio), Stranger Studios
 */

// Constants
define( 'PMPROSL_FREE_LEVEL_ID', 2 );
define( 'PMPROSL_MEMBERSHIP_PERIOD_DAYS', 7 );

/**
 * This function hooks into the AJAX action of Social Locker when a click is tracked. We're using it to set a cookie 
 * so that we can verify if a user has access to the site.
 *
 * Note: They are not using check_ajax_referer(), and there is no nonce set, therefore we cannot check here either.
 */
add_action( 'wp_ajax_sociallocker_tracking', 'pmprosl_sociallocker_tracking', 1 );
add_action( 'wp_ajax_nopriv_sociallocker_tracking', 'pmprosl_sociallocker_tracking', 1 );
function pmprosl_sociallocker_tracking() {
	// First make sure we have a valid post ID
	if ( ! ( int ) $_POST['targetId'] )
		exit;

	// Next make sure the "sender" is valid
	if ( empty( $_POST['sender'] ) || ! in_array( $_POST['sender'], array( 'button', 'timer', 'cross' ) ) )
		exit;

	// Next make sure the "senderName" is valid
	if ( empty( $_POST['senderName'] ) )
		exit;

	// Finally, make sure we haven't already set the cookie
	if( isset( $_COOKIE['pmprosl_has_access_flag'] ) && ! $_COOKIE['pmprosl_has_access_flag'] )
		exit;

	// Passed all validation checks, lets set the cookies
	setcookie( 'pmprosl_has_access', PMPROSL_FREE_LEVEL_ID, ( time() + ( 60 * 60 * 24 * PMPROSL_MEMBERSHIP_PERIOD_DAYS ) ), COOKIEPATH, COOKIE_DOMAIN, false ); // has_access cookie (expires in PMPROSL_MEMBERSHIP_PERIOD_DAYS days)
	setcookie( 'pmprosl_has_access_flag', true, ( time() + ( 60 * 60 * 24 * 10 * 365 ) ), COOKIEPATH, COOKIE_DOMAIN, false ); // has_access flag cookie used to verify if a user already had access once (expires in 10 years; i.e. never)

	return; // We're returning here because we know Social Locker's hook is coming up next
}

/**
 * This function determines if the pmprosl_has_access cookie is set and verifies if the user should have access.
 */
add_filter( 'pmpro_has_membership_access_filter', 'pmprosl_pmpro_has_membership_access_filter', 10, 4 );
function pmprosl_pmpro_has_membership_access_filter( $hasaccess, $post, $user, $post_membership_levels ) {

	// If the flag is set
	if ( isset( $_COOKIE['pmprosl_has_access'] ) && $_COOKIE['pmprosl_has_access'] )
		// Loop through post levels
		foreach ( $post_membership_levels as $level )
			// If the cookie matches one of the post levels, give them access
			if ( ( int ) $_COOKIE['pmprosl_has_access'] == $level->id ) {
				$hasaccess = true;

				break;
			}

	return $hasaccess;
}