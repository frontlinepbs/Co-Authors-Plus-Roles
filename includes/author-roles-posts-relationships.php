<?php
/**
 * Functions that create, establish, or change a relationship between a co-author and a post.
 *
 */

namespace CoAuthorsPlusRoles;


/**
 * Sets a guest author as a contributor on a post, with a specified role.
 *
 * This should be called on all additional contributors, not on primary
 * authors/bylines, who will use the existing functionality from Co-Authors Plus.
 *
 * @param int|object $post_id Post to set author as "coauthor" on
 * @param object|string $author user_nicename of Author to add on post (or WP_User object)
 * @param object|string $author_role Term or slug of contributor role to set. Defaults to "byline" if empty
 * @return bool True on success, false on failure (if any of the inputs are not acceptable).
 */
function set_author_on_post( $post_id, $author, $author_role = false ) {
	global $coauthors_plus;

	if ( is_object( $post_id ) && isset( $post_id->ID ) ) {
		$post_id = $post_id->ID;
	}

	$post_id = intval( $post_id );

	if ( is_string( $author ) ) {
		$author = $coauthors_plus->get_coauthor_by( 'user_nicename', $author );
	}

	if ( ! isset( $author->user_nicename ) ) {
		return false;
	}

	// Only create the byline term if the contributor role is:
	//  - one of the byline roles, as set in register_author_role(), or
	//  - unset, meaning they should default to primary author role.
	if ( ! $author_role || in_array( $author_role, byline_roles() ) ) {
		$coauthors_plus->add_coauthors( $post_id, array( $author->user_nicename ), true );
	}

	if ( ! $post_id || ! $author ) {
		return false;
	}

	foreach ( get_post_meta( $post_id ) as $key => $values ) {
		if ( strpos( $key, 'cap-' ) === 0 && in_array( $author->user_nicename, $values ) ) {
			delete_post_meta( $post_id, $key, $author->user_nicename );
		}
	}

	if ( ! is_object( $author_role ) ) {
		$author_role = get_author_role( $author_role );
	}

	if ( ! $author_role ) {
		return false;
	}

	add_post_meta( $post_id, 'cap-' . $author_role->slug, $author->user_nicename );
}


/**
 * Clear all coauthor data on a post.
 *
 * Used when updating coauthors, as otherwise there's no way of ordering them.
 *
 * @param int $post_id Post to remove coauthor postmeta from
 */
function remove_all_coauthor_meta( $post_id ) {
	$post_meta = get_post_meta( $post_id );
	$user_nicenames = wp_list_pluck( get_top_authors(), 'user_nicename' );

	foreach ( $post_meta as $key => $values ) {
		if ( strpos( $key, 'cap-' === 0 ) ) {
			foreach ( $values as $value ) {
				if ( in_array( $value, $user_nicenames ) )
					delete_post_meta( $post_id, $key, $value );
			}
		}
	}
}


/**
 * Removes a guest author from a post.
 *
 * @param int|object $post_id Post to set author as "coauthor" on
 * @param object|string $author WP_User object, or nicename/user_login/slug
 * @return bool True on success, false on failure (if any of the inputs are not acceptable).
 */
function remove_author_from_post( $post_id, $author ) {
	global $coauthors_plus;

	if ( is_object( $post_id ) && isset( $post_id->ID ) ) {
		$post_id = $post_id->ID;
	}

	$post_id = intval( $post_id );

	if ( is_string( $author ) ) {
		$author = $coauthors_plus->get_coauthor_by( 'user_nicename', $author );
	}

	if ( is_int( $author ) ) {
		$author = $coauthors_plus->get_coauthor_by( 'id', $author );
	}

	// Remove byline term from post: Start by getting the author terms on the post.
	$existing_authors = wp_get_object_terms( $post_id, $coauthors_plus->coauthor_taxonomy, array( 'fields' => 'slugs' ) );
	$new_authors = array_diff( $existing_authors, array( 'cap-' . $author->user_nicename ) );
	wp_set_object_terms( $post_id, $new_authors, $coauthors_plus->coauthor_taxonomy, true );

	// Delete meta value setting contributor on post
	foreach ( get_post_meta( $post_id ) as $key => $values ) {
		if ( strpos( $key, 'cap-' ) === 0 && in_array( $author->display_name, $values ) ) {
			delete_post_meta( $post_id, $key, $author->display_name );
		}
	}
}

