<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       mailto:joshuaslaven42@gmail.com
 * @since      1.0.0
 *
 * @package    Suitepresssso
 * @subpackage Suitepresssso/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Suitepresssso
 * @subpackage Suitepresssso/admin
 * @author     Joshua Slaven <joshuaslaven42@gmail.com>
 */
class MemberSuiteSSO_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	private $membersuite_sso_options;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	public function admin_menu() {
		add_options_page(
			'MemberSuite SSO',
			'MemberSuite SSO',
			'read',
			'membersuite-sso',
			array( $this, 'mssso_options_page')
		);
	}

	public function mssso_options_page() {
		$this->membersuite_sso_options = get_option( 'membersuite_sso_option_name' );
		?>
			<div class="wrap">
				<h2>MemberSuite SSO</h2>
				<p>MemberSuite API settings</p>
				<?php settings_errors(); ?>

				<form method="post" action="options.php">
					<?php
						settings_fields( 'membersuite_sso_option_group' );
						do_settings_sections( 'membersuite-sso-admin' );
						submit_button();
					?>
				</form>
			</div>
		<?php
	}

	public function membersuite_sso_page_init() {
		register_setting(
			'membersuite_sso_option_group', // option_group
			'membersuite_sso_option_name', // option_name
			array( $this, 'membersuite_sso_sanitize' ) // sanitize_callback
		);

		add_settings_section(
			'membersuite_sso_setting_section', // id
			'Settings', // title
			array( $this, 'membersuite_sso_section_info' ), // callback
			'membersuite-sso-admin' // page
		);

		add_settings_field(
			'accesskeyid_0', // id
			'AccessKeyId', // title
			array( $this, 'accesskeyid_0_callback' ), // callback
			'membersuite-sso-admin', // page
			'membersuite_sso_setting_section' // section
		);

		add_settings_field(
			'associationid_1', // id
			'AssociationId', // title
			array( $this, 'associationid_1_callback' ), // callback
			'membersuite-sso-admin', // page
			'membersuite_sso_setting_section' // section
		);

		add_settings_field(
			'secretaccesskey_2', // id
			'SecretAccessKey', // title
			array( $this, 'secretaccesskey_2_callback' ), // callback
			'membersuite-sso-admin', // page
			'membersuite_sso_setting_section' // section
		);

		add_settings_field(
			'signingcertificateid_3', // id
			'SigningcertificateId', // title
			array( $this, 'signingcertificateid_3_callback' ), // callback
			'membersuite-sso-admin', // page
			'membersuite_sso_setting_section' // section
		);

		add_settings_field(
			'singingcertificatexml_4', // id
			'Singing Certificate Xml', // title
			array( $this, 'singingcertificatexml_4_callback' ), // callback
			'membersuite-sso-admin', // page
			'membersuite_sso_setting_section' // section
		);

		add_settings_field(
			'portalurl_5', // id
			'PortalUrl', // title
			array( $this, 'portalurl_5_callback' ), // callback
			'membersuite-sso-admin', // page
			'membersuite_sso_setting_section' // section
		);

		add_settings_field(
			'wpusers_6', // id
			'WPUsers', // title
			array( $this, 'wpusers_6_callback' ), // callback
			'membersuite-sso-admin', // page
			'membersuite_sso_setting_section' // section
		);

        add_settings_field(
            'wmnnicoheretxtpassword', // id
            'wmnnicoheretxtpassword', // title
            array( $this, 'wmnnicoheretxtpassword_callback' ), // callback
            'membersuite-sso-admin', // page
            'membersuite_sso_setting_section' // section
        );
        add_settings_field(
            'wmnnicoherecseq', // id
            'wmnnicoherecseq', // title
            array( $this, 'wmnnicoherecseq_callback' ), // callback
            'membersuite-sso-admin', // page
            'membersuite_sso_setting_section' // section
        );

        add_settings_field(
            'sslnicoheretxtpassword', // id
            'sslnicoheretxtpassword', // title
            array( $this, 'sslnicoheretxtpassword_callback' ), // callback
            'membersuite-sso-admin', // page
            'membersuite_sso_setting_section' // section
        );
        add_settings_field(
            'sslnicoherecseq', // id
            'sslnicoherecseq', // title
            array( $this, 'sslnicoherecseq_callback' ), // callback
            'membersuite-sso-admin', // page
            'membersuite_sso_setting_section' // section
        );

		add_settings_field(
            'rpdcicoheretxtpassword', // id
            'rpdcicoheretxtpassword', // title
            array( $this, 'rpdcicoheretxtpassword_callback' ), // callback
            'membersuite-sso-admin', // page
            'membersuite_sso_setting_section' // section
        );
        add_settings_field(
            'rpdcicoherecseq', // id
            'rpdcicoherecseq', // title
            array( $this, 'rpdcicoherecseq_callback' ), // callback
            'membersuite-sso-admin', // page
            'membersuite_sso_setting_section' // section
        );
		

        add_settings_field(
            'icoherehidpassthroughreturn', // id
            'icoherehidpassthroughreturn', // title
            array( $this, 'icoherehidpassthroughreturn_callback' ), // callback
            'membersuite-sso-admin', // page
            'membersuite_sso_setting_section' // section
        );

        add_settings_field(
            'icoherehidpassthroughsource', // id
            'icoherehidpassthroughsource', // title
            array( $this, 'icoherehidpassthroughsource_callback' ), // callback
            'membersuite-sso-admin', // page
            'membersuite_sso_setting_section' // section
        );
	}

	public function membersuite_sso_sanitize($input) {
		$sanitary_values = array();
		if ( isset( $input['accesskeyid_0'] ) ) {
			$sanitary_values['accesskeyid_0'] = sanitize_text_field( $input['accesskeyid_0'] );
		}

		if ( isset( $input['associationid_1'] ) ) {
			$sanitary_values['associationid_1'] = sanitize_text_field( $input['associationid_1'] );
		}

		if ( isset( $input['secretaccesskey_2'] ) ) {
			$sanitary_values['secretaccesskey_2'] = sanitize_text_field( $input['secretaccesskey_2'] );
		}

		if ( isset( $input['signingcertificateid_3'] ) ) {
			$sanitary_values['signingcertificateid_3'] = sanitize_text_field( $input['signingcertificateid_3'] );
		}

		if ( isset( $input['singingcertificatexml_4'] ) ) {
			$sanitary_values['singingcertificatexml_4'] = esc_textarea( $input['singingcertificatexml_4'] );
		}

		if ( isset( $input['portalurl_5'] ) ) {
			$sanitary_values['portalurl_5'] = sanitize_text_field( $input['portalurl_5'] );
		}

		if ( isset( $input['wpusers_6'] ) ) {
			$sanitary_values['wpusers_6'] = sanitize_text_field( $input['wpusers_6'] );
		}

        if ( isset( $input['wmnnicoheretxtpassword'] ) ) {
            $sanitary_values['wmnnicoheretxtpassword'] = sanitize_text_field( $input['wmnnicoheretxtpassword'] );
        }
        if ( isset( $input['wmnnicoherecseq'] ) ) {
            $sanitary_values['wmnnicoherecseq'] = sanitize_text_field( $input['wmnnicoherecseq'] );
        }

        if ( isset( $input['sslnicoheretxtpassword'] ) ) {
            $sanitary_values['sslnicoheretxtpassword'] = sanitize_text_field( $input['sslnicoheretxtpassword'] );
        }
        if ( isset( $input['sslnicoherecseq'] ) ) {
            $sanitary_values['sslnicoherecseq'] = sanitize_text_field( $input['sslnicoherecseq'] );
        }

        if ( isset( $input['rpdcicoheretxtpassword'] ) ) {
            $sanitary_values['rpdcicoheretxtpassword'] = sanitize_text_field( $input['rpdcicoheretxtpassword'] );
        }
        if ( isset( $input['rpdcicoherecseq'] ) ) {
            $sanitary_values['rpdcicoherecseq'] = sanitize_text_field( $input['rpdcicoherecseq'] );
        }
		

        if ( isset( $input['icoherehidpassthroughreturn'] ) ) {
            $sanitary_values['icoherehidpassthroughreturn'] = sanitize_text_field( $input['icoherehidpassthroughreturn'] );
        }

        if ( isset( $input['icoherehidpassthroughsource'] ) ) {
            $sanitary_values['icoherehidpassthroughsource'] = sanitize_text_field( $input['icoherehidpassthroughsource'] );
        }

		return $sanitary_values;
	}

	public function membersuite_sso_section_info() {
		
	}

	public function accesskeyid_0_callback() {
		printf(
			'<input class="regular-text" type="text" name="membersuite_sso_option_name[accesskeyid_0]" id="accesskeyid_0" value="%s">',
			isset( $this->membersuite_sso_options['accesskeyid_0'] ) ? esc_attr( $this->membersuite_sso_options['accesskeyid_0']) : ''
		);
	}

	public function associationid_1_callback() {
		printf(
			'<input class="regular-text" type="text" name="membersuite_sso_option_name[associationid_1]" id="associationid_1" value="%s">',
			isset( $this->membersuite_sso_options['associationid_1'] ) ? esc_attr( $this->membersuite_sso_options['associationid_1']) : ''
		);
	}

	public function secretaccesskey_2_callback() {
		printf(
			'<input class="regular-text" type="text" name="membersuite_sso_option_name[secretaccesskey_2]" id="secretaccesskey_2" value="%s">',
			isset( $this->membersuite_sso_options['secretaccesskey_2'] ) ? esc_attr( $this->membersuite_sso_options['secretaccesskey_2']) : ''
		);
	}

	public function signingcertificateid_3_callback() {
		printf(
			'<input class="regular-text" type="text" name="membersuite_sso_option_name[signingcertificateid_3]" id="signingcertificateid_3" value="%s">',
			isset( $this->membersuite_sso_options['signingcertificateid_3'] ) ? esc_attr( $this->membersuite_sso_options['signingcertificateid_3']) : ''
		);
	}

	public function singingcertificatexml_4_callback() {
		printf(
			'<textarea class="large-text" rows="10" name="membersuite_sso_option_name[singingcertificatexml_4]" id="singingcertificatexml_4">%s</textarea>',
			isset( $this->membersuite_sso_options['singingcertificatexml_4'] ) ? $this->membersuite_sso_options['singingcertificatexml_4'] : ''
		);
	}

	public function portalurl_5_callback() {
		printf(
			'<input class="regular-text" type="text" name="membersuite_sso_option_name[portalurl_5]" id="portalurl_5" value="%s">',
			isset( $this->membersuite_sso_options['portalurl_5'] ) ? esc_attr( $this->membersuite_sso_options['portalurl_5']) : ''
		);
	}

	public function wpusers_6_callback() {
		printf(
			'<input class="regular-text" type="checkbox" name="membersuite_sso_option_name[wpusers_6]" id="wpusers_6" %s>' . 
			'<p>If this box is checked, the plugin will not authenticate users with their Member Suite credentials. It WILL ' .
			'create MS portal users at SSO time. This is useful if you want WordPress to be the source of authority for user accounts.</p>' .
			'<p>NOTE: Email address is assumed to be unique to correctly match wordpress accounts and MS portal accounts. If an email address is not found a new account will be created.</p>',
			isset( $this->membersuite_sso_options['wpusers_6'] ) ? 'checked' : ''
		);
	}

    public function wmnnicoheretxtpassword_callback() {
    printf(
        '<input class="regular-text" type="text" name="membersuite_sso_option_name[wmnnicoheretxtpassword]" id="wmnnicoheretxtpassword" value="%s">',
        isset( $this->membersuite_sso_options['wmnnicoheretxtpassword'] ) ? esc_attr( $this->membersuite_sso_options['wmnnicoheretxtpassword']) : ''
        );
    }
    public function wmnnicoherecseq_callback() {
        printf(
            '<input class="regular-text" type="text" name="membersuite_sso_option_name[wmnnicoherecseq]" id="wmnnicoherecseq" value="%s">',
            isset( $this->membersuite_sso_options['wmnnicoherecseq'] ) ? esc_attr( $this->membersuite_sso_options['wmnnicoherecseq']) : ''
        );
    }

    public function sslnicoheretxtpassword_callback() {
        printf(
            '<input class="regular-text" type="text" name="membersuite_sso_option_name[sslnicoheretxtpassword]" id="sslnicoheretxtpassword" value="%s">',
            isset( $this->membersuite_sso_options['sslnicoheretxtpassword'] ) ? esc_attr( $this->membersuite_sso_options['sslnicoheretxtpassword']) : ''
        );
    }
    public function sslnicoherecseq_callback() {
        printf(
            '<input class="regular-text" type="text" name="membersuite_sso_option_name[sslnicoherecseq]" id="sslnicoherecseq" value="%s">',
            isset( $this->membersuite_sso_options['sslnicoherecseq'] ) ? esc_attr( $this->membersuite_sso_options['sslnicoherecseq']) : ''
        );
    }

	public function rpdcicoheretxtpassword_callback() {
        printf(
            '<input class="regular-text" type="text" name="membersuite_sso_option_name[rpdcicoheretxtpassword]" id="rpdcicoheretxtpassword" value="%s">',
            isset( $this->membersuite_sso_options['rpdcicoheretxtpassword'] ) ? esc_attr( $this->membersuite_sso_options['rpdcicoheretxtpassword']) : ''
        );
    }
    public function rpdcicoherecseq_callback() {
        printf(
            '<input class="regular-text" type="text" name="membersuite_sso_option_name[rpdcicoherecseq]" id="rpdcicoherecseq" value="%s">',
            isset( $this->membersuite_sso_options['rpdcicoherecseq'] ) ? esc_attr( $this->membersuite_sso_options['rpdcicoherecseq']) : ''
        );
    }
	

    public function icoherehidpassthroughreturn_callback() {
        printf(
            '<input class="regular-text" type="text" name="membersuite_sso_option_name[icoherehidpassthroughreturn]" id="icoherehidpassthroughreturn" value="%s">',
            isset( $this->membersuite_sso_options['icoherehidpassthroughreturn'] ) ? esc_attr( $this->membersuite_sso_options['icoherehidpassthroughreturn']) : ''
        );
    }

    public function icoherehidpassthroughsource_callback() {
        printf(
            '<input class="regular-text" type="text" name="membersuite_sso_option_name[icoherehidpassthroughsource]" id="icoherehidpassthroughsource" value="%s">',
            isset( $this->membersuite_sso_options['icoherehidpassthroughsource'] ) ? esc_attr( $this->membersuite_sso_options['icoherehidpassthroughsource']) : ''
        );
    }

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Suitepresssso_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Suitepresssso_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/suitepresssso-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Suitepresssso_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Suitepresssso_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/suitepresssso-admin.js', array( 'jquery' ), $this->version, false );

	}

	public static function get_instance() {

		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new self('membersuite-sso', '1.0');
		}

		return $instance;
	}

}

