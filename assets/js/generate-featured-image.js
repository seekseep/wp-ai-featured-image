/**
 * AI Featured Image generator button handler.
 *
 * @package WpAiFeaturedImage
 */

/* global jQuery, wpAiFeaturedImage, wp */
(function ($) {
	'use strict';

	$(function () {
		var $btn = $('#wp-ai-generate-btn');
		var $status = $('#wp-ai-generate-status');
		var $preview = $('#wp-ai-generate-preview');

		$btn.on('click', function (e) {
			e.preventDefault();

			var postId = $btn.data('post-id') || $('#post_ID').val();

			if (!postId) {
				$status
					.html('<p class="notice notice-warning" style="padding:8px;">' +
						'Please save the post first.' +
						'</p>');
				return;
			}

			$btn.prop('disabled', true);
			$status.html(
				'<p style="padding:4px 0;">' +
				'<span class="spinner is-active" style="float:none;vertical-align:middle;margin:0 4px 0 0;"></span>' +
				'Generating featured image... This may take up to a minute.' +
				'</p>'
			);
			$preview.empty();

			$.ajax({
				url: wpAiFeaturedImage.ajaxUrl,
				type: 'POST',
				data: {
					action: 'wp_ai_generate_featured_image',
					_wpnonce: wpAiFeaturedImage.nonce,
					post_id: postId
				},
				success: function (res) {
					if (res.success) {
						$status.html(
							'<p class="notice notice-success" style="padding:8px;">' +
							'Featured image set successfully!' +
							'</p>'
						);
						$preview.html(
							'<img src="' + res.data.url + '" alt="Generated featured image" ' +
							'style="max-width:100%;height:auto;margin-top:8px;border:1px solid #ddd;">'
						);

						// Update the native WordPress featured image panel.
						if (typeof wp !== 'undefined' && wp.media && wp.media.featuredImage) {
							wp.media.featuredImage.set(res.data.attachment_id);
						}
					} else {
						$status.html(
							'<p class="notice notice-error" style="padding:8px;">' +
							'Error: ' + (res.data && res.data.message ? res.data.message : 'Unknown error') +
							'</p>'
						);
					}
				},
				error: function () {
					$status.html(
						'<p class="notice notice-error" style="padding:8px;">' +
						'Request failed. Please try again.' +
						'</p>'
					);
				},
				complete: function () {
					$btn.prop('disabled', false);
				}
			});
		});
	});
})(jQuery);
