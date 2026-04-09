<?php
/**
 * Meta Boxes for Glossary
 *
 * @package Your_Glossary
 */

namespace Your_Glossary;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Meta_Boxes
 */
class Meta_Boxes {

	/**
	 * Initialize the meta boxes
	 */
	public static function init(): void {
		add_action( 'add_meta_boxes', [ __CLASS__, 'add_meta_boxes' ] );
		add_action( 'save_post_your_glossary', [ __CLASS__, 'save_meta_boxes' ], 10 );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_scripts' ] );
	}

	/**
	 * Add meta boxes for glossary entries
	 */
	public static function add_meta_boxes(): void {
		add_meta_box(
			'your_glossary_details',
			__( 'Glossary entry', 'your-glossary' ),
			[ __CLASS__, 'render_meta_box' ],
			'your_glossary',
			'normal',
			'high'
		);
	}

	/**
	 * Get glossary entry data with defaults.
	 *
	 * @param int $post_id The post ID.
	 * @return array<string, mixed> The glossary data.
	 */
	public static function get_entry_data( int $post_id ): array {
		$defaults = [
			'short_description' => '',
			'long_description'  => '',
			'synonyms'          => [],
			'case_sensitive'    => false,
			'disable_autolink'  => false,
		];

		$data = get_post_meta( $post_id, '_your_glossary_data', true );

		if ( ! is_array( $data ) ) {
			return $defaults;
		}

		return wp_parse_args( $data, $defaults );
	}

	/**
	 * Render the meta box content
	 *
	 * @param \WP_Post $post The post object.
	 */
	public static function render_meta_box( $post ): void {
		// Add nonce for security.
		wp_nonce_field( 'your_glossary_meta_box', 'your_glossary_meta_box_nonce' );

		// Get current values.
		$data              = self::get_entry_data( $post->ID );
		$short_description = $data['short_description'];
		$long_description  = $data['long_description'];
		$synonyms          = $data['synonyms'];
		$case_sensitive    = $data['case_sensitive'];
		$disable_autolink  = $data['disable_autolink'];
		?>
		<div class="your-glossary-meta-box">
			<p>
				<label for="your_glossary_short_description">
					<strong><?php esc_html_e( 'Short description', 'your-glossary' ); ?></strong>
					<span class="required">*</span>
				</label>
				<br>
				<span class="description">
					<?php esc_html_e( 'A brief definition that will appear in the popover (recommended: 1-2 sentences).', 'your-glossary' ); ?>
				</span>
			</p>
			<p>
				<textarea
					id="your_glossary_short_description"
					name="your_glossary_short_description"
					rows="3"
					class="large-text"
					required><?php echo esc_textarea( $short_description ); ?></textarea>
			</p>

			<p>
				<label for="your_glossary_long_description">
					<strong><?php esc_html_e( 'Long description', 'your-glossary' ); ?></strong>
				</label>
				<br>
				<span class="description">
					<?php esc_html_e( 'A detailed explanation that will appear on the glossary page.', 'your-glossary' ); ?>
				</span>
			</p>
			<p>
				<?php
				wp_editor(
					$long_description,
					'your_glossary_long_description',
					[
						'textarea_name' => 'your_glossary_long_description',
						'textarea_rows' => 10,
						'media_buttons' => true,
						'teeny'         => false,
						'tinymce'       => true,
						'quicktags'     => true,
					]
				);
				?>
			</p>

			<p>
				<label>
					<strong><?php esc_html_e( 'Synonyms', 'your-glossary' ); ?></strong>
				</label>
				<br>
				<span class="description">
					<?php esc_html_e( 'Add alternative terms or phrases that should also trigger this glossary entry.', 'your-glossary' ); ?>
				</span>
			</p>
			<div id="your-glossary-synonyms-container">
				<?php if ( ! empty( $synonyms ) ) : ?>
					<?php foreach ( $synonyms as $index => $synonym ) : ?>
						<div class="your-glossary-synonym-row" style="margin-bottom: 10px;display: flex;gap: 10px;">
							<input
								type="text"
								name="your_glossary_synonyms[]"
								value="<?php echo esc_attr( $synonym ); ?>"
								class="regular-text"
								placeholder="<?php esc_attr_e( 'e.g., CLS, layout shift', 'your-glossary' ); ?>"
							>
							<button type="button" class="button your-glossary-remove-synonym">
								<?php esc_html_e( 'Remove', 'your-glossary' ); ?>
							</button>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
			<p>
				<button
					type="button"
					id="your-glossary-add-synonym"
					class="button"
					data-placeholder="<?php esc_attr_e( 'e.g., CLS, layout shift', 'your-glossary' ); ?>"
					data-remove-text="<?php esc_attr_e( 'Remove', 'your-glossary' ); ?>"
				>
					<?php esc_html_e( 'Add synonym', 'your-glossary' ); ?>
				</button>
			</p>
			<hr>
			<p>
				<label>
					<input
						type="checkbox"
						name="your_glossary_case_sensitive"
						value="1"
						<?php checked( $case_sensitive ); ?>
					>
					<strong><?php esc_html_e( 'Case sensitive', 'your-glossary' ); ?></strong>
				</label>
				<br>
				<span class="description">
					<?php esc_html_e( 'Only match terms when the case matches exactly.', 'your-glossary' ); ?>
				</span>
			</p>
			<p>
				<label>
					<input
						type="checkbox"
						name="your_glossary_disable_autolink"
						value="1"
						<?php checked( $disable_autolink ); ?>
					>
					<strong><?php esc_html_e( 'Disable auto-linking', 'your-glossary' ); ?></strong>
				</label>
				<br>
				<span class="description">
					<?php esc_html_e( 'This term will appear in the glossary but will not be automatically linked in content.', 'your-glossary' ); ?>
				</span>
			</p>
		</div>
		<?php
	}

