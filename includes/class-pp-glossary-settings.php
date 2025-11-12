<?php
/**
 * Glossary Settings Page
 *
 * @package PP_Glossary
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class PP_Glossary_Settings
 */
class PP_Glossary_Settings {

	/**
	 * Option name for settings
	 */
	const OPTION_NAME = 'pp_glossary_settings';

	/**
	 * Initialize the settings
	 */
	public static function init(): void {
		add_action( 'admin_menu', [ __CLASS__, 'add_settings_page' ] );
		add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
	}

	/**
	 * Add settings page to Glossary menu
	 */
	public static function add_settings_page(): void {
		add_submenu_page(
			'edit.php?post_type=pp_glossary',
			__( 'Glossary Settings', 'pp-glossary' ),
			__( 'Settings', 'pp-glossary' ),
			'manage_options',
			'pp-glossary-settings',
			[ __CLASS__, 'render_settings_page' ]
		);
	}

	/**
	 * Register settings
	 */
	public static function register_settings(): void {
		register_setting(
			'pp_glossary_settings_group',
			self::OPTION_NAME,
			[
				'sanitize_callback' => [ __CLASS__, 'sanitize_settings' ],
			]
		);

		add_settings_section(
			'pp_glossary_display_section',
			__( 'Display Settings', 'pp-glossary' ),
			[ __CLASS__, 'render_display_section' ],
			'pp-glossary-settings'
		);

		add_settings_field(
			'glossary_page',
			__( 'Glossary Page', 'pp-glossary' ),
			[ __CLASS__, 'render_glossary_page_field' ],
			'pp-glossary-settings',
			'pp_glossary_display_section'
		);
	}

	/**
	 * Render settings page
	 */
	public static function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Check if settings were saved.
		if ( isset( $_GET['settings-updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing -- Nonce check not needed here.
			add_settings_error(
				'pp_glossary_messages',
				'pp_glossary_message',
				esc_html__( 'Settings saved.', 'pp-glossary' ),
				'updated'
			);
		}

		settings_errors( 'pp_glossary_messages' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'pp_glossary_settings_group' );
				do_settings_sections( 'pp-glossary-settings' );
				submit_button( __( 'Save Settings', 'pp-glossary' ) );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render display section description
	 */
	public static function render_display_section(): void {
		echo '<p>' . esc_html__( 'Configure how and where the glossary is displayed on your site.', 'pp-glossary' ) . '</p>';
	}

	/**
	 * Render glossary page field
	 */
	public static function render_glossary_page_field(): void {
		$settings = self::get_settings();
		$page_id  = isset( $settings['glossary_page'] ) ? absint( $settings['glossary_page'] ) : 0;

		wp_dropdown_pages(
			[
				'name'              => esc_attr( self::OPTION_NAME ) . '[glossary_page]',
				'selected'          => esc_attr( (string) $page_id ),
				'show_option_none'  => esc_html__( '— Select a Page —', 'pp-glossary' ),
				'option_none_value' => '0',
			]
		);

		echo '<p class="description">';
		echo esc_html__( 'Select the page where the glossary block is located. This page will be used for "Read more" links in popovers.', 'pp-glossary' );
		echo '</p>';
	}

	/**
	 * Sanitize settings
	 *
	 * @param array<string, mixed> $input Settings input.
	 * @return array<string, mixed> Sanitized settings.
	 */
	public static function sanitize_settings( $input ): array {
		$sanitized = [];

		if ( isset( $input['glossary_page'] ) ) {
			$sanitized['glossary_page'] = absint( $input['glossary_page'] );
		}

		return $sanitized;
	}

	/**
	 * Get settings
	 *
	 * @return array<string, mixed> Settings.
	 */
	public static function get_settings(): array {
		$defaults = [
			'glossary_page' => 0,
		];

		$settings = get_option( self::OPTION_NAME, [] );

		return wp_parse_args( $settings, $defaults );
	}

	/**
	 * Get glossary page ID
	 *
	 * @return int Page ID or 0 if not set.
	 */
	public static function get_glossary_page_id(): int {
		$settings = self::get_settings();
		return absint( $settings['glossary_page'] );
	}

	/**
	 * Get glossary page URL
	 *
	 * @return string Page URL or empty string if not set.
	 */
	public static function get_glossary_page_url(): string {
		$page_id = self::get_glossary_page_id();

		if ( ! $page_id ) {
			return '';
		}

		$permalink = get_permalink( $page_id );
		return $permalink ? $permalink : '';
	}
}
