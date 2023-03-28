<?php
/**
 * Plugin Name: Learning Forward Single Sign-On New Plugin
 * Plugin URI:  https://learningforward.org
 * Description: New plugin for MemberSuite single sign-on capabilities for WordPress
 * Version:     1.0.0
 * Author:      Learning Forward
 * Author URI:  https://learningforward.org
 * Text Domain: membersuite
 *
 * @package   MemberSuite Single Sign-On
 * @version   1.0.0
 */

/**
 * Setting up the class.
 *
 * @since  1.0.0
 * @access public
 */
class MemberSuiteSSO {

	/**
	 * Minimum required PHP version.
	 *
	 * @since  2.0.0
	 * @access public
	 * @var    string
	 */
	protected $php_version = '7.0.0';

	/**
	 * Plugin directory path.
	 *
	 * @since  2.0.0
	 * @access public
	 * @var    string
	 */
	public $dir = '';

	/**
	 * Plugin directory URI.
	 *
	 * @since  2.0.0
	 * @access public
	 * @var    string
	 */
	public $uri = '';

    /**
     * MemberSuiteSSO constructor.
     */
    public function __construct()
    {

    }


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
			$instance->setup();
			$instance->includes();
			$instance->setup_actions();
		}

		return $instance;
	}

	/**
	 * Sets up globals.
	 *
	 * @since  1.0.0
	 * @access private
	 * @return void
	 */
	protected function setup() {

		// Main plugin directory path and URI.
		$this->dir = trailingslashit( plugin_dir_path( __FILE__ ) );
		$this->uri = trailingslashit( plugin_dir_url(  __FILE__ ) );
	}

	/**
	 * Loads files needed by the plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 * @return void
	 */
	protected function includes() {

		// Check if we meet the minimum PHP version.
		if ( version_compare( PHP_VERSION, $this->php_version, '<' ) ) {

			// Add admin notice.
			add_action( 'admin_notices', array( $this, 'php_admin_notice' ) );

			// Bail.
			return;
		}

		require_once( $this->dir . 'ms_sdk/src/MemberSuite.php' );
		require_once( $this->dir . 'ms_sdk/SSOWithSDK/ConciergeApiHelper.php' );

		require_once( $this->dir . 'inc/class-userconfig.php' );
		require_once( $this->dir . 'inc/class-messages.php' );
		require_once( $this->dir . 'inc/functions.php' );
		require_once( $this->dir . 'inc/functions-member.php' );
		require_once( $this->dir . 'inc/functions-shortcodes.php' );
        require_once( $this->dir . 'inc/memberSuiteUserData.php' );

		if ( is_admin() ) {
			require_once( $this->dir . 'admin/class-membersuite-sso-admin.php' );
			require_once( $this->dir . 'admin/functions-settings.php' );
		}
	}

	/**
	 * Sets up main plugin actions and filters.
	 *
	 * @since  1.0.0
	 * @access private
	 * @return void
	 */
	protected function setup_actions() {

		// Internationalize the text strings used.
		add_action( 'plugins_loaded', array( $this, 'i18n' ), 2 );

		// Register activation hook.
		register_activation_hook( __FILE__, array( $this, 'activation' ) );
	}

	/**
	 * Loads the translation files.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function i18n() {

		load_plugin_textdomain( 'membersuite', false, trailingslashit( dirname( plugin_basename( __FILE__ ) ) ) . 'lang' );
	}

	/**
	 * Method that runs only when the plugin is activated.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function activation() {

		// Check PHP version requirements.
		if ( version_compare( PHP_VERSION, $this->php_version, '<' ) ) {

			// Make sure the plugin is deactivated.
			deactivate_plugins( plugin_basename( __FILE__ ) );

			// Add an error message and die.
			wp_die( $this->get_min_php_message() );
		}

	}

	/**
	 * Returns a message noting the minimum version of PHP required.
	 *
	 * @since  1.0.0
	 * @access private
	 * @return void
	 */
	protected function get_min_php_message() {

		return sprintf(
			__( 'MemberSuite SSO requires PHP version %1$s. You are running version %2$s. Please upgrade and try again.', 'membersuite' ),
			$this->php_version,
			PHP_VERSION
		);
	}

	/**
	 * Outputs the admin notice that the user needs to upgrade their PHP version. It also
	 * auto-deactivates the plugin.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function php_admin_notice() {

		// Output notice.
		printf(
			'<div class="notice notice-error is-dismissible"><p><strong>%s</strong></p></div>',
			esc_html( $this->get_min_php_message() )
		);

		// Make sure the plugin is deactivated.
		deactivate_plugins( plugin_basename( __FILE__ ) );
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
function membersuite_sso_plugin() {
	return MemberSuiteSSO::get_instance();
}

membersuite_sso_plugin();

function membersuite_sso_admin() {
	return MemberSuiteSSO_Admin::get_instance();
}
