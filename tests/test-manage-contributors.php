<?php
/**
 * Test new functions for managing contributors and contributor roles.
 *
 * These tests mirror the tests in test-manage-coauthors.php, but also include roles.
 */

class Test_Manage_Author_Roles extends CoAuthorsPlusRoles_TestCase {

	/**
	 * Test registering, removing, and modifying a role.
	 */
	public function test_setup_and_remove_author_roles() {

		// The default author roles should be active initially.
		$roles = get_author_roles();
		$this->assertEquals( 3, count( $roles ) );
		$this->assertEquals( 'Author', $roles['author']->name );

		// removing and adding roles.
		remove_author_role( 'photographer' );
		$roles = get_author_roles();
		$this->assertEquals( 2, count( $roles ) );
	}

	/**
	 * Test assigning a WP user a role on a post.
	 */
	public function test_manage_author_roles_relationships() {
		global $coauthors_plus;
		$author1 = get_user_by( 'id', $this->author1 );
		$editor1 = get_user_by( 'id', $this->editor1 );

		// Setup: assign author1 as the only co-author (byline)
		$coauthors_plus->add_coauthors(
			$this->author1_post1, array( $author1->user_nicename ), false
		);
		$this->assertEquals( $this->author1, get_post( $this->author1_post1 )->post_author );

		// Add a coauthor in a non-byline role. Should not be returned by get_coauthors.
		\CoAuthorsPlusRoles\set_author_on_post(
			$this->author1_post1, $editor1, 'contributor'
		);
		$coauthors = get_coauthors( $this->author1_post1 );
		$coauthors_this_plugin = CoAuthorsPlusRoles\get_coauthors( $this->author1_post1 );
		$this->assertEquals( count( $coauthors ), count( $coauthors_this_plugin ) );
		$this->assertEquals( 1, count( $coauthors ) );

		$all_credits = CoAuthorsPlusRoles\get_coauthors( $this->author1_post1, array( 'author_role' => 'any' ) );
		$this->assertEquals( 2, count( $all_credits ) );

		// Remove a co-author from a post
		\CoAuthorsPlusRoles\remove_author_from_post( $this->author1_post1, $this->editor1 );
		$all_credits = CoAuthorsPlusRoles\get_coauthors( $this->author1_post1, array( 'author_role' => 'any' ) );
		$this->assertEquals( 1, count( $all_credits ) );

	}

	/**
	 * Test the functions called by the Admin UI.
	 */
	public function test_admin_ui_author_roles_functions() {
		global $coauthors_plus;

		// Add a couple new guest authors on this site. They should be included
		// in the get_top_coauthors count.
		$guest_author_1 = $coauthors_plus->guest_authors->create( array(
			'display_name' => 'New Guest Author 1',
			'user_login' => 'new-guest-author-1'
		) );
		$guest_author_2 = $coauthors_plus->guest_authors->create( array(
			'display_name' => 'New Guest Author 2',
			'user_login' => 'new-guest-author-2'
		) );
		$all_contributors = CoAuthorsPlusRoles\get_top_authors();
		$this->assertEquals( count( $all_contributors ), 3 );
		$this->assertContains( $guest_author_1, wp_list_pluck( $all_contributors, 'ID' ) );
		$this->assertContains( $guest_author_2, wp_list_pluck( $all_contributors, 'ID' ) );

		$post = $this->author1_post1;

		$guest_author_1_user_object = $coauthors_plus->get_coauthor_by( 'id', $guest_author_1 );
		$guest_author_2_user_object = $coauthors_plus->get_coauthor_by( 'id', $guest_author_2 );

		// search_coauthors() on a post with no search term specified should return all coauthors on the site
		$all_contributors = CoAuthorsPlusRoles\search_coauthors( false );
		$this->assertContains( $guest_author_1, wp_list_pluck( $all_contributors, 'ID' ) );

		// Passing an exclude array to search_coauthors() should exclude them from the results returned
		$contributors_minus_ga1 = CoAuthorsPlusRoles\search_coauthors( false,
			array(
				'post_ID' => $post,
				'exclude' => array( $guest_author_1_user_object->user_nicename )
			)
		);
		$this->assertNotContains( $guest_author_1, wp_list_pluck( $contributors_minus_ga1, 'ID' ) );


		// Setting a guest author as an author on a post should include
		// them in the get_coauthors() response for that post.
		\CoAuthorsPlusRoles\set_author_on_post( $post, $guest_author_1_user_object );
		$all_credits_on_post = CoAuthorsPlusRoles\get_coauthors( $post, array( 'author_role' => 'any' ) );
		$this->assertContains( $guest_author_1, wp_list_pluck( $all_credits_on_post, 'ID' ) );

		// After adding author to post, search_coauthors() on that post should no longer return them.
		$all_contributors = CoAuthorsPlusRoles\search_coauthors( false, "post_ID=$post" );
		$this->assertNotContains( $guest_author_1, wp_list_pluck( $all_contributors, 'ID' ) );

		// Setting a guest author as a non-byline role on a post should also include
		// them in the get_coauthors() response for that post.
		\CoAuthorsPlusRoles\set_author_on_post( $post, $guest_author_2_user_object, 'contributor' );
		$all_credits_on_post = CoAuthorsPlusRoles\get_coauthors( $post, array( 'author_role' => 'any' ) );
		$this->assertContains( $guest_author_2, wp_list_pluck( $all_credits_on_post, 'ID' ) );

		// After adding author to post in non-byline role, search_coauthors() on that post should no
		// longer return them.
		$all_contributors = CoAuthorsPlusRoles\search_coauthors( false, "post_ID=$post" );
		$this->assertNotContains( $guest_author_2, wp_list_pluck( $all_contributors, 'ID' ) );

	}

