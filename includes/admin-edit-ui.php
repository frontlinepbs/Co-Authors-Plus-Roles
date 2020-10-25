<?php
/**
 * Display the meta box and other UI elements for this plugin on the post.php screen.
 *
 * Responsible for removing the meta box from CAP, dequeueing its scripts, as
 * well as adding the new meta box for roles information.
 *
 */

namespace CoAuthorsPlusRoles;


/*
 * Initialize admin scripts for this plugin.
 *
 * Responsible for removing the meta box from CAP, dequeueing its scripts, as
 * well as adding the new meta box for roles information.
 */
function admin_init() {
	global $coauthors_plus;

	if ( empty( $coauthors_plus ) ) {
		return;
	}

	remove_action( 'add_meta_boxes',        array( $coauthors_plus, 'add_coauthors_box' ) );
	remove_action( 'admin_enqueue_scripts', array( $coauthors_plus, 'enqueue_scripts'   ) );
	remove_action( 'admin_head',            array( $coauthors_plus, 'js_vars'           ) );

	add_action( 'add_meta_boxes',              'CoAuthorsPlusRoles\add_meta_boxes'         );
	add_action( 'admin_enqueue_scripts',       'CoAuthorsPlusRoles\enqueue_scripts'        );
	add_action( 'admin_print_footer_scripts',  'CoAuthorsPlusRoles\coauthor_select_dialog' );
}

// Right after the CAP admin_init action runs:
add_action( 'admin_init', 'CoAuthorsPlusRoles\admin_init', 11 );


/*
 * Add the meta box for this plugin. Should respect the filters in CAP
 * regarding context and priority.
 */
function add_meta_boxes() {
	global $coauthors_plus;

	if ( empty( $coauthors_plus ) ) {
		return;
	}

	if ( $coauthors_plus->is_post_type_enabled() &&
			$coauthors_plus->current_user_can_set_authors() ) {
		add_meta_box(
			'coauthorsrolesdiv',
			__( 'Authors', 'co-authors-plus-roles' ),
			'CoAuthorsPlusRoles\coauthors_meta_box',
			get_post_type(),
			apply_filters( 'coauthors_meta_box_context', 'normal' ),
			apply_filters( 'coauthors_meta_box_priority', 'high' )
		);
	}
}


/**
 * Outputs the HTML markup for the Authors meta box.
 *
 *
 */
