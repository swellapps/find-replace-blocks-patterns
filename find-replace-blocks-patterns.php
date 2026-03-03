<?php
/**
 * Plugin Name:       Find & Replace Blocks & Patterns
 * Plugin URI:        https://swellapps.ai/find-replace-blocks-patterns/
 * Description:       Bulk find and replace Gutenberg block or pattern markup across posts, with post-type filtering, dry-run preview, and revision-backed undo.
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Version:           1.2.0
 * Author:            Swell
 * Author URI:        https://swellapps.ai
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       find-replace-blocks-patterns
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FRBP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FRBP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FRBP_VERSION', '1.2.0' );

require_once FRBP_PLUGIN_DIR . 'includes/class-find-replace.php';
require_once FRBP_PLUGIN_DIR . 'includes/class-admin-page.php';

add_action( 'admin_menu', function () {
	( new FRBP_Admin_Page() )->register_menu();
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( $hook !== 'tools_page_find-replace-blocks-patterns' ) {
		return;
	}
	wp_enqueue_style( 'frbp-admin', FRBP_PLUGIN_URL . 'assets/css/admin.css', [], FRBP_VERSION );
	wp_enqueue_script( 'frbp-admin', FRBP_PLUGIN_URL . 'assets/js/admin.js', [ 'jquery' ], FRBP_VERSION, true );
	wp_localize_script( 'frbp-admin', 'frbpData', [
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'frbp_nonce' ),
		'i18n'    => [
			'findEmpty'       => __( 'The Find field cannot be empty.', 'find-replace-blocks-patterns' ),
			'noPostTypes'     => __( 'Please select at least one post type.', 'find-replace-blocks-patterns' ),
			'noMatches'       => __( 'No matches found across the selected post types.', 'find-replace-blocks-patterns' ),
			'errorOccurred'   => __( 'An error occurred.', 'find-replace-blocks-patterns' ),
			'requestFailed'   => __( 'Request failed. Please try again.', 'find-replace-blocks-patterns' ),
			'confirmExecute'  => __( 'Apply replacements to all matching posts? This action cannot be fully undone for post types that do not support revisions.', 'find-replace-blocks-patterns' ),
			/* translators: %d = number of matching posts */
			'foundSingular'   => __( 'Found %d post with matching content:', 'find-replace-blocks-patterns' ),
			/* translators: %d = number of matching posts */
			'foundPlural'     => __( 'Found %d posts with matching content:', 'find-replace-blocks-patterns' ),
			'colTitle'        => __( 'Post Title', 'find-replace-blocks-patterns' ),
			'colType'         => __( 'Post Type', 'find-replace-blocks-patterns' ),
			'colStatus'       => __( 'Status', 'find-replace-blocks-patterns' ),
			'colMatches'      => __( 'Matches', 'find-replace-blocks-patterns' ),
			'colRevisions'    => __( 'Revisions', 'find-replace-blocks-patterns' ),
			'colEdit'         => __( 'Edit', 'find-replace-blocks-patterns' ),
			'revisionYes'     => __( 'Revision will be saved', 'find-replace-blocks-patterns' ),
			'revisionNo'      => __( 'This post type does not support revisions \u2014 changes cannot be undone', 'find-replace-blocks-patterns' ),
			'revisionNoLabel' => __( '\u26a0 No', 'find-replace-blocks-patterns' ),
			'revisionWarning' => __( '\u26a0 Warning: One or more matched post types do not support revisions. Changes to those posts cannot be undone via the post editor.', 'find-replace-blocks-patterns' ),
			'editLink'        => __( 'Edit', 'find-replace-blocks-patterns' ),
		],
	] );
} );

add_action( 'wp_ajax_frbp_preview', function () {
	( new FRBP_Admin_Page() )->ajax_preview();
} );

add_action( 'wp_ajax_frbp_execute', function () {
	( new FRBP_Admin_Page() )->ajax_execute();
} );
