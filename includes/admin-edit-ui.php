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

	if ( $coauthors_plus->is_post_type_enabled() &&
			$coauthors_plus->current_user_can_set_authors() ) {
		add_meta_box(
			'coauthorsrolesdiv',
			__( 'Authors', 'co-authors-plus' ),
			'CoAuthorsPlusRoles\coauthors_meta_box',
			get_post_type(),
			apply_filters( 'coauthors_meta_box_context', 'normal' ),
			apply_filters( 'coauthors_meta_box_priority', 'high' )
		);
	}
}


/**
 * Outputs the HTML markup for the Contributors meta box.
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
		$coauthors = get_coauthors( null, array( 'author_role' => 'any' ) );
	}
	// -- end copypasta

	echo '<h2 style="margin-bottom:0">' . __( 'Credits', 'co-authors-plus' ) . '</h2>';
	echo '<p>' . __( 'Click on an author to change them. Drag to change their order. Click on <b>Remove</b> to remove them.', 'co-authors-plus' ) . '</p>';

	wp_nonce_field( 'coauthors_save', 'edit_coauthorsplus_roles_nonce' );

	echo '<ul id="coauthors-select-list" class="ui-sortable">';

	if ( $coauthors ) {
		foreach ( $coauthors as $coauthor ) {
			template_coauthor_sortable( $coauthor );
		}
	}

	echo '</ul>';

	echo '<h4><a class="hide-if-no-js" href="#coauthor-add" id="coauthor-add-toggle">'
		. __( '+ Add New Contributor', 'co-authors-plus' ) . '</a></h4>';

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
	if ( $author_role )
		$coauthor->author_role = $author_role;

	if ( ! isset( $coauthor->type ) )
		$coauthor->type = 'WP USER';

	// The format in which these values are posted.
	$coauthor_input_value = "{$coauthor->ID}|||{$coauthor->author_role}";
	?>
	<li id="menu-item-<?php echo $coauthor->ID; ?>" class="menu-item coauthor-sortable">
		<dl class="menu-item-bar">
			<dt class="menu-item-handle">
				<span class="author-avatar">
					<?php echo get_avatar( $coauthor->user_email, 48 ); ?>
				</span>
				<span class="author-info sortable-flex-section">
					<span class="author-name"><?php echo $coauthor->display_name; ?></span>
					<span class="author-email"><?php echo $coauthor->user_email; ?></span>
				</span>
				<?php $author_role = ( isset( $coauthor->author_role ) ) ?
										$coauthor->author_role : 'BYLINE'; ?>
				<span class="author-role sortable-flex-section">
					<a class="edit-coauthor"><?php echo $author_role; ?></a>
				</span>
				<span class="author-controls sortable-flex-section">
					<span class="publishing-actions">
						<a class="submitdelete deletiion" href="#delete-<?php echo $coauthor->ID; ?>"><?php _e( 'Remove' ); ?></a>
					</span>
				</span>
				<input type="hidden" name="coauthors[]" value="<?php echo $coauthor_input_value; ?>" />
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

	$coauthor = $coauthors_plus->get_coauthor_by( 'id', intval( $_REQUEST['authorId'] ) );
	$role = get_author_role( $_REQUEST['authorRole'] );

	if ( ! $coauthor || ! $role )
		wp_die( 'Missing required information.' );

	$coauthor->author_role = $role->slug;

	template_coauthor_sortable( $coauthor );
	die(0);
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
			'title' => __( 'Insert/edit contributor', 'co-authors-plus-roles' ),
			'update' => __( 'Update' ),
			'save' => __( 'Add Contributor', 'co-authors-plus-roles' ),
			'noMatchesFound' => __( 'No results found.' )
		)
	);
}


function coauthor_select_dialog() {
	global $post_ID, $post;

	$post_id = $post_ID;
	if ( ! isset( $post_id ) && isset( $post ) )
		$post_id = $post->ID;

		?>
	<div id="coauthor-select-backdrop" style="display: none"></div>
	<div id="coauthor-select-wrap" class="wp-core-ui" style="display: none">
		<form id="coauthor-select-contributor" tabindex="-1">
		<?php wp_nonce_field( 'coauthor-select', '_coauthor_select_nonce', false ); ?>
			<div id="coauthor-select-modal-title">
				<?php _e( 'Add new contributor to post', 'coauthors-plus-roles' ) ?>
				<button type="button" id="coauthor-select-close">
					<span class="screen-reader-text"><?php _e( 'Close' ); ?></span>
				</button>
			</div>
			<div id="coauthor-select">
				<div id="coauthor-options">
					<p class="howto"><?php _e( 'Choose the role for this contributor:', 'coauthors-plus-roles' ); ?></p>

					<select id="coauthor-select-role" name="coauthor-select-role">
						<option value=""><?php _e( 'Choose a role', 'coauthors-plus-roles' ); ?></option>
					<?php $roles_available = apply_filters( 'coauthors_author_roles', get_author_roles(), $post_id );
						foreach ( $roles_available as $role ) {
							echo '<option value="' . $role->slug . '">' . $role->name . '</option>';
						}
					?>
					</select>
					<input type="hidden" id="coauthor-author-id" value="" />
				</div>
				<div id="coauthor-search-panel">
					<div class="coauthor-search-wrapper">
						<label>
							<p class="howto"><?php _e( 'Search by name or email address:', 'coauthors-plus-roles' ); ?></p>
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
							<em class="query-notice-default"><?php _e( 'No search term specified. Showing recent items.' ); ?></em>
							<em class="query-notice-hint screen-reader-text"><?php _e( 'Search or use up and down arrow keys to select an item.' ); ?></em>
						</div>
						<ul></ul>
						<div class="river-waiting">
							<span class="spinner"></span>
						</div>
					</div>
				</div>
			</div>
			<div class="submitbox">
				<div id="coauthor-select-cancel">
					<a class="submitdelete deletion" href="#"><?php _e( 'Cancel' ); ?></a>
				</div>
				<div id="coauthor-select-update">
					<input type="submit" value="<?php esc_attr_e( 'Add Link' ); ?>" class="button button-primary" id="coauthor-select-submit" name="coauthor-select-submit">
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
 * @param integer $post_ID The post being searched on.
 */
