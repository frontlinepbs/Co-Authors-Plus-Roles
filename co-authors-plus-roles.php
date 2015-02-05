<?php
/*
Plugin Name: Co-authors Plus - Roles
Plugin URI: http://github.com/goldenapples/co-authors-plus-roles/
Description: Extends the Co-Authors Plus plugin with extensible roles. Allows for adding contributors of multiple roles - "Additional reporting by", "Additional research by", or any other.
Version: 0.1-alpha
Author: Nathaniel Taintor
Text Domain: co-authors-plus-roles
Domain Path: /languages

GNU General Public License, Free Software Foundation <http://creativecommons.org/licenses/GPL/2.0/>
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

$co_authors_plus_roles = new CoAuthorsPlusRoles();

register_activation_hook( __FILE__, array( $co_authors_plus_roles, 'activate' ) );

class CoAuthorsPlusRoles {

	/**
	 * On activation, check that Co-Authors Plus is installed and activated.
	 * This plugin depends on some of the data structures introduced in CA+.
	 */
	public function activate() {
		global $coauthors_plus;

		/*
		 * If Co-Authors Plus is not active, show a warning message and bail.
		 */
		if ( ! isset( $coauthors_plus ) ) {
			update_option( 'co_authors_plus_roles__notices',
				array(
					'class' => 'error',
					'message' => __( 'You must have Co-Authors Plus installed and activated first in order to use this plugin.' )
				)
			);
			deactivate_plugins( __FILE__ );

		}
	}

	/**
	 * Show a message explaining why we're not activating this plugin.
	 *
	 * @return void
	 */
	public function admin_notices() {
		$notices = get_option( 'co_authors_plus_roles__notices' );

		if ( $notices && is_array( $notices ) ) {
			echo '
				<div id="message" class="' . $notices['class'] . '">
					<p>' . $notices['message'] . '</p>
				</div>';
			delete_option( 'co_authors_plus_roles__notices' );
		}
	}
}

add_action( 'admin_notices', array( $co_authors_plus_roles, 'admin_notices' ) );


require( 'includes/query.php' );
require( 'includes/contributor-roles.php' );
require( 'includes/default-contributor-roles.php' );
require( 'includes/contributor-roles-posts-relationships.php' );
require( 'includes/admin-edit-ui.php' );

/**
 * The Public API for this plugin.
 *
 * All functions that should be available in the global namespace are listed here.
 * This would be done with `use` statements in PHP 5.6+.
 *
 */
function register_contributor_role( $slug, $args = array() ) {
	return CoAuthorsPlusRoles\register_contributor_role( $slug, $args );
}
function remove_contributor_role( $slug ) {
	return CoAuthorsPlusRoles\remove_contributor_role( $slug );
}
function modify_contributor_role( $slug, $args = array() ) {
	return CoAuthorsPlusRoles\modify_contributor_role( $slug, $args );
}
function get_contributor_roles() {
	return CoAuthorsPlusRoles\get_contributor_roles();
}



