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

}