function search_coauthors( $search_term, $post_ID ) {
	global $coauthors_plus;

	if ( isset( $search_term ) )
		$coauthors = $coauthors_plus->search_authors( $search_term );
	else
		$coauthors = get_top_authors();

	if ( isset( $post_ID ) && $post_ID ) {
		$existing = get_coauthors( $post_ID );

		// XXX: I suspect, but haven't measured, that this comparator function
		// is ridiculously slow. Investigate refactoring.
		$coauthors = array_udiff( $coauthors, $existing,
			function( $author, $existing ) {
				return $author->ID === $existing->ID;
			}
		);
	}

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
	$coauthors = search_coauthors( sanitize_text_field( $_REQUEST['search'] ), intval( $_POST['post_ID'] ) );

	if ( $coauthors )
		wp_send_json( array_values( $coauthors ) );
	else
		wp_send_json_error( 'No authors were found.' );

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

	if ( ! $all_published_authors )
		return false;

	$coauthors = array();

	foreach ( $all_published_authors as $author_term )
		$coauthors[] = $coauthors_plus->get_coauthor_by( 'user_nicename', $author_term );

	// Because coauthors can include duplicates (in the case of linked accounts), uniq it first.
	return array_unique( $coauthors, SORT_REGULAR );
}


/**
 * Update the co-authors on a post on saving.
 *
 */
function update_coauthors_on_post( $post_id, $post ) {
	global $coauthors_plus;

	if ( ! $post_id && isset( $_POST['post_ID'] ) )
		$post_id = intval( $_POST['post_ID'] );

	if ( defined( 'DOING_AUTOSAVE' ) && ! DOING_AUTOSAVE )
		return;

	if ( ! $coauthors_plus->is_post_type_enabled( $post->post_type ) )
		return;

	if ( ! isset( $POST['coauthors_save'] ) )
		return false;

	if ( $coauthors_plus->current_user_can_set_authors( $post ) ) {
		// if current_user_can_set_authors and nonce valid
		check_admin_referer( 'coauthors_save', 'edit_coauthorsplus_roles_nonce' );

		$coauthors = (array) $_POST['coauthors'];

		if ( $coauthors && is_array( $coauthors ) ) {
			foreach ( $coauthors as $coauthor ) {

				// Parse and sanitize terms. `set_contributor_on_post` does
				// some type checking of its parameters, so we coerce the
				// posted string into the expected types here.
				list( $author, $role ) = explode( '|||', $coauthor );
				$author = intval( $author );
				$role = get_author_role( $role );

				set_contributor_on_post( $post_id, $author, $role );
			}
		}

	} else {
		// If the user can't set authors and a co-author isn't currently set,
		// we need to explicity set one
		if ( ! $coauthors_plus->has_author_terms( $post_id ) ) {
			$user = get_userdata( $post->post_author );

			if ( $user )
				set_contributor_on_post( $post_id, $user->user_login );
		}
	}
}

add_action( 'save_post', 'CoAuthorsPlusRoles\update_coauthors_on_post', 10, 2 );
