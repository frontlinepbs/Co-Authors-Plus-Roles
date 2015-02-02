<?php
/**
 * All functions for querying post-author relationships.
 *
 */

namespace CoAuthorsPlusRoles;


/**
 * Get all coauthors on a post, and their roles.
 *
 * A lot of copypasta from get_coauthors() in Co-Authors Plus, with additional options.
 *
 * @param int $post_ID ID of post to check. Defaults to the current post.
 * @param arr $args    Query options. Available options are:
 *						contributor_role array of roles to query in
 * @return arr Array of authors
 */
function get_coauthors( $post_id = 0, $args = array() ) {
	global $post, $post_ID, $coauthors_plus, $wpdb;

	// Merge default query args with parameters
	$args = wp_parse_args( $args, array( 'contributor_role' => 'byline' ) );

	$coauthors = array();

	// Default to current post if $post_id is not set.
	$post_id = (int)$post_id;
	if ( ! $post_id && $post_ID )
		$post_id = $post_ID;
	if ( ! $post_id && $post )
		$post_id = $post->ID;

	if ( ! $post_id )
		return;

	// Querying for "bylines" (the default) should return the same results as in CAP
	if ( $args['contributor_role'] === 'byline' ) {
		/*
		 * This query is the same as the one from Co-Authors Plus, because any co-authors
		 * added in byline roles should have an author term set.
		 */
		$coauthor_terms = get_the_terms( $post_id, $coauthors_plus->coauthor_taxonomy );
		if ( is_array( $coauthor_terms ) && ! empty( $coauthor_terms ) ) {
			foreach ( $coauthor_terms as $coauthor ) {
				$coauthor_slug = preg_replace( '#^cap\-#', '', $coauthor->slug );
				$post_author = $coauthors_plus->get_coauthor_by( 'user_nicename', $coauthor_slug );
				// In case the user has been deleted while plugin was deactivated
				if ( ! empty( $post_author ) )
					$coauthors[] = $post_author;
			}
		} else if ( ! $coauthors_plus->force_guest_authors ) {
			if ( $post && $post_id == $post->ID ) {
				$post_author = get_userdata( $post->post_author );
			} else {
				$post_author = get_userdata( $wpdb->get_var( $wpdb->prepare( "SELECT post_author FROM $wpdb->posts WHERE ID = %d", $post_id ) ) );
			}
			if ( ! empty( $post_author ) )
				$coauthors[] = $post_author;
		} // the empty else case is because if we force guest authors, we don't ever care what value wp_posts.post_author has.
	} else {
		/*
		 * Any other queries need to be performed using postmeta fields.
		 *
		 * Passing the string 'any' or false to 'contributor_role' should get anyone, regardless of role.
		 * Passing a string or array of strings should get all roles with those slugs.
		 */
		if ( $args['contributor_role'] === 'any' || ! $args['contributor_role'] ) {
			$contributor_roles = wp_list_pluck( get_contributor_roles(), 'slug' );
		} else if ( is_string( $args['contributor_role'] ) ) {
			$contributor_roles = array( $args['contributor_role'] );
		} else {
			$contributor_roles = $args['contributor_role'];
		}

		$roles_meta_keys = array_map(
			function( $term ) { return 'cap-' . $term; },
			array_filter( $contributor_roles, 'CoAuthorsPlusRoles\get_contributor_role' )
		);

		// Because $wpdb->prepare would add slashes to my IN() clause...
		$meta_key_in = "( '" . implode( "','", array_map( 'esc_sql', $roles_meta_keys ) ) . "' )";

		$coauthor_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key IN {$meta_key_in};",
				array( $post_id )
			)
		);

		foreach ( $coauthor_ids as $coauthor_id ) {
			$coauthors[] = $coauthors_plus->get_coauthor_by( 'id', $coauthor_id );
		}

		// Now, get the author terms, in case of bylines or coauthors who were entered with CAP.
		$coauthor_terms = get_the_terms( $post_id, $coauthors_plus->coauthor_taxonomy );

		if ( is_array( $coauthor_terms ) && ! empty( $coauthor_terms ) ) {
			foreach ( $coauthor_terms as $coauthor ) {
				$coauthor_slug = preg_replace( '#^cap\-#', '', $coauthor->slug );
				$post_author = $coauthors_plus->get_coauthor_by( 'user_nicename', $coauthor_slug );
				// In case the user has been deleted while plugin was deactivated
				if ( ! empty( $post_author ) )
					$coauthors[] = $post_author;
			}
		} else if ( ! $coauthors_plus->force_guest_authors ) {
			if ( $post && $post_id == $post->ID ) {
				$post_author = get_userdata( $post->post_author );
			} else {
				$post_author = get_userdata( $wpdb->get_var( $wpdb->prepare( "SELECT post_author FROM $wpdb->posts WHERE ID = %d", $post_id ) ) );
			}
			if ( ! empty( $post_author ) )
				$coauthors[] = $post_author;
		}
	}
	return $coauthors;

}
