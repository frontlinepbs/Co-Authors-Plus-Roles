<?php
global $_tests_dir;

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) $_tests_dir = '/tmp/wordpress-tests-lib';

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
	global $_tests_dir;
	require $_tests_dir . '/wp-content/plugins/co-authors-plus/co-authors-plus.php';
	require dirname( __FILE__ ) . '/../../co-authors-plus-roles.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';

require dirname( __FILE__ ) . '/coauthorsplusroles-testcase.php';