function coauthors_meta_box( $post ) {
	global $post, $coauthors_plus, $current_screen;

	/*
	 * For compatability with Co-Authors Plus, this plugin should set the default author on a new post
	 * or auto-draft in the same way as CAP does. This bit of copypasta from that plugin assures that it does.
	 */
	$post_id = $post->ID;
	$default_user = apply_filters( 'coauthors_default_author', wp_get_current_user() );

	// Roles available can be filtered, by post type for example...
	$roles_available = apply_filters( 'coauthors_author_roles', get_author_roles(), $post_id );

	// -- COPYPASTA from Co-Authors Plus:
	// $post_id and $post->post_author are always set when a new post is created due to auto draft,
	// and the else case below was always able to properly assign users based on wp_posts.post_author,
	// but that's not possible with force_guest_authors = true.
	if ( ! $post_id || $post_id == 0
			|| ( ! $post->post_author && ! $coauthors_plus->force_guest_authors )
			|| ( $current_screen->base == 'post' && $current_screen->action == 'add' ) ) {
		$coauthors = array();
		// If guest authors is enabled, try to find a guest author attached to this user ID
		if ( $coauthors_plus->is_guest_authors_enabled() ) {
			$coauthor = $coauthors_plus->guest_authors->get_guest_author_by( 'linked_account', $default_user->user_login );
			if ( $coauthor ) {
				$coauthors[] = $coauthor;
			}
		}
		// If the above block was skipped, or if it failed to find a guest author, use the current
		// logged in user, so long as force_guest_authors is false. If force_guest_authors = true, we are
		// OK with having an empty authoring box.
		if ( ! $coauthors_plus->force_guest_authors && empty( $coauthors ) ) {
			if ( is_array( $default_user ) ) {
				$coauthors = $default_user;
			} else {
				$coauthors[] = $default_user;
			}
		}

	} else {
		$coauthors = get_coauthors( $post_id, array( 'author_role' => 'any' ) );
	}
	// -- end copypasta

	// Extend the coauthors as returned from Co-Authors Plus with the 'author_role' field, as it won't
	// have that field if the author hasn't been saved yet.
	$coauthors = array_map(
		function($author) use ( $post_id ) {
			if ( ! isset( $author->author_role ) ) {
				$author->author_role = apply_filters( 'coauthors_default_role', '', $author, $post_id );
			}
			return $author;
		}, $coauthors );

	echo '<p>' .
		sprintf(
			esc_html__( 'Click on an author to change them. Drag to change their order. Click on %s to remove them.', 'co-authors-plus-roles' ),
			'<b>' . esc_html__( 'Remove', 'co-authors-plus-roles' ) . '</b>'
		) . '</p>';

	wp_nonce_field( 'coauthors_save', 'edit_coauthorsplus_roles_nonce' );

	echo '<ul id="coauthors-select-list" class="ui-sortable">';

	if ( $coauthors ) {
		foreach ( $coauthors as $coauthor ) {
			template_coauthor_sortable( $coauthor );
		}
	}

	echo '</ul>';

	echo '<h4><a class="hide-if-no-js" href="#coauthor-add" id="coauthor-add-toggle">'
		. esc_html__( '+ Add New Author', 'co-authors-plus-roles' ) . '</a></h4>';

}


/**
 * Output the sortable <li> for the coauthor meta box.
 *
 * Abstracted to its own function because the same markup needs to be returned
 * from admin-ajax when a new author is added to a post.
 *
 * @param object $coauthor Coauthor to return. Should be extended with the author_role attribute.
 * @param string $author_role Optional. If adding a new coauthor to a post, it won't already have this.
 *                                 Passing this second parameter will set it on the coauthor.
 */
function template_coauthor_sortable( $coauthor, $author_role = null ) {
	if ( $author_role ) {
		$coauthor->author_role = $author_role;
	}

	if ( ! isset( $coauthor->type ) ) {
		$coauthor->type = 'WP USER';
	}

	if ( ! isset( $coauthor->author_role ) ) {
		$coauthor->author_role = '';
	}

	// The format in which these values are posted.
	$coauthor_input_value = "{$coauthor->user_nicename}|||{$coauthor->author_role}";
	?>
	<li id="menu-item-<?php echo esc_attr( $coauthor->user_nicename ); ?>" class="menu-item coauthor-sortable">
		<dl class="menu-item-bar">
			<dt class="menu-item-handle">
				<span class="author-avatar">
					<?php echo get_avatar( $coauthor->user_email, 48 ); ?>
				</span>
				<span class="author-info sortable-flex-section">
					<span class="author-name"><?php echo esc_html( $coauthor->display_name ); ?></span>
					<span class="author-email"><?php echo esc_html( $coauthor->user_email ); ?></span>
				</span>
				<?php
					/*
					 * The special case where `author_role` is empty should be handled separately.
					 * An empty role means that its treated as a "byline". We give it a name here for display purposes,
					 * but this name is never saved.
					 */
					$author_role = ( ! empty( $coauthor->author_role ) ) ?
								$coauthor->author_role : esc_html__( 'byline', 'co-authors-plus-roles' ); ?>
				<span class="author-role sortable-flex-section">
					<a class="edit-coauthor"
						data-author-name="<?php echo esc_attr( $coauthor->display_name ); ?>"
						data-author-type="<?php echo esc_attr( $coauthor->type ); ?>"
						data-author-email="<?php echo esc_attr( $coauthor->user_email ); ?>"
						data-role="<?php echo esc_attr( $coauthor->author_role ); ?>"
						data-author-nicename="<?php echo esc_attr( $coauthor->user_nicename ); ?>"
						title="<?php echo esc_attr__( 'Change the role which this coauthor is assigned to', 'co-authors-plus-roles' ); ?>"
						><?php echo esc_html( $author_role ); ?></a>
				</span>
				<span class="author-controls sortable-flex-section">
					<span class="publishing-actions">
						<a class="remove-coauthor submitdelete deletion"
							title="<?php echo esc_attr__( 'Remove this coauthor from the post', 'co-authors-plus-roles' ); ?>"
							><?php esc_attr_e( 'Remove', 'co-authors-plus-roles' ); ?></a>
					</span>
				</span>
				<input type="hidden" name="coauthors[]" value="<?php echo esc_attr( $coauthor_input_value ); ?>" />
			</dt>
		</dl>
	</li>
	<?php
}


