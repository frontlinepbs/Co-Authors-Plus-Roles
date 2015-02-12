<?php
/**
 * Registers the post type for "Author Roles", and provides a basic API
 * for adding and removing roles.
 *
 */

namespace CoAuthorsPlusRoles;


/**
 * Register the default objects for contributor roles.
 *
 * Called on init. Registers the default roles.
 *
 */
function create_author_roles() {
	global $_wp_author_roles;
	$_wp_author_roles = array();

	register_default_author_roles();

	do_action( 'author_roles_created' );
}

add_action( 'init', '\CoAuthorsPlusRoles\create_author_roles', 0 );



/**
 * Registers a new "contributor role" for the site.
 *
 * @param string $slug Slug to use for this contributor role
 * @param array $args Overrides to define this role
 * @return object the created WP_Author_Role object
 */
function register_author_role( $slug, $args = array() ) {
	global $_wp_author_roles;

	$name = ( isset( $args['name'] ) ) ? $args['name'] : ucwords( $slug );

	$defaults = array(
		'slug' => $slug,
		'byline' => false,
		'name' => $name,
		'labels' => array(
			'name_user_role_singular' => $name,
			'name_user_role_plural' => $name,
			'post_relationship_by' => "$name by %s"
		)
	);

	$args = wp_parse_args( $args, $defaults );

	$_wp_author_roles[ $slug ] = (object) $args;
}


/**
 * Removes a previously registered "contributor role" for the site.
 *
 * @param string $slug Slug of role to remove
 * @return void
 */
function remove_author_role( $slug ) {
	global $_wp_author_roles;

	unset( $_wp_author_roles[ $slug ] );
}


/**
 * Modifies a previously registered "contributor role" for the site.
 *
 * An alias for register_author_role.
 *
 * @param string $slug Slug of role to remove
 * @param array $args Overrides to define this role
 * @return object the created WP_Author_Role object
 */
function modify_author_role( $slug, $name, $args ) {
	register_author_role( $slug, $name, $args );
}


/**
 * Get all registered roles for the current site.
 *
 * @return array Array of all roles ad their arguments.
 */
function get_author_roles() {
	global $_wp_author_roles;
	return $_wp_author_roles;
}


/**
 * Get a single contributor role term by slug.
 *
 * Useful for retrieving labels or settings attached to roles.
 *
 * @return object Author role object
 */
function get_author_role( $slug ) {
	global $_wp_author_roles;

	if ( strpos( $slug, 'cap-' ) === 0 )
		$slug = strstr( $slug, 4 );

	if ( ! isset( $_wp_author_roles[ $slug ] ) )
		return false;

	return $_wp_author_roles[ $slug ];
}


/**
 * Get a list of the roles which are set as "byline roles".
 *
 * @return array Array of role slugs.
 */
function byline_roles() {
	global $_wp_author_roles;

	$byline_roles = array_filter( $_wp_author_roles,
		function( $role ) {
			return $role->byline === true;
		}
	);

	return array_keys( $byline_roles );
}

