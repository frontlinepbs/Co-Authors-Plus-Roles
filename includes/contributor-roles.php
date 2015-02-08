<?php
/**
 * Registers the post type for "Contributor Roles", and provides a basic API
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
function create_contributor_roles() {
	global $_wp_contributor_roles;
	$_wp_contributor_roles = array();

	register_default_contributor_roles();

	do_action( 'contributor_roles_created' );
}

add_action( 'init', '\CoAuthorsPlusRoles\create_contributor_roles', 0 );



/**
 * Registers a new "contributor role" for the site.
 *
 * @param string $slug Slug to use for this contributor role
 * @param array $args Overrides to define this role
 * @return object the created WP_Contributor_Role object
 */
function register_contributor_role( $slug, $args = array() ) {
	global $_wp_contributor_roles;

	$name = ( isset( $args['name'] ) ) ? $args['name'] : ucwords( $slug );

	$defaults = array(
		'slug' => $slug,
		'byline' => false,
		'name' => __( $name ),
		'labels' => array(
			'name_user_role_singular' => __( $name ),
			'name_user_role_plural' => __( $name ),
			'post_relationship_by' => __( "$name by %s" )
		)
	);

	$args = wp_parse_args( $args, $defaults );

	$_wp_contributor_roles[ $slug ] = (object) $args;
}


/**
 * Removes a previously registered "contributor role" for the site.
 *
 * @param string $slug Slug of role to remove
 * @return void
 */
function remove_contributor_role( $slug ) {
	global $_wp_contributor_roles;

	unset( $_wp_contributor_roles[ $slug ] );
}


/**
 * Modifies a previously registered "contributor role" for the site.
 *
 * An alias for register_contributor_role.
 *
 * @param string $slug Slug of role to remove
 * @param array $args Overrides to define this role
 * @return object the created WP_Contributor_Role object
 */
function modify_contributor_role( $slug, $name, $args ) {
	register_contributor_role( $slug, $name, $args );
}


/**
 * Get all registered roles for the current site.
 *
 * @return array Array of all roles ad their arguments.
 */
function get_contributor_roles() {
	global $_wp_contributor_roles;
	return $_wp_contributor_roles;
}


/**
 * Get a single contributor role term by slug.
 *
 * Useful for retrieving labels or settings attached to roles.
 *
 * @return object Contributor role object
 */
function get_contributor_role( $slug ) {
	global $_wp_contributor_roles;

	if ( strpos( $slug, 'cap-' ) === 0 )
		$slug = strstr( $slug, 4 );

	if ( ! isset( $_wp_contributor_roles[ $slug ] ) )
		return false;

	return $_wp_contributor_roles[ $slug ];
}


/**
 * Get a list of the roles which are set as "byline roles".
 *
 * @return array Array of role slugs.
 */
function byline_roles() {
	global $_wp_contributor_roles;

	$byline_roles = array_filter( $_wp_contributor_roles,
		function( $role ) {
			return $role->byline === true;
		}
	);

	return array_keys( $byline_roles );
}