/**
 * Respond to a selected coauthor in modal window.
 *
 * Output the HTML markup for an <li> to add to the sortable list in the
 * Co-Authors meta box.
 */
function ajax_template_coauthor_sortable() {
	check_ajax_referer( 'coauthor-select', '_ajax_coauthor_template_nonce' );
	global $coauthors_plus;

	$coauthor = $coauthors_plus->get_coauthor_by( 'user_nicename', sanitize_text_field( $_REQUEST['authorNicename'] ) );
	$role = get_author_role( sanitize_text_field( $_REQUEST['authorRole'] ) );

	if ( ! $coauthor ) {
		wp_send_json_error( 'Missing required information.' );
	}

	if ( is_object( $role ) ) {
		$role = $role->slug;
	}

	ob_start();
	template_coauthor_sortable( $coauthor, $role );
	$sortable_markup = ob_get_clean();

	wp_send_json_success( $sortable_markup );
}

add_action( 'wp_ajax_coauthor-sortable-template', 'CoAuthorsPlusRoles\ajax_template_coauthor_sortable' );


/**
 * Initialize admin UI styles and scripts.
 *
 */
function enqueue_scripts() {
	wp_enqueue_style( 'coauthor-select', \plugins_url( 'css/admin-ui.css', __FILE__ ) );
	wp_enqueue_script( 'coauthor-select', \plugins_url( 'js/coauthors.js', __FILE__ ),
		array( 'jquery', 'jquery-ui-core', 'jquery-ui-sortable' ), false, true
	);
	wp_localize_script( 'coauthor-select', 'coauthorsL10n',
		array(
			'title' => __( 'Insert/edit author', 'co-authors-plus-roles' ),
			'update' => __( 'Update', 'co-authors-plus-roles' ),
			'save' => __( 'Add Author', 'co-authors-plus-roles' ),
			'noMatchesFound' => __( 'No results found.', 'co-authors-plus-roles' ),
			'addNewAuthorHeader' => __( 'Add new author to post', 'co-authors-plus-roles' ),
			'editExistingAuthorHeader' => __( 'Edit author on post', 'co-authors-plus-roles' ),
			'addNewAuthorButton' => __( 'Add author to post', 'co-authors-plus-roles' ),
			'editExistingAuthorButton' => __( 'Save changes to author', 'co-authors-plus-roles' )
		)
	);
}


