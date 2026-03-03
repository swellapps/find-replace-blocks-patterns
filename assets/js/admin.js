/* global frbpData, jQuery */
( function ( $ ) {
	'use strict';

	var i18n = frbpData.i18n;

	var $previewBtn   = $( '#frbp-preview-btn' );
	var $executeBtn   = $( '#frbp-execute-btn' );
	var $spinner      = $( '#frbp-spinner' );
	var $results      = $( '#frbp-results' );
	var $resultsInner = $( '#frbp-results-inner' );
	var $confirm      = $( '#frbp-execute-confirm' );
	var $notice       = $( '#frbp-notice' );
	var $findField    = $( '#frbp-find' );
	var $replaceField = $( '#frbp-replace' );

	function getFind() {
		return $findField.val();
	}

	function getReplace() {
		return $replaceField.val();
	}

	function getPostTypes() {
		return $( '.frbp-post-type:checked' ).map( function () {
			return $( this ).val();
		} ).get();
	}

	function setLoading( loading ) {
		$previewBtn.prop( 'disabled', loading );
		$spinner.toggleClass( 'is-active', loading );
	}

	function showNotice( message, type ) {
		$notice
			.removeClass( 'notice-success notice-error notice-warning' )
			.addClass( 'notice notice-' + type )
			.html( '<p>' + message + '</p>' )
			.show();
		$( 'html, body' ).animate( { scrollTop: $notice.offset().top - 32 }, 300 );
	}

	function clearState() {
		$results.hide();
		$resultsInner.empty();
		$confirm.hide();
		$executeBtn.prop( 'disabled', true );
		$notice.hide();
	}

	function sprintf( str, value ) {
		return str.replace( '%d', value );
	}

	// Invalidate any existing preview whenever inputs change.
	$findField.on( 'input', clearState );
	$replaceField.on( 'input', clearState );
	$( document ).on( 'change', '.frbp-post-type', clearState );

	$previewBtn.on( 'click', function () {
		clearState();

		var find      = getFind();
		var postTypes = getPostTypes();

		if ( ! find.trim() ) {
			showNotice( i18n.findEmpty, 'error' );
			return;
		}

		if ( ! postTypes.length ) {
			showNotice( i18n.noPostTypes, 'error' );
			return;
		}

		setLoading( true );

		$.post( frbpData.ajaxUrl, {
			action:     'frbp_preview',
			nonce:      frbpData.nonce,
			find:       find,
			post_types: postTypes,
		} )
		.done( function ( response ) {
			if ( ! response.success ) {
				showNotice( response.data.message || i18n.errorOccurred, 'error' );
				return;
			}

			var matches = response.data.matches;

			if ( ! matches.length ) {
				showNotice( i18n.noMatches, 'warning' );
				return;
			}

			var hasNoRevisions = false;
			var foundStr = matches.length === 1 ? i18n.foundSingular : i18n.foundPlural;

			var html = '<p>' + sprintf( foundStr, matches.length ) + '</p>';

			html += '<table class="wp-list-table widefat fixed striped frbp-matches-table">';
			html += '<thead><tr>' +
				'<th>' + i18n.colTitle + '</th>' +
				'<th>' + i18n.colType + '</th>' +
				'<th>' + i18n.colStatus + '</th>' +
				'<th>' + i18n.colMatches + '</th>' +
				'<th>' + i18n.colRevisions + '</th>' +
				'<th>' + i18n.colEdit + '</th>' +
				'</tr></thead><tbody>';

			$.each( matches, function ( i, match ) {
				var revisionCell;
				if ( match.supports_revisions ) {
					revisionCell = '<span class="frbp-revision-yes" title="' + escapeHtml( i18n.revisionYes ) + '">&#10003;</span>';
				} else {
					revisionCell = '<span class="frbp-revision-no" title="' + escapeHtml( i18n.revisionNo ) + '">' + escapeHtml( i18n.revisionNoLabel ) + '</span>';
					hasNoRevisions = true;
				}

				html += '<tr>' +
					'<td>' + escapeHtml( match.title ) + '</td>' +
					'<td><code>' + escapeHtml( match.post_type ) + '</code></td>' +
					'<td><span class="frbp-status frbp-status-' + escapeHtml( match.post_status ) + '">' + escapeHtml( match.post_status ) + '</span></td>' +
					'<td>' + match.match_count + '</td>' +
					'<td>' + revisionCell + '</td>' +
					'<td><a href="' + escapeHtml( match.edit_url ) + '" target="_blank">' + escapeHtml( i18n.editLink ) + '</a></td>' +
					'</tr>';
			} );

			html += '</tbody></table>';

			if ( hasNoRevisions ) {
				html += '<p class="frbp-revision-warning">' + escapeHtml( i18n.revisionWarning ) + '</p>';
			}

			$resultsInner.html( html );
			$confirm.show();
			$results.show();
			$executeBtn.prop( 'disabled', false );
		} )
		.fail( function () {
			showNotice( i18n.requestFailed, 'error' );
		} )
		.always( function () {
			setLoading( false );
		} );
	} );

	$executeBtn.on( 'click', function () {
		var find      = getFind();
		var replace   = getReplace();
		var postTypes = getPostTypes();

		if ( ! find.trim() ) {
			showNotice( i18n.findEmpty, 'error' );
			return;
		}

		if ( ! postTypes.length ) {
			showNotice( i18n.noPostTypes, 'error' );
			return;
		}

		if ( ! window.confirm( i18n.confirmExecute ) ) {
			return;
		}

		setLoading( true );
		$executeBtn.prop( 'disabled', true );

		$.post( frbpData.ajaxUrl, {
			action:     'frbp_execute',
			nonce:      frbpData.nonce,
			find:       find,
			replace:    replace,
			post_types: postTypes,
		} )
		.done( function ( response ) {
			if ( ! response.success ) {
				showNotice( response.data.message || i18n.errorOccurred, 'error' );
				return;
			}
			$results.hide();
			showNotice( response.data.message, 'success' );
		} )
		.fail( function () {
			showNotice( i18n.requestFailed, 'error' );
		} )
		.always( function () {
			setLoading( false );
		} );
	} );

	function escapeHtml( str ) {
		return String( str )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' );
	}

} )( jQuery );
