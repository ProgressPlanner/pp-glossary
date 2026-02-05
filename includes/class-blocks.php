<?php
/**
 * Block Registration for Glossary
 *
 * @package PP_Glossary
 */

namespace PP_Glossary;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Blocks
 */
class Blocks {

	/**
	 * Initialize blocks
	 */
	public static function init(): void {
		add_action( 'init', [ __CLASS__, 'register_blocks' ] );
	}

	/**
	 * Register blocks
	 */
	public static function register_blocks(): void {
		// Register the editor script.
		wp_register_script(
			'pp-glossary-block-editor',
			PP_GLOSSARY_PLUGIN_URL . 'blocks/glossary-list/editor.js',
			[ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ],
			PP_GLOSSARY_VERSION,
			true
		);

		// Register the block type.
		register_block_type(
			PP_GLOSSARY_PLUGIN_DIR . 'blocks/glossary-list',
			[
				'editor_script'   => 'pp-glossary-block-editor',
				'render_callback' => [ __CLASS__, 'render_glossary_list_block' ],
			]
		);
	}

	/**
	 * Render glossary list block
	 *
	 * @return string|false Block HTML.
	 */
	public static function render_glossary_list_block() {
		$grouped_entries = self::get_grouped_entries();

		// Get glossary page ID for schema.
		$glossary_page_id = Settings::get_glossary_page_id();
		$use_microdata    = ! defined( 'WPSEO_VERSION' );
		$glossary_url     = Settings::get_glossary_page_url();

		// Get schema microdata attributes (empty if Yoast SEO is active).
		$schema_attrs = Schema::get_microdata_attributes( $glossary_page_id );

		ob_start();
		?>
		<div class="pp-glossary-block"<?php echo $schema_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php if ( ! empty( $grouped_entries ) ) : ?>
				<?php
				// Hidden schema name for microdata.
				if ( $use_microdata && $glossary_page_id ) {
					echo '<meta itemprop="name" content="' . esc_attr( get_the_title( $glossary_page_id ) ) . '">';
				}
				?>
				<nav class="glossary-navigation" aria-label="<?php esc_attr_e( 'Glossary alphabet navigation', 'pp-glossary' ); ?>">
					<ul class="glossary-alphabet">
						<?php foreach ( $grouped_entries as $letter => $entries ) : ?>
							<li>
								<a href="#letter-<?php echo esc_attr( pp_glossary_strtolower( $letter ) ); ?>">
									<?php echo esc_html( $letter ); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</nav>

				<div class="glossary-entries">
					<?php foreach ( $grouped_entries as $letter => $entries ) : ?>
						<section class="glossary-letter-section" id="letter-<?php echo esc_attr( pp_glossary_strtolower( $letter ) ); ?>">
							<h3 class="glossary-letter-heading"><?php echo esc_html( $letter ); ?></h3>

							<?php foreach ( $entries as $entry ) : ?>
								<?php $entry_schema = Schema::get_entry_microdata_attributes(); ?>
								<article id="<?php echo esc_attr( $entry['slug'] ); ?>" class="glossary-entry"<?php echo $entry_schema; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
									<?php $entry_url = $glossary_url . '#' . $entry['slug']; ?>
									<?php if ( $use_microdata ) : ?>
										<link itemprop="url" href="<?php echo esc_url( $entry_url ); ?>">
									<?php endif; ?>

									<h4 class="glossary-entry-title"<?php echo Schema::get_itemprop( 'name' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
										<?php echo esc_html( $entry['title'] ); ?>
									</h4>

									<?php if ( current_user_can( 'edit_post', $entry['id'] ) ) : ?>
										<p class="glossary-edit-link">
											<a href="<?php echo esc_url( get_edit_post_link( $entry['id'] ) ); ?>">
												<?php esc_html_e( 'Edit', 'pp-glossary' ); ?>
											</a>
										</p>
									<?php endif; ?>

									<?php if ( ! empty( $entry['synonyms'] ) ) : ?>
										<div class="glossary-synonyms">
											<span class="synonyms-label"><?php esc_html_e( 'Also known as:', 'pp-glossary' ); ?></span>
											<span><?php echo esc_html( implode( ', ', $entry['synonyms'] ) ); ?></span>
											<?php
											// Output multiple meta tags for Microdata (array of alternateName).
											if ( $use_microdata ) {
												foreach ( $entry['synonyms'] as $synonym ) {
													echo '<meta itemprop="alternateName" content="' . esc_attr( $synonym ) . '">';
												}
											}
											?>
										</div>
									<?php endif; ?>

									<?php
									$description = ! empty( $entry['long_description'] ) ? $entry['long_description'] : $entry['short_description'];
									if ( ! empty( $description ) ) :
										// Link glossary terms within the description, excluding self.
										$description = Term_Linker::link_terms_in_text( $description, $entry['id'] );
										?>
										<div class="glossary-long-description" <?php echo Schema::get_itemprop( 'description' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
											<?php echo wp_kses_post( $description ); ?>
										</div>
									<?php endif; ?>
								</article>
							<?php endforeach; ?>
						</section>
					<?php endforeach; ?>
				</div>

			<?php else : ?>
				<p><?php esc_html_e( 'No glossary entries found.', 'pp-glossary' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get glossary entries grouped by first letter
	 *
	 * @return array<string, array<int, array<string, mixed>>> Grouped glossary entries.
	 */
	private static function get_grouped_entries(): array {
		$grouped = [];
		$entries = pp_glossary_get_entries();

		foreach ( $entries as $entry ) {
			$letter = pp_glossary_strtoupper( pp_glossary_substr( $entry['title'], 0, 1 ) );

			// Handle numbers and special characters.
			// Match Latin (including extended), Cyrillic, and Greek letters.
			if ( ! preg_match( '/[\p{Latin}\p{Cyrillic}\p{Greek}]/u', $letter ) ) {
				$letter = '#';
			}

			if ( ! isset( $grouped[ $letter ] ) ) {
				$grouped[ $letter ] = [];
			}

			$grouped[ $letter ][] = $entry;
		}

		ksort( $grouped );

		return $grouped;
	}
}
