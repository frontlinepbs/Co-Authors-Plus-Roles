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
	$args = wp_parse_args( $args,
		array( 'contributor_role' => array( 'byline' ) )
	);

	$coauthors = array();

	// Default to current post if $post_id is not set.
	$post_id = (int)$post_id;
	if ( ! $post_id && $post_ID )
		$post_id = $post_ID;
	if ( ! $post_id && $post )
		$post_id = $post->ID;

	if ( $post_id ) {
		// Querying for "bylines" (the default) should return the same results as in CAP
		if ( $args['contributor_role'] !== 'byline' ) {
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
		} // the empty else case is because if we force guest authors, we don't ever care what value wp_posts.post_author has.
	} else {
		// NOTE: This is where this query diverges from CAP
		//
		// Passing the string 'any' or false to 'contributor_roles' should get anyone, regardless of role
		if ( $args['contributor_roles'] === 'any' || ! $args['contributor_roles'] ) {

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
		} else {
			// If we're querying by contributor role, its a whole different query.
			$coauthor_ids = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT meta_value FROM wp_postmeta WHERE post_id = %d AND meta_key IN ( %s )",
					array( $post_id, implode( ',', $args['contributor_roles'] ) ) // TODO! not escaped XXX!
				)
			);
			$coauthors = array();
			foreach( $coauthor_ids as $coauthor_id ) {
				$coauthors[] = $coauthors_plus->get_coauthor_by( 'ID', $coauthor_id );
			}
		}
	}
	return $coauthors;

}
