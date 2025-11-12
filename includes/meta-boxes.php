<?php
/**
 * Meta Boxes for Glossary
 *
 * @package PP_Glossary
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class PP_Glossary_Meta_Boxes
 */
class PP_Glossary_Meta_Boxes {

	/**
	 * Initialize the meta boxes
	 */
	public static function init() {
		add_action( 'add_meta_boxes', [ __CLASS__, 'add_meta_boxes' ] );
		add_action( 'save_post_pp_glossary', [ __CLASS__, 'save_meta_boxes' ], 10, 2 );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_scripts' ] );
	}

	/**
	 * Add meta boxes for glossary entries
	 */
	public static function add_meta_boxes() {
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
	 * Render the meta box content
	 *
	 * @param WP_Post $post The post object.
	 */
	public static function render_meta_box( $post ) {
		// Add nonce for security.
		wp_nonce_field( 'pp_glossary_meta_box', 'pp_glossary_meta_box_nonce' );

		// Get current values.
		$short_description = get_post_meta( $post->ID, '_pp_glossary_short_description', true );
		$long_description  = get_post_meta( $post->ID, '_pp_glossary_long_description', true );
		$synonyms          = get_post_meta( $post->ID, '_pp_glossary_synonyms', true );

		if ( ! is_array( $synonyms ) ) {
			$synonyms = [];
		}
		?>
		<div class="pp-glossary-meta-box">
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
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public static function save_meta_boxes( $post_id, $post ) {
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

		// Save short description.
		if ( isset( $_POST['pp_glossary_short_description'] ) ) {
			update_post_meta(
				$post_id,
				'_pp_glossary_short_description',
				sanitize_textarea_field( wp_unslash( $_POST['pp_glossary_short_description'] ) )
			);
		}

		// Save long description.
		if ( isset( $_POST['pp_glossary_long_description'] ) ) {
			update_post_meta(
				$post_id,
				'_pp_glossary_long_description',
				wp_kses_post( wp_unslash( $_POST['pp_glossary_long_description'] ) )
			);
		}

		// Save synonyms.
		$synonyms = [];
		if ( isset( $_POST['pp_glossary_synonyms'] ) && is_array( $_POST['pp_glossary_synonyms'] ) ) {
			foreach ( wp_unslash( $_POST['pp_glossary_synonyms'] ) as $synonym ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitization handled below.
				$synonym = sanitize_text_field( $synonym );
				if ( ! empty( $synonym ) ) {
					$synonyms[] = $synonym;
				}
			}
		}
		update_post_meta( $post_id, '_pp_glossary_synonyms', $synonyms );
	}

	/**
	 * Enqueue admin scripts for synonyms functionality
	 *
	 * @param string $hook The current admin page hook.
	 */
	public static function enqueue_admin_scripts( $hook ) {
		// Only load on post edit screens for glossary entries.
		if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ] ) ) {
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
	public static function render_synonyms_script() {
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