function coauthor_select_dialog() {
	global $post_ID, $post;

	$post_id = $post_ID;
	if ( ! isset( $post_id ) && isset( $post ) ) {
		$post_id = $post->ID;
	}

		?>
	<div id="coauthor-select-backdrop" style="display: none"></div>
	<div id="coauthor-select-wrap" class="wp-core-ui" style="display: none">
		<form id="coauthor-select-contributor" tabindex="-1">
		<?php wp_nonce_field( 'coauthor-select', '_coauthor_select_nonce', false ); ?>
		<input type="hidden" id="coauthor-post-id" value="<?php echo $post_id; ?>" />
			<div id="coauthor-select-modal-title">
				<span id="coauthor-select-header"></span>
				<button type="button" id="coauthor-select-close">
					<span class="screen-reader-text"><?php esc_html_e( 'Close', 'co-authors-plus-roles' ); ?></span>
				</button>
			</div>
			<div id="coauthor-select">
				<input type="hidden" id="coauthor-author-nicename" value="" />
				<div id="coauthor-search-panel">
					<div class="coauthor-search-wrapper">
						<label>
							<p class="howto"><?php esc_html_e( 'Search by name or email address:', 'co-authors-plus-roles' ); ?></p>
							<input type="search" id="coauthor-search-field" class="coauthor-search-field" autocomplete="off" />
							<span class="spinner"></span>
						</label>
					</div>
					<div id="search-results" class="query-results" tabindex="0">
						<ul></ul>
						<div class="river-waiting">
							<span class="spinner"></span>
						</div>
					</div>
					<div id="most-recent-results" class="query-results" tabindex="0">
						<div class="query-notice" id="query-notice-message">
							<em class="query-notice-default"><?php esc_html_e( 'No search term specified. Showing most used authors.', 'co-authors-plus-roles' ); ?></em>
							<em class="query-notice-hint screen-reader-text"><?php esc_html_e( 'Search or use up and down arrow keys to select an author.', 'co-authors-plus-roles' ); ?></em>
						</div>
						<ul></ul>
						<div class="river-waiting">
							<span class="spinner"></span>
						</div>
					</div>
				</div>
			</div>
			<?php $roles_available = apply_filters( 'coauthors_author_roles', get_author_roles(), $post_id );
					$options = array( '' => __( 'Choose a role', 'co-authors-plus-roles' ) );
					foreach( $roles_available as $role ) {
						$options[ $role->slug ] = $role->name;
					}
					$options = apply_filters( 'coauthors_role_options', $options, $post_id );
					if ( ! empty( $options ) ) : ?>
			<div id="coauthor-options">
				<p class="howto"><?php esc_html_e( 'Choose the role for this contributor:', 'co-authors-plus-roles' ); ?></p>
				<select id="coauthor-select-role" name="coauthor-select-role">
					<?php foreach ( $options as $value => $label ) {
						echo '<option value="' . esc_attr( $value ) . '">' . esc_html( $label ) . '</option>';
					} ?>
				</select>
			</div>
			<?php endif; ?>
			<div class="submitbox">
				<div id="coauthor-select-cancel">
					<a class="submitdelete deletion" href="#"><?php esc_html_e( 'Cancel', 'co-authors-plus-roles' ); ?></a>
				</div>
				<div id="coauthor-select-update">
					<input type="submit" value="<?php esc_attr_e( 'Add coauthor to post', 'co-authors-plus-roles' ); ?>" class="button button-primary" id="coauthor-select-submit" name="coauthor-select-submit">
				</div>
			</div>
		</form>
		</div>
	<?php

}


/**
 * Populate the coauthors search river through Ajax.
 *
 * Finds all coauthors available on a site who match a given string, in either
 * name, slug, or email address. Called on keyup from the coauthors search
 * field in the modal.
 *
 * If no search term is specified, returns the most frequent contributors on a
 * blog. This is used to populate the initial list shown in the modal.
 *
 * If the optional $post_ID parameter present, the values returned will exclude
 * authors on the current post.
 *
 * @param string $search_term String to search for. Can be empty.
 * @param args Query-style args to modify the search:
 *              'post_ID' (int) The post being searched on. Authors from this post will be excluded
 *              'exclude' (array) A list of authors (nicenames) to exclude. If present, overrides the post_id parameter
 */
