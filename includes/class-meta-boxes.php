<?php
/**
 * Meta Boxes for Glossary
 *
 * @package PP_Glossary
 */

namespace PP_Glossary;

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
		add_action( 'save_post_pp_glossary', [ __CLASS__, 'save_meta_boxes' ], 10 );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_scripts' ] );
	}

	/**
	 * Add meta boxes for glossary entries
	 */
	public static function add_meta_boxes(): void {
		add_meta_box(
			'pp_glossary_details',
			__( 'Glossary Entry Details', 'pp-glossary' ),
			[ __CLASS__, 'render_meta_box' ],
			'pp_glossary',
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

		$data = get_post_meta( $post_id, '_pp_glossary_data', true );

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
		wp_nonce_field( 'pp_glossary_meta_box', 'pp_glossary_meta_box_nonce' );

		// Get current values.
		$data              = self::get_entry_data( $post->ID );
		$short_description = $data['short_description'];
		$long_description  = $data['long_description'];
		$synonyms          = $data['synonyms'];
		$case_sensitive    = $data['case_sensitive'];
		$disable_autolink  = $data['disable_autolink'];

		if ( ! is_array( $synonyms ) ) {
			$synonyms = [];
		}
		?>
		<div class="pp-glossary-meta-box">
			<p>
				<label>
					<input
						type="checkbox"
						name="pp_glossary_case_sensitive"
						value="1"
						<?php checked( $case_sensitive ); ?>
					>
					<strong><?php esc_html_e( 'Case sensitive', 'pp-glossary' ); ?></strong>
				</label>
				<br>
				<span class="description">
					<?php esc_html_e( 'Only match terms when the case matches exactly.', 'pp-glossary' ); ?>
				</span>
			</p>
			<p>
				<label>
					<input
						type="checkbox"
						name="pp_glossary_disable_autolink"
						value="1"
						<?php checked( $disable_autolink ); ?>
					>
					<strong><?php esc_html_e( 'Disable auto-linking', 'pp-glossary' ); ?></strong>
				</label>
				<br>
				<span class="description">
					<?php esc_html_e( 'This term will appear in the glossary but will not be automatically linked in content.', 'pp-glossary' ); ?>
				</span>
			</p>
			<hr>
			<p>
				<label for="pp_glossary_short_description">
					<strong><?php esc_html_e( 'Short Description', 'pp-glossary' ); ?></strong>
					<span class="required">*</span>
				</label>
				<br>
				<span class="description">
					<?php esc_html_e( 'A brief definition that will appear in the popover (recommended: 1-2 sentences).', 'pp-glossary' ); ?>
				</span>
			</p>
			<p>
				<textarea
					id="pp_glossary_short_description"
					name="pp_glossary_short_description"
					rows="3"
					class="large-text"
					required><?php echo esc_textarea( $short_description ); ?></textarea>
			</p>

			<p>
				<label for="pp_glossary_long_description">
					<strong><?php esc_html_e( 'Long Description', 'pp-glossary' ); ?></strong>
				</label>
				<br>
				<span class="description">
					<?php esc_html_e( 'A detailed explanation that will appear on the glossary page.', 'pp-glossary' ); ?>
				</span>
			</p>
			<p>
				<?php
				wp_editor(
					$long_description,
					'pp_glossary_long_description',
					[
						'textarea_name' => 'pp_glossary_long_description',
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
					<strong><?php esc_html_e( 'Synonyms', 'pp-glossary' ); ?></strong>
				</label>
				<br>
				<span class="description">
					<?php esc_html_e( 'Add alternative terms or phrases that should also trigger this glossary entry.', 'pp-glossary' ); ?>
				</span>
			</p>
			<div id="pp-glossary-synonyms-container">
				<?php if ( ! empty( $synonyms ) ) : ?>
					<?php foreach ( $synonyms as $index => $synonym ) : ?>
						<div class="pp-glossary-synonym-row" style="margin-bottom: 10px;display: flex;gap: 10px;">
							<input
								type="text"
								name="pp_glossary_synonyms[]"
								value="<?php echo esc_attr( $synonym ); ?>"
								class="regular-text"
								placeholder="<?php esc_attr_e( 'e.g., CLS, layout shift', 'pp-glossary' ); ?>"
							>
							<button type="button" class="button pp-glossary-remove-synonym">
								<?php esc_html_e( 'Remove', 'pp-glossary' ); ?>
							</button>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
			<p>
				<button type="button" id="pp-glossary-add-synonym" class="button">
					<?php esc_html_e( 'Add Synonym', 'pp-glossary' ); ?>
				</button>
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
		if ( ! isset( $_POST['pp_glossary_meta_box_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pp_glossary_meta_box_nonce'] ) ), 'pp_glossary_meta_box' ) ) {
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
			'case_sensitive'    => isset( $_POST['pp_glossary_case_sensitive'] ),
			'disable_autolink'  => isset( $_POST['pp_glossary_disable_autolink'] ),
			'short_description' => '',
			'long_description'  => '',
			'synonyms'          => [],
		];

		// Sanitize short description.
		if ( isset( $_POST['pp_glossary_short_description'] ) ) {
			$data['short_description'] = sanitize_textarea_field( wp_unslash( $_POST['pp_glossary_short_description'] ) );
		}

		// Sanitize long description.
		if ( isset( $_POST['pp_glossary_long_description'] ) ) {
			$data['long_description'] = wp_kses_post( wp_unslash( $_POST['pp_glossary_long_description'] ) );
		}

		// Sanitize synonyms.
		if ( isset( $_POST['pp_glossary_synonyms'] ) && is_array( $_POST['pp_glossary_synonyms'] ) ) {
			foreach ( wp_unslash( $_POST['pp_glossary_synonyms'] ) as $synonym ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitization handled below.
				$synonym = sanitize_text_field( $synonym );
				if ( ! empty( $synonym ) ) {
					$data['synonyms'][] = $synonym;
				}
			}
		}

		update_post_meta( $post_id, '_pp_glossary_data', $data );
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
		if ( ! $screen || 'pp_glossary' !== $screen->post_type ) {
			return;
		}

		// Enqueue jQuery.
		wp_enqueue_script( 'jquery' );

		// Add inline script for adding/removing synonyms.
		add_action( 'admin_footer', [ __CLASS__, 'render_synonyms_script' ] );
	}

	/**
	 * Render the synonyms JavaScript in the footer
	 */
	public static function render_synonyms_script(): void {
		?>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			// Add synonym.
			$('#pp-glossary-add-synonym').on('click', function(e) {
				e.preventDefault();
				var container = $('#pp-glossary-synonyms-container');
				var row = $('<div class="pp-glossary-synonym-row" style="margin-bottom: 10px;display: flex;gap: 10px;">' +
					'<input type="text" name="pp_glossary_synonyms[]" value="" class="regular-text" placeholder="<?php echo esc_js( __( 'e.g., CLS, layout shift', 'pp-glossary' ) ); ?>">' +
					'<button type="button" class="button pp-glossary-remove-synonym"><?php echo esc_js( __( 'Remove', 'pp-glossary' ) ); ?></button>' +
					'</div>');
				container.append(row);
			});

			// Remove synonym (delegated event).
			$(document).on('click', '.pp-glossary-remove-synonym', function(e) {
				e.preventDefault();
				$(this).closest('.pp-glossary-synonym-row').remove();
			});
		});
		</script>
		<?php
	}
}
