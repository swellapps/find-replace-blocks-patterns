<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FRBP_Admin_Page {

	public function register_menu(): void {
		add_submenu_page(
			'tools.php',
			__( 'Find & Replace Blocks & Patterns', 'find-replace-blocks-patterns' ),
			__( 'Find & Replace Blocks & Patterns', 'find-replace-blocks-patterns' ),
			'edit_others_posts',
			'find-replace-blocks-patterns',
			[ $this, 'render_page' ]
		);
	}

	public function render_page(): void {
		$post_types = $this->get_post_types();
		?>
		<div class="wrap frbp-wrap">
			<h1><?php esc_html_e( 'Find & Replace Blocks & Patterns', 'find-replace-blocks-patterns' ); ?></h1>
			<p class="description"><?php esc_html_e( 'Paste raw Gutenberg block or pattern markup in the fields below. Select which post types to search, run a preview to confirm matches, then execute the replacement.', 'find-replace-blocks-patterns' ); ?></p>

			<div class="frbp-form-wrapper">
				<div class="frbp-fields">
					<div class="frbp-field">
						<label for="frbp-find"><strong><?php esc_html_e( 'Find', 'find-replace-blocks-patterns' ); ?></strong></label>
						<textarea id="frbp-find" rows="10" placeholder="<?php esc_attr_e( 'Paste the block or pattern markup to find\u2026', 'find-replace-blocks-patterns' ); ?>"></textarea>
					</div>
					<div class="frbp-field">
						<label for="frbp-replace"><strong><?php esc_html_e( 'Replace', 'find-replace-blocks-patterns' ); ?></strong></label>
						<textarea id="frbp-replace" rows="10" placeholder="<?php esc_attr_e( 'Paste the replacement block or pattern markup\u2026', 'find-replace-blocks-patterns' ); ?>"></textarea>
					</div>
				</div>

				<div class="frbp-post-types">
					<strong><?php esc_html_e( 'Post Types', 'find-replace-blocks-patterns' ); ?></strong>
					<ul>
						<?php foreach ( $post_types as $slug => $label ) : ?>
							<li>
								<label>
									<input type="checkbox" class="frbp-post-type" value="<?php echo esc_attr( $slug ); ?>" checked>
									<?php echo esc_html( $label ); ?>
									<span class="frbp-slug">(<?php echo esc_html( $slug ); ?>)</span>
								</label>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>

				<div class="frbp-actions">
					<button id="frbp-preview-btn" class="button button-secondary"><?php esc_html_e( 'Preview Matches', 'find-replace-blocks-patterns' ); ?></button>
					<button id="frbp-execute-btn" class="button button-primary" disabled><?php esc_html_e( 'Execute Replace', 'find-replace-blocks-patterns' ); ?></button>
					<span id="frbp-spinner" class="spinner"></span>
				</div>
			</div>

			<div id="frbp-results" class="frbp-results" style="display:none;">
				<h2><?php esc_html_e( 'Preview Results', 'find-replace-blocks-patterns' ); ?></h2>
				<div id="frbp-results-inner"></div>
				<div id="frbp-execute-confirm" style="display:none;">
					<p class="frbp-confirm-message">
						<strong><?php esc_html_e( 'Ready to execute.', 'find-replace-blocks-patterns' ); ?></strong>
						<?php esc_html_e( 'WordPress revisions will be saved before changes are applied where the post type supports them (indicated in the table above). Use the post editor\'s Revisions panel to revert if needed.', 'find-replace-blocks-patterns' ); ?>
					</p>
				</div>
			</div>

			<div id="frbp-notice" class="frbp-notice" style="display:none;"></div>
		</div>
		<?php
	}

	public function ajax_preview(): void {
		check_ajax_referer( 'frbp_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to perform this action.', 'find-replace-blocks-patterns' ) ] );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Raw block markup must be preserved exactly; any HTML sanitizer would corrupt it.
		$find       = wp_unslash( (string) ( $_POST['find'] ?? '' ) );
		$post_types = $this->sanitize_post_types( wp_unslash( (array) ( $_POST['post_types'] ?? [] ) ) );

		if ( $find === '' ) {
			wp_send_json_error( [ 'message' => __( 'The Find field cannot be empty.', 'find-replace-blocks-patterns' ) ] );
		}

		if ( empty( $post_types ) ) {
			wp_send_json_error( [ 'message' => __( 'Please select at least one post type.', 'find-replace-blocks-patterns' ) ] );
		}

		$engine  = new FRBP_Find_Replace();
		$matches = $engine->preview( $find, $post_types );

		wp_send_json_success( [ 'matches' => $matches ] );
	}

	public function ajax_execute(): void {
		check_ajax_referer( 'frbp_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to perform this action.', 'find-replace-blocks-patterns' ) ] );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Raw block markup must be preserved exactly; any HTML sanitizer would corrupt it.
		$find       = wp_unslash( (string) ( $_POST['find'] ?? '' ) );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Raw block markup must be preserved exactly; any HTML sanitizer would corrupt it.
		$replace    = wp_unslash( (string) ( $_POST['replace'] ?? '' ) );
		$post_types = $this->sanitize_post_types( wp_unslash( (array) ( $_POST['post_types'] ?? [] ) ) );

		if ( $find === '' ) {
			wp_send_json_error( [ 'message' => __( 'The Find field cannot be empty.', 'find-replace-blocks-patterns' ) ] );
		}

		if ( empty( $post_types ) ) {
			wp_send_json_error( [ 'message' => __( 'Please select at least one post type.', 'find-replace-blocks-patterns' ) ] );
		}

		$engine = new FRBP_Find_Replace();
		$count  = $engine->execute( $find, $replace, $post_types );

		wp_send_json_success( [
			'count'   => $count,
			'message' => sprintf(
				/* translators: %d = number of posts updated */
				_n(
					'Done. %d post was updated. Revisions were saved — use the post editor to revert if needed.',
					'Done. %d posts were updated. Revisions were saved — use the post editor to revert if needed.',
					$count,
					'find-replace-blocks-patterns'
				),
				$count
			),
		] );
	}

	private function sanitize_post_types( $raw ): array {
		if ( ! is_array( $raw ) ) {
			return [];
		}
		$valid = array_keys( $this->get_post_types() );
		return array_values( array_intersect( array_map( 'sanitize_key', $raw ), $valid ) );
	}

	private function get_post_types(): array {
		$types  = get_post_types( [ 'public' => true ], 'objects' );
		$result = [];
		foreach ( $types as $slug => $obj ) {
			$result[ $slug ] = $obj->labels->singular_name ?: $slug;
		}
		return $result;
	}
}
