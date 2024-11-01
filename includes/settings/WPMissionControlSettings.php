<?php
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class WPMissionControlSettings {
	private $options;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'wp_mission_control_settings_add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'wp_mission_control_settings_page_init' ) );
	}

	public function wp_mission_control_settings_add_plugin_page() {
		add_options_page(
			__( 'WPMissionControl Settings', 'wpmissioncontrol' ),// page_title
			__( 'WPMissionControl', 'wpmissioncontrol' ),// menu_title
			'manage_options', // capability
			'wp-mission-control-settings', // menu_slug
			array( $this, 'wp_mission_control_settings_create_admin_page' ) // function
		);
	}

	public function wp_mission_control_settings_create_admin_page() {
		$this->options = get_option( 'wpmc_options' );
		?>
		<div class="wrap">
			<img src="<?php echo esc_url( WPMC_STATIC_ASSETS_URL ); ?>/logo.png" alt="WPMissionControl Logo">
			<h2><?php esc_html_e( 'Remote Monitoring System', 'wpmissioncontrol' ); ?></h2>
			<br>
			<form method="post" action="options.php">
				<?php
					settings_fields( 'wp_mission_control_settings_option_group' );
					do_settings_sections( 'wp-mission-control-settings-admin' );
					submit_button();
					do_settings_sections( 'wp-mission-control-info-admin' );
				?>
			</form>
		</div>
	<?php }

	public function wp_mission_control_settings_page_init() {
		register_setting(
			'wp_mission_control_settings_option_group',
			'wpmc_options',
			array( $this, 'wp_mission_control_settings_sanitize' )
		);

		add_settings_section(
			'wp_mission_control_settings_setting_section',
			__( 'Settings', 'wpmissioncontrol' ),
			array( $this, 'wp_mission_control_settings_section_info' ),
			'wp-mission-control-settings-admin'
		);

		add_settings_field(
			'wp_mission_control_api_key',
			__( 'API Key', 'wpmissioncontrol' ),
			array( $this, 'api_key_callback' ),
			'wp-mission-control-settings-admin',
			'wp_mission_control_settings_setting_section'
		);

		add_settings_section(
			'wp_mission_control_settings_info_section',
			__( 'Information', 'wpmissioncontrol' ),
			array( $this, 'wp_mission_control_info_section' ),
			'wp-mission-control-info-admin'
		);
	}

	public function wp_mission_control_settings_sanitize( $input ) {
		$sanitary_values = array();
		if ( isset( $input['api_key'] ) ) {
			$sanitary_values['api_key'] = sanitize_text_field( $input['api_key'] );
		}
		return $sanitary_values;
	}

	public function wp_mission_control_settings_section_info() {
		// echo '<h1>INFO</h1>';
	}

	public function api_key_callback() {
		printf(
			'<input class="regular-text" type="text" name="wpmc_options[api_key]" id="api_key" value="%s">',
			isset( $this->options['api_key'] ) ? esc_attr( $this->options['api_key'] ) : ''
		);
		?>
		<p>You can obtain your API Key in the WPMissionControl dashboard at <a href="https://wpmissioncontrol.com" target="_blank">wpmissioncontrol.com</a></p><?php
	}

	public function wp_mission_control_info_section() {
		?>
		<p>WPMissionControl is a monitoring tool supervising your Wordpress website 24/7 to ensure it's safety, health and performance.</p>
		<small>Contact the support team at <a href="mailto:support@wpmissioncontrol.com">support@wpmissioncontrol.com</a> in case of any questions.</small>
		<?php
	}
	
}