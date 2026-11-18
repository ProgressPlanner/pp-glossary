<?php
/**
 * Tests for the entry meta box.
 *
 * @package PP_Glossary
 */

namespace PP_Glossary\Tests;

use PP_Glossary\Meta_Boxes;

/**
 * Covers reading and saving glossary entry data.
 */
class Meta_Boxes_Test extends Glossary_TestCase {

	/**
	 * Create a glossary entry without data.
	 *
	 * @return int The post ID.
	 */
	private function create_entry(): int {
		return self::factory()->post->create( [ 'post_type' => 'pp_glossary' ] );
	}

	/**
	 * Clean up the request data.
	 */
	public function tear_down() {
		$_POST = [];
		parent::tear_down();
	}

	/**
	 * Entries without data get the defaults.
	 */
	public function test_get_entry_data_defaults() {
		$defaults = [
			'short_description' => '',
			'long_description'  => '',
			'synonyms'          => [],
			'case_sensitive'    => false,
			'disable_autolink'  => false,
		];
		$post_id  = $this->create_entry();

		$this->assertSame( $defaults, Meta_Boxes::get_entry_data( $post_id ) );

		update_post_meta( $post_id, '_pp_glossary_data', 'not an array' );
		$this->assertSame( $defaults, Meta_Boxes::get_entry_data( $post_id ) );
	}

	/**
	 * Partial data is completed with the defaults.
	 */
	public function test_get_entry_data_merges_defaults() {
		$post_id = $this->create_entry();
		update_post_meta(
			$post_id,
			'_pp_glossary_data',
			[
				'short_description' => 'Short.',
				'case_sensitive'    => true,
			]
		);

		$data = Meta_Boxes::get_entry_data( $post_id );

		$this->assertSame( 'Short.', $data['short_description'] );
		$this->assertTrue( $data['case_sensitive'] );
		$this->assertSame( [], $data['synonyms'] );
		$this->assertFalse( $data['disable_autolink'] );
	}

	/**
	 * Saving sanitizes every field.
	 */
	public function test_save_sanitizes_fields() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$post_id = $this->create_entry();

		$_POST = [
			'pp_glossary_meta_box_nonce'    => wp_create_nonce( 'pp_glossary_meta_box' ),
			'pp_glossary_short_description' => 'Short <script>alert(1)</script><b>text</b>',
			'pp_glossary_long_description'  => '<p onclick="x()">Long</p><script>alert(1)</script>',
			'pp_glossary_synonyms'          => [ ' CLS ', '', '<i>shift</i>' ],
			'pp_glossary_case_sensitive'    => '1',
		];

		Meta_Boxes::save_meta_boxes( $post_id );

		$this->assertSame(
			[
				'case_sensitive'    => true,
				'disable_autolink'  => false,
				'short_description' => 'Short text',
				'long_description'  => '<p>Long</p>alert(1)',
				'synonyms'          => [ 'CLS', 'shift' ],
			],
			get_post_meta( $post_id, '_pp_glossary_data', true )
		);
	}

	/**
	 * Nothing is saved without a valid nonce.
	 */
	public function test_save_requires_valid_nonce() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$post_id = $this->create_entry();

		$_POST = [ 'pp_glossary_short_description' => 'Short' ];
		Meta_Boxes::save_meta_boxes( $post_id );
		$this->assertSame( '', get_post_meta( $post_id, '_pp_glossary_data', true ) );

		$_POST['pp_glossary_meta_box_nonce'] = 'invalid';
		Meta_Boxes::save_meta_boxes( $post_id );
		$this->assertSame( '', get_post_meta( $post_id, '_pp_glossary_data', true ) );
	}

	/**
	 * Nothing is saved for users who cannot edit the entry.
	 */
	public function test_save_requires_capability() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );
		$post_id = $this->create_entry();

		$_POST = [
			'pp_glossary_meta_box_nonce'    => wp_create_nonce( 'pp_glossary_meta_box' ),
			'pp_glossary_short_description' => 'Short',
		];
		Meta_Boxes::save_meta_boxes( $post_id );

		$this->assertSame( '', get_post_meta( $post_id, '_pp_glossary_data', true ) );
	}
}