	/**
	 * Save meta box data
	 *
	 * @param int $post_id Post ID.
	 */
	public static function save_meta_boxes( $post_id ): void {
		// Check nonce.
		if ( ! isset( $_POST['your_glossary_meta_box_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['your_glossary_meta_box_nonce'] ) ), 'your_glossary_meta_box' ) ) {
			return;
		}

		// Check autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Build data array.
		$data = [
			'case_sensitive'    => isset( $_POST['your_glossary_case_sensitive'] ),
			'disable_autolink'  => isset( $_POST['your_glossary_disable_autolink'] ),
			'short_description' => '',
			'long_description'  => '',
			'synonyms'          => [],
		];

		// Sanitize short description.
		if ( isset( $_POST['your_glossary_short_description'] ) ) {
			$data['short_description'] = sanitize_textarea_field( wp_unslash( $_POST['your_glossary_short_description'] ) );
		}

		// Sanitize long description.
		if ( isset( $_POST['your_glossary_long_description'] ) ) {
			$data['long_description'] = wp_kses_post( wp_unslash( $_POST['your_glossary_long_description'] ) );
		}

		// Sanitize synonyms.
		if ( isset( $_POST['your_glossary_synonyms'] ) && is_array( $_POST['your_glossary_synonyms'] ) ) {
			foreach ( wp_unslash( $_POST['your_glossary_synonyms'] ) as $synonym ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitization handled below.
				$synonym = sanitize_text_field( $synonym );
				if ( ! empty( $synonym ) ) {
					$data['synonyms'][] = $synonym;
				}
			}
		}

		update_post_meta( $post_id, '_your_glossary_data', $data );
	}

	/**
	 * Enqueue admin scripts for synonyms functionality
	 *
	 * @param string $hook The current admin page hook.
	 */
	public static function enqueue_admin_scripts( $hook ): void {
		// Only load on post edit screens for glossary entries.
		if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'your_glossary' !== $screen->post_type ) {
			return;
		}

		// Enqueue admin script for synonyms management.
		wp_enqueue_script(
			'your-glossary-admin',
			YOUR_GLOSSARY_PLUGIN_URL . 'assets/js/admin.js',
			[],
			YOUR_GLOSSARY_VERSION,
			true
		);
	}
}
