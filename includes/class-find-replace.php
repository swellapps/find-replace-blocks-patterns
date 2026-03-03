<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FRBP_Find_Replace {

	/**
	 * Find all posts containing the search string and return match metadata.
	 *
	 * @param string   $find       Raw block markup to search for.
	 * @param string[] $post_types Array of post type slugs to search.
	 * @return array[] Array of associative arrays with keys: id, title, edit_url, post_type, match_count, supports_revisions.
	 */
	public function preview( string $find, array $post_types ): array {
		$posts   = $this->query_posts( $post_types );
		$matches = [];

		foreach ( $posts as $post ) {
			$count = substr_count( $post->post_content, $find );
			if ( $count > 0 ) {
				$matches[] = [
					'id'                => $post->ID,
					'title'             => $post->post_title ?: '(no title)',
					'edit_url'          => get_edit_post_link( $post->ID ),
					'post_type'         => $post->post_type,
					'post_status'       => $post->post_status,
					'match_count'       => $count,
					'supports_revisions' => post_type_supports( $post->post_type, 'revisions' ),
				];
			}
		}

		return $matches;
	}

	/**
	 * Replace all occurrences of $find with $replace across matching posts.
	 * For post types that support revisions, a revision is saved before changes are applied.
	 * Uses a direct DB update to avoid touching post_modified timestamps.
	 *
	 * @param string   $find       Raw block markup to search for.
	 * @param string   $replace    Replacement block markup.
	 * @param string[] $post_types Array of post type slugs to search.
	 * @return int Number of posts updated.
	 */
	public function execute( string $find, string $replace, array $post_types ): int {
		global $wpdb;

		$posts   = $this->query_posts( $post_types );
		$updated = 0;

		foreach ( $posts as $post ) {
			if ( strpos( $post->post_content, $find ) === false ) {
				continue;
			}

			if ( post_type_supports( $post->post_type, 'revisions' ) ) {
				wp_save_post_revision( $post->ID );
			}

			$new_content = str_replace( $find, $replace, $post->post_content );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Direct update is intentional to avoid bumping post_modified; post cache is cleared below.
			$wpdb->update(
				$wpdb->posts,
				[ 'post_content' => $new_content ],
				[ 'ID' => $post->ID ],
				[ '%s' ],
				[ '%d' ]
			);

			clean_post_cache( $post->ID );

			$updated++;
		}

		return $updated;
	}

	/**
	 * Query all addressable posts across the given post types.
	 * Includes published, draft, scheduled, and private posts.
	 *
	 * @param string[] $post_types
	 * @return WP_Post[]
	 */
	private function query_posts( array $post_types ): array {
		$query = new WP_Query( [
			'post_type'      => $post_types,
			'post_status'    => [ 'publish', 'draft', 'future', 'private' ],
			'posts_per_page' => -1,
			'no_found_rows'  => true,
		] );

		return $query->posts;
	}
}
