<?php // phpcs:disable WordPress.Files.FileName -- PHPUnit naming (*Test.php).
/**
 * CTA target rule (#40, Q9 in #34): `#kontakt` when the page has ContactBlock, else `/kontakt/#kontakt`.
 *
 * @package PW
 */

namespace PW\Tests\Integration;

use WP_UnitTestCase;

/**
 * Helper pw_contact_target() on real posts.
 */
class ContactTargetTest extends WP_UnitTestCase {

	/**
	 * Page with the given content.
	 *
	 * @param string $content Block markup.
	 */
	private function page( string $content ): int {
		return self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_content' => $content,
			)
		);
	}

	public function test_page_with_contact_block_targets_the_anchor_on_the_same_page(): void {
		$id = $this->page( '<!-- wp:paragraph --><p>Hej</p><!-- /wp:paragraph --><!-- wp:pw/contact /-->' );

		$this->assertSame( '#kontakt', pw_contact_target( $id ) );
	}

	public function test_page_without_contact_block_targets_the_contact_page(): void {
		$id = $this->page( '<!-- wp:paragraph --><p>Cennik</p><!-- /wp:paragraph -->' );

		$this->assertSame( home_url( '/kontakt/#kontakt' ), pw_contact_target( $id ) );
	}

	public function test_contact_block_nested_in_a_group_counts(): void {
		$id = $this->page( '<!-- wp:group --><div class="wp-block-group"><!-- wp:pw/contact {"wariant":"pelny"} /--></div><!-- /wp:group -->' );

		$this->assertSame( '#kontakt', pw_contact_target( $id ) );
	}

	public function test_contact_block_inside_a_synced_pattern_counts(): void {
		$pattern = self::factory()->post->create(
			array(
				'post_type'    => 'wp_block',
				'post_status'  => 'publish',
				'post_content' => '<!-- wp:group --><div class="wp-block-group"><!-- wp:pw/contact /--></div><!-- /wp:group -->',
			)
		);
		$id      = $this->page( '<!-- wp:block {"ref":' . $pattern . '} /-->' );

		$this->assertSame( '#kontakt', pw_contact_target( $id ) );
	}

	public function test_synced_pattern_without_contact_block_does_not_count(): void {
		$pattern = self::factory()->post->create(
			array(
				'post_type'    => 'wp_block',
				'post_status'  => 'publish',
				'post_content' => '<!-- wp:paragraph --><p>Stopka</p><!-- /wp:paragraph -->',
			)
		);
		$id      = $this->page( '<!-- wp:block {"ref":' . $pattern . '} /-->' );

		$this->assertSame( home_url( '/kontakt/#kontakt' ), pw_contact_target( $id ) );
	}

	public function test_defaults_to_the_current_post(): void {
		$this->go_to( get_permalink( $this->page( '<!-- wp:pw/contact /-->' ) ) );

		$this->assertSame( '#kontakt', pw_contact_target() );
	}

	public function test_target_is_filterable(): void {
		$id = $this->page( '' );
		add_filter(
			'pw/contact_target',
			static fn( string $target, ?int $post_id ): string => $target . '?z=' . $post_id,
			10,
			2
		);

		$this->assertSame( home_url( '/kontakt/#kontakt' ) . '?z=' . $id, pw_contact_target( $id ) );
	}
}
