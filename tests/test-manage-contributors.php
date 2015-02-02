<?php
/**
 * Test new functions for managing contributors and contributor roles.
 *
 * These tests mirror the tests in test-manage-coauthors.php, but also include roles.
 */

class Test_Manage_ContributorRoles extends CoAuthorsPlusRoles_TestCase {

	/**
	 * Test registering, removing, and modifying a role.
	 */
	public function test_manage_contributor_roles() {

		// The default contributor roles should be active initially.
		$roles = get_contributor_roles();
		$this->assertEquals( 3, count( $roles ) );
		$this->assertEquals( 'Author', $roles['author']->name );

		// removing and adding roles.
		remove_contributor_role( 'photographer' );
		$roles = get_contributor_roles();
		$this->assertEquals( 2, count( $roles ) );
	}

	/**
	 * Test assigning a WP user a role on a post.
	 */
	public function test_manage_contributor_roles_relationships() {
		global $coauthors_plus;
		$author1 = get_user_by( 'id', $this->author1 );

		// Setup: assign author1 as the only co-author (byline)
		$coauthors_plus->add_coauthors(
			$this->author1_post1, array( $author1->user_login ), false
		);
		$this->assertEquals( $this->author1, get_post( $this->author1_post1 )->post_author );

		// Add a coauthor in a non-byline role. Should not be returned by get_coauthors.
		\CoAuthorsPlusRoles\set_contributor_on_post(
			$this->author1_post1, $this->editor1, 'contributing-author'
		);
		$coauthors = get_coauthors( $this->author1_post1 );
		$coauthors_this_plugin = CoAuthorsPlusRoles\get_coauthors( $this->author1_post1 );
		$this->assertEquals( $coauthors, $coauthors_this_plugin );
		$this->assertEquals( 1, count( $coauthors ) );

		$all_credits = CoAuthorsPlusRoles\get_coauthors( $this->author1_post1, array( 'contributor_role' => 'any' ) );
		$this->assertEquals( 2, count( $all_credits ) );

	}
}