function search_coauthors( $search_term, $args = array() ) {
	global $coauthors_plus;

	$defaults = array(
		'post_ID' => null,
		'exclude' => null,
	);

	$args = wp_parse_args( $args, $defaults );

	if ( isset( $search_term ) && $search_term ) {
		$coauthors = $coauthors_plus->search_authors( $search_term );
	} else {
		$coauthors = get_top_authors();
	}

	$existing_authors = array();

	if ( isset( $args['post_ID'] ) && intval( $args['post_ID'] ) > 0 ) {
		$existing_authors = get_coauthors( $args['post_ID'], array( 'author_role' => 'any' ) );

	}

	if ( isset( $args['exclude'] ) && count( $args['exclude'] ) > 0 ) {

		// We're expecting a flat array of nicenames... Turn that into an array
		// of guest author objects by mapping get_coauthor_by over it.
		$existing_authors = array_map( function($author) {
			global $coauthors_plus;
			return $coauthors_plus->get_coauthor_by( 'user_nicename', $author );
		}, $args['exclude'] );

	}

	/* Removing the below code allows you to select the same author more than once */
	/* if ( count( $existing_authors ) ) {
		// Remove array elements from $coauthors that are identical to existing authors.
		/*$coauthors = array_udiff( $coauthors, $existing_authors,
			function( $author, $existing ) {
				return strcmp( $author->user_nicename, $existing->user_nicename );
			}
		);
	}*/

	return $coauthors;
}


/**
 * Responds to the ajax endpoint to search for coauthors.
 *
 * Validates nonce, sanitizes inputs and calls search_coauthors().
 *
 * @uses CoAuthorsRolesPlus\search_coauthors()
 */
function ajax_search_coauthors() {
	check_ajax_referer( 'coauthor-select', '_ajax_coauthor_search_nonce' );

	$search = isset( $_REQUEST['search'] ) ?
		sanitize_text_field( $_REQUEST['search'] ) : false;
	$post_ID = isset( $_REQUEST['postId'] ) ?
		intval( $_REQUEST['postId'] ) : null;
	$exclude_authors = isset( $_REQUEST['exclude'] ) ?
		array_map( 'sanitize_text_field', $_REQUEST['exclude'] ) : null;

	$coauthors = search_coauthors( $search,
		array(
			'post_ID' => $post_ID,
			'exclude' => $exclude_authors
		)
	);

	$paginate = intval( $_REQUEST['page'] );
	$results_per_page = 20;

	$coauthors = array_slice( $coauthors, ( $paginate -1 ) * $results_per_page, $results_per_page );

	if ( $coauthors ) {
		wp_send_json_success( array_values( $coauthors ) );
	} else {
		wp_send_json_error( 'No authors were found.' );
	}

	die(0);
}

add_action( 'wp_ajax_coauthor-select-ajax', 'CoAuthorsPlusRoles\ajax_search_coauthors' );


/**
 * Get the most prolific authors on a site.
 *
 * Used to populate the suggestion list when first opening the "Add Co-Author"
 * modal box. NOTE: Only queries the "guest author" taxonomy, and uses the
 * count of byline roles, not all contributions.
 */
function get_top_authors() {
	global $coauthors_plus;

	$all_published_authors = get_terms(
		$coauthors_plus->coauthor_taxonomy,
		array(
			'orderby' => 'count',
			'order' => 'DESC',
			'hide_empty' => false,
			'fields' => 'id=>slug'
		)
	);

	if ( ! $all_published_authors ) {
		return false;
	}

	$coauthors = array();

	foreach ( $all_published_authors as $author_term ) {
		$coauthors[] = $coauthors_plus->get_coauthor_by( 'user_nicename', $author_term );
	}

	// Because coauthors can include duplicates (in the case of linked accounts), uniq it first.
	return array_unique( $coauthors, SORT_REGULAR );
}


/**
 * Update the co-authors on a post on saving.
 *
 * @param int $post_ID
 * @param array $new_coauthors Array of strings in the format "{nicename}|||{role}"
 *                              as posted by the sortables in the meta box.
 */
