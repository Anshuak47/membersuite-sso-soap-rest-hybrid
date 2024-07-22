<?php
/**
 * Shortcodes for use within posts and other shortcode-aware areas.
 *
 * @package    MemberSuite Single Sign-On
 * @subpackage Includes
 */

add_action( 'init', 'mssso_register_shortcodes' );

/**
 * Registers shortcodes.
 *
 * @since  1.0.0
 * @access public
 * @return void
 */
function mssso_register_shortcodes() {

	add_shortcode( 'mssso-login', 'mssso_login' );
}

/* Membersuite Login Form as a shortcode */

function mssso_login( $attr, $content = null ) {
	$output = '<form method="post" action="">';
		
		$output .= '<div class="form-group">';
		$output .= '<input type="email" name="portalusername" id="portalusername" class="form-control" placeholder="Email address" required>';
        $output .= '</div>';

        $output .= '<div class="form-group">';
		$output .= '<input type="password" name="portalpassword" id="portalpassword" class="form-control" placeholder="Password" required>';

		$output .= '<input type="submit" name="submit" value="Login" class="btn btn-warning">';
        $output .= '</div>';
	$output .= '</form>';

	$output .= '<a href="https://domain.com/profile/ForgotPassword.aspx?returnURL=https://domain.com/password-reset-request">Forgot account?</a>';

	return $output;
}
