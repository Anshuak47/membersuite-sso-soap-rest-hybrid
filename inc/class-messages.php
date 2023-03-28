<?php

/* This file contains the code to display error messages or notices for SSO operations */

class MemberSuiteSSO_Messages {

	public $message = '';

	/**
	 * Returns the instance.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return object
	 */
	public static function get_instance() {

		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new self;
		}

		return $instance;
	}

	/**
	 * Constructor method.
	 *
	 * @since  1.0.0
	 * @access private
	 * @return void
	 */
	public function __construct() {}

	public function get_message()
    {
		parse_str( $_SERVER['QUERY_STRING'], $query_string );

		if ( isset($query_string['error']) ) {
			if ( $query_string['error'] == 'credential-error' ) {
				$this->message = 'There was an error with your login credentials. Please reset password <a href="https://lf.ps.membersuite.com/profile/ForgotPassword.aspx?returnURL=https://learningforward.org/password-reset-request" style="color:#ffffff !important;">here.</a>';

			} elseif ($query_string['error'] == 'certificate-error' ) {
				$this->message = 'Signing certificate does not exist.';
			}
		}

		return $this->message;
	}
}

/**
 * Gets the instance of the `MemberSuiteSSO` class.  This function is useful for quickly grabbing data
 * used throughout the plugin.
 *
 * @since  1.0.0
 * @access public
 * @return object
 */
function membersuite_sso_messages() {
	return MemberSuiteSSO_Messages::get_instance();
}

membersuite_sso_messages();
