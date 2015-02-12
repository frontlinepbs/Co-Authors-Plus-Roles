<?php
/**
 * Provide a default set of contributor roles.
 *
 * These can be filtered by hooking into 'author_roles_created'
 * and calling `register_author_role()`, `remove_author_role()`, or
 * `modify_author_role()`.
 */

namespace CoAuthorsPlusRoles;


/**
 * Defines the set of default roles.
 *
 * XXX: Needs user testing. This set of roles should be vetted by someone with
 * editorial experience, and adjusted to fit a common set of use cases.
 */
function register_default_author_roles() {
	register_author_role( 'author',
		array(
			'byline' => true,
			'name' => __( 'Author', 'co-authors-plus-roles' ),
			'labels' => array(
				'name_user_role_singular' => __( 'Author', 'co-authors-plus-roles' ),
				'name_user_role_plural' => __( 'Authors', 'co-authors-plus-roles' ),
				'post_relationship_by' => __( 'by %s', 'co-authors-plus-roles' )
			)
		)
	);
	register_author_role( 'contributing-author',
		array(
			'byline' => false,
			'name' => __( 'Contributing Author', 'co-authors-plus-roles' ),
			'labels' => array(
				'name_user_role_singular' => __( 'Contributing Author', 'co-authors-plus-roles' ),
				'name_user_role_plural' => __( 'Contributing Authors', 'co-authors-plus-roles' ),
				'post_relationship_by' => __( 'Additional Reporting by %s', 'co-authors-plus-roles' )
			)
		)
	);
	register_author_role( 'photographer',
		array(
			'byline' => false,
			'name' => __( 'Photographer', 'co-authors-plus-roles' ),
			'labels' => array(
				'name_user_role_singular' => __( 'Photographer', 'co-authors-plus-roles' ),
				'name_user_role_plural' => __( 'Photographers', 'co-authors-plus-roles' ),
				'post_relationship_by' => __( 'Photography by %s', 'co-authors-plus-roles' )
			)
		)
	);
}
