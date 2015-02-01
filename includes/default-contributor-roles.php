<?php
/**
 * Provide a default set of contributor roles.
 *
 * These can be filtered by hooking into 'contributor_roles_created'
 * and calling `register_contributor_role()`, `remove_contributor_role()`, or
 * `modify_contributor_role()`.
 */

namespace CoAuthorsPlusRoles;


/**
 * Defines the set of default roles.
 *
 * XXX: Needs user testing. This set of roles should be vetted by someone with
 * editorial experience, and adjusted to fit a common set of use cases.
 */
function register_default_contributor_roles() {
	register_contributor_role( 'author',
		array(
			'byline' => true,
			'name' => __( 'Author' ),
			'labels' => array(
				'name_user_role_singular' => __( 'Author' ),
				'name_user_role_plural' => __( 'Authors' ),
				'post_relationship_by' => __( 'by %s' )
			)
		)
	);
	register_contributor_role( 'contributing-author',
		array(
			'byline' => true,
			'name' => __( 'Contributing Author' ),
			'labels' => array(
				'name_user_role_singular' => __( 'Contributing Author' ),
				'name_user_role_plural' => __( 'Contributing Authors' ),
				'post_relationship_by' => __( 'Additional Reporting by %s' )
			)
		)
	);
	register_contributor_role( 'photographer',
		array(
			'byline' => true,
			'name' => __( 'Photographer' ),
			'labels' => array(
				'name_user_role_singular' => __( 'Photographer' ),
				'name_user_role_plural' => __( 'Photographers' ),
				'post_relationship_by' => __( 'Photography by %s' )
			)
		)
	);
}