function update_coauthors_on_post( $post_id, $new_coauthors ) {
	global $coauthors_plus;

	$post = get_post( $post_id );
	if ( ! $coauthors_plus->is_post_type_enabled( $post->post_type ) ) {
		return;
	}

	if ( $new_coauthors && is_array( $new_coauthors ) ) {

		// Convert the new coauthors array from the string format used on inputs from the post edit
		// screen into proper objects which can be compared with existing coauthors.
		$new_coauthors = array_filter(
			array_map(
				function( $coauthor_string ) {
					global $coauthors_plus;

					list( $author_name, $role ) = explode( '|||', $coauthor_string );

					// Empty roles can come in either as the string "byline",
					// or as an empty string. Handle them both the same.
					if ( $role === 'byline' ) {
						$role = '';
					}

					$new_coauthor = $coauthors_plus->get_coauthor_by( 'user_nicename', sanitize_text_field( $author_name ) );
					if ( $new_coauthor ) {
						$new_coauthor->author_role = sanitize_text_field( $role );
						return $new_coauthor;
					}
				}, $new_coauthors
			)
		);

		// Diff new data against the already added coauthors. If they aren't identical, wipe the old postmeta and re-add.
		$existing_coauthors = get_coauthors( $post_id, array( 'author_role' => 'any' ) );
		

		$difference = false;
		for ( $i=0; $i<=max( count( $new_coauthors ), count( $existing_coauthors ) ); $i++ ) {
			if ( ! isset( $new_coauthors[$i] ) || !isset( $existing_coauthors[ $i ] ) ) {
				$difference = true; break;
			}
			if ( $new_coauthors[ $i ]->user_nicename !== $existing_coauthors[ $i ]->user_nicename ) {
				$difference = true; break;
			}
			if ( $new_coauthors[ $i ]->author_role !== $existing_coauthors[ $i ]->author_role ) {
				$difference = true; break;
			}
		}

		if ( $difference ) {
			remove_all_coauthor_meta( $post_id );
			foreach ( $new_coauthors as $new_coauthor ) {
				set_author_on_post( $post_id, $new_coauthor, $new_coauthor->author_role );
			}
		}

		// Use $coauthors_plus->set_coauthors to update byline roles. This clears the existing terms and
		// re-adds them in the correct order.
		$new_byline_coauthors = array_filter(
			$new_coauthors,
			function( $author ) {
				return ( empty( $author->author_role ) || in_array( $author->author_role, byline_roles() ) );
			}
		);

		$byline_coauthors_slugs = wp_list_pluck( $new_byline_coauthors, 'user_nicename' );

		$coauthors_plus->add_coauthors( $post_id, $byline_coauthors_slugs, false );
	}
}


/**
 * Update the co-authors on a post on saving.
 *
 */
function action_update_coauthors_on_post( $post_id, $post ) {
	global $coauthors_plus;

	if ( ! $post_id && isset( $_POST['post_ID'] ) ) {
		$post_id = intval( $_POST['post_ID'] );
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( empty( $_POST['coauthors'] ) ) {
		return;
	}

	if ( $coauthors_plus->current_user_can_set_authors( $post ) ) {
		// if current_user_can_set_authors and nonce valid
		check_admin_referer( 'coauthors_save', 'edit_coauthorsplus_roles_nonce' );

		$coauthors = array_map( 'sanitize_text_field', (array) $_POST['coauthors'] );

		update_coauthors_on_post( $post_id, $coauthors );

	} else {
		// If the user can't set authors and a co-author isn't currently set,
		// we need to explicitly set one
		if ( ! $coauthors_plus->has_author_terms( $post_id ) ) {
			$user = get_userdata( $post->post_author );

			if ( $user ) {
				set_contributor_on_post( $post_id, $user->user_login );
			}
		}
	}
}

add_action( 'save_post', 'CoAuthorsPlusRoles\action_update_coauthors_on_post', 100, 2 );
