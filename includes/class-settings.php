<?php
/**
 * Glossary Settings Page
 *
 * @package PP_Glossary
 */

namespace PP_Glossary;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Settings
 */
class Settings {

	/**
	 * Option name for settings
	 */
	const OPTION_NAME = 'pp_glossary_settings';

	/**
	 * Default excluded tags for term highlighting
	 */
	const DEFAULT_EXCLUDED_TAGS = [ 'a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ];


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
			__( 'Display settings', 'pp-glossary' ),
			[ __CLASS__, 'render_display_section' ],
			'pp-glossary-settings'
		);

		add_settings_field(
			'glossary_page',
			__( 'Glossary page', 'pp-glossary' ),
			[ __CLASS__, 'render_glossary_page_field' ],
			'pp-glossary-settings',
			'pp_glossary_display_section'
		);

		add_settings_field(
			'excluded_tags',
			__( 'Excluded HTML tags', 'pp-glossary' ),
			[ __CLASS__, 'render_excluded_tags_field' ],
			'pp-glossary-settings',
			'pp_glossary_display_section'
		);

		add_settings_field(
			'excluded_post_types',
			__( 'Excluded post types', 'pp-glossary' ),
			[ __CLASS__, 'render_excluded_post_types_field' ],
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
	 * Render excluded tags field
	 */
	public static function render_excluded_tags_field(): void {
		$settings      = self::get_settings();
		$excluded_tags = isset( $settings['excluded_tags'] ) ? $settings['excluded_tags'] : self::DEFAULT_EXCLUDED_TAGS;
		$tags_string   = implode( ', ', $excluded_tags );

		printf(
			'<input type="text" name="%s[excluded_tags]" value="%s" class="regular-text" />',
			esc_attr( self::OPTION_NAME ),
			esc_attr( $tags_string )
		);

		echo '<p class="description">';
		echo esc_html__( 'HTML tags where glossary terms should not be highlighted (comma-separated, without angle brackets).', 'pp-glossary' );
		echo '<br>';
		printf(
			/* translators: %s: default tags */
			esc_html__( 'Default: %s', 'pp-glossary' ),
			esc_html( implode( ', ', self::DEFAULT_EXCLUDED_TAGS ) )
		);
		echo '</p>';
	}

	/**
	 * Render excluded post types field
	 */
	public static function render_excluded_post_types_field(): void {
		$settings            = self::get_settings();
		$excluded_post_types = isset( $settings['excluded_post_types'] ) ? $settings['excluded_post_types'] : [];

		// Get all public post types except the glossary itself.
		$post_types = get_post_types(
			[
				'public' => true,
			],
			'objects'
		);

		// Remove the glossary post type from the list.
		unset( $post_types['pp_glossary'] );

		if ( empty( $post_types ) ) {
			echo '<p>' . esc_html__( 'No public post types found.', 'pp-glossary' ) . '</p>';
			return;
		}

		echo '<fieldset>';
		foreach ( $post_types as $post_type ) {
			$checked = in_array( $post_type->name, $excluded_post_types, true ) ? 'checked' : '';
			printf(
				'<label><input type="checkbox" name="%s[excluded_post_types][]" value="%s" %s /> %s</label><br>',
				esc_attr( self::OPTION_NAME ),
				esc_attr( $post_type->name ),
				esc_attr( $checked ),
				esc_html( $post_type->labels->name )
			);
		}
		echo '</fieldset>';

		echo '<p class="description">';
		echo esc_html__( 'Glossary terms will not be highlighted in content from the selected post types.', 'pp-glossary' );
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

		if ( isset( $input['excluded_tags'] ) ) {
			$tags_string = sanitize_text_field( $input['excluded_tags'] );
			$tags_array  = array_map( 'trim', explode( ',', $tags_string ) );
			// Filter out empty values and sanitize each tag (lowercase, alphanumeric only).
			$sanitized['excluded_tags'] = array_values(
				array_filter(
					array_map(
						function ( $tag ) {
							return preg_replace( '/[^a-z0-9]/', '', strtolower( $tag ) );
						},
						$tags_array
					)
				)
			);
		}

		if ( isset( $input['excluded_post_types'] ) && is_array( $input['excluded_post_types'] ) ) {
			$sanitized['excluded_post_types'] = array_map( 'sanitize_key', $input['excluded_post_types'] );
		} else {
			// If no checkboxes are checked, save an empty array.
			$sanitized['excluded_post_types'] = [];
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
			'glossary_page'       => 0,
			'excluded_tags'       => self::DEFAULT_EXCLUDED_TAGS,
			'excluded_post_types' => [],
			'db_version'          => PP_GLOSSARY_VERSION,
		];

		$settings = get_option( self::OPTION_NAME, [] );

		return wp_parse_args( $settings, $defaults );
	}

	/**
	 * Update a single setting.
	 *
	 * @param string $key   The setting key.
	 * @param mixed  $value The setting value.
	 */
	public static function update_setting( string $key, $value ): void {
		$settings         = self::get_settings();
		$settings[ $key ] = $value;
		update_option( self::OPTION_NAME, $settings );
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

	/**
	 * Get excluded tags for term highlighting
	 *
	 * @return array<int, string> Array of excluded tag names.
	 */
	public static function get_excluded_tags(): array {
		$settings = self::get_settings();
		return isset( $settings['excluded_tags'] ) ? $settings['excluded_tags'] : self::DEFAULT_EXCLUDED_TAGS;
	}

	/**
	 * Get excluded post types for term highlighting
	 *
	 * @return array<int, string> Array of excluded post type names.
	 */
	public static function get_excluded_post_types(): array {
		$settings = self::get_settings();
		return isset( $settings['excluded_post_types'] ) ? $settings['excluded_post_types'] : [];
	}
}