	/**
	 * Test the functions called on update_post to edit the contributors on a post.
	 */
	public function test_admin_set_contributors_on_post() {
		global $coauthors_plus;

		// Create a post with a WP user as author. Calling update_coauthors_on_post should reset it.
		$post = $this->factory->post->create( array(
			'post_status'     => 'publish',
			'post_content'    => rand_str(),
			'post_title'      => rand_str(),
			'post_author'     => $this->author1_post1,
			) );
		$guest_author = $coauthors_plus->guest_authors->create( array(
			'display_name' => 'Guest Author through UI',
			'user_login' => 'guest-author-through-ui'
		) );
		$guest_author_nicename = $coauthors_plus->get_coauthor_by( 'id', $guest_author )->user_nicename;

		\CoAuthorsPlusRoles\update_coauthors_on_post( $post, array( "{$guest_author_nicename}|||author" ) );
		$updated_coauthors = \CoAuthorsPlusRoles\get_coauthors( (int) $post, array( 'author_role' => 'any' ) );
		$this->assertCount( 1, $updated_coauthors );
		$this->assertContains( $guest_author, wp_list_pluck( $updated_coauthors, 'ID' ) );

		$user1 = get_userdata( $this->author1 );

		// Calling update_coauthors_on_post with an array should add all the new authors
		// Using: one WP_User, and one of Guest Author post type
		\CoAuthorsPlusRoles\update_coauthors_on_post( $post,
			array( "{$user1->user_login}|||author", "guest-author-through-ui|||contributor" )
		);

		// Both should show up in a query with author_role = any
		$updated_coauthors = \CoAuthorsPlusRoles\get_coauthors( $post, array( 'author_role' => 'any' ) );
		$this->assertContains( $user1->user_login, wp_list_pluck( $updated_coauthors, 'user_nicename' ) );
		$this->assertContains( 'guest-author-through-ui', wp_list_pluck( $updated_coauthors, 'user_nicename' ) );

		// Only the contributor should show up in this query where author_role = contributor
		$contributors = \CoAuthorsPlusRoles\get_coauthors( $post, array( 'author_role' => 'contributor' ) );
		$this->assertNotContains( $user1->user_login, wp_list_pluck( $contributors, 'user_nicename' ) );
		$this->assertContains( 'guest-author-through-ui', wp_list_pluck( $contributors, 'user_nicename' ) );

		// Updating coauthors in a different order; the same tests should pass, but the new order should be preserved.
		\CoAuthorsPlusRoles\update_coauthors_on_post( $post,
			array( "guest-author-through-ui|||contributor", "{$user1->user_login}|||author" )
		);

		// Both should show up in a query with author_role = any, and the first one posted should be first
		$updated_coauthors = \CoAuthorsPlusRoles\get_coauthors( $post, array( 'author_role' => 'any' ) );

		$this->assertContains( $user1->user_login, wp_list_pluck( $updated_coauthors, 'user_nicename' ) );
		$this->assertContains( 'guest-author-through-ui', wp_list_pluck( $updated_coauthors, 'user_nicename' ) );
		$this->assertEquals( 'guest-author-through-ui', $updated_coauthors[0]->user_nicename );

		// Updating an individual coauthor; should change role (sorting is presently undefined...)
		\CoAuthorsPlusRoles\set_author_on_post( $post, 'guest-author-through-ui', 'author' );

		$updated_coauthors = \CoAuthorsPlusRoles\get_coauthors( $post, array( 'author_role' => 'author' ) );
		$this->assertcount( 2, $updated_coauthors );

		// Removing an individual coauthor using `remove_coauthor_from_post()`
		\CoAuthorsPlusRoles\remove_author_from_post( $post, 'guest-author-through-ui' );
		$updated_coauthors = \CoAuthorsPlusRoles\get_coauthors( $post, array( 'author_role' => 'any' ) );
		$this->assertcount( 1, $updated_coauthors );
	}

}
