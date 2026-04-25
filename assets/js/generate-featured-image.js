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
		var $actions = $('#wp-ai-generate-actions');
		var i18n = wpAiFeaturedImage.i18n || {};

		$btn.on('click', function (e) {
			e.preventDefault();

			var postId = $btn.data('post-id') || $('#post_ID').val();

			if (!postId) {
				$status
					.html('<p class="notice notice-warning" style="padding:8px;">' +
						i18n.saveDraftFirst +
						'</p>');
				return;
			}

			$btn.prop('disabled', true);
			$status.html(
				'<p style="padding:4px 0;">' +
				'<span class="spinner is-active" style="float:none;vertical-align:middle;margin:0 4px 0 0;"></span>' +
				i18n.generating +
				'</p>'
			);
			$preview.empty();
			$actions.empty();

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
							i18n.generated +
							'</p>'
						);
						$preview.html(
							'<img src="' + res.data.url + '" alt="" ' +
							'style="max-width:100%;height:auto;margin-top:8px;border:1px solid #ddd;">'
						);

						$actions.html(
							'<div style="display:flex;gap:8px;margin-top:8px;">' +
							'<button type="button" id="wp-ai-set-thumbnail" class="button button-primary">' +
							i18n.setAsThumbnail +
							'</button>' +
							'<button type="button" id="wp-ai-insert-post" class="button">' +
							i18n.insertIntoPost +
							'</button>' +
							'</div>'
						);

						$('#wp-ai-set-thumbnail').on('click', function () {
							var $this = $(this);
							$this.prop('disabled', true).text(i18n.setting);

							$.ajax({
								url: wpAiFeaturedImage.ajaxUrl,
								type: 'POST',
								data: {
									action: 'wp_ai_set_thumbnail',
									_wpnonce: wpAiFeaturedImage.nonce,
									post_id: postId,
									attachment_id: res.data.attachment_id
								},
								success: function (thumbRes) {
									if (thumbRes.success) {
										$this.text(i18n.done).addClass('disabled');
										if (typeof wp !== 'undefined' && wp.media && wp.media.featuredImage) {
											wp.media.featuredImage.set(res.data.attachment_id);
										}
									} else {
										$this.prop('disabled', false).text(i18n.setAsThumbnail);
										alert(thumbRes.data && thumbRes.data.message ? thumbRes.data.message : 'Failed');
									}
								},
								error: function () {
									$this.prop('disabled', false).text(i18n.setAsThumbnail);
								}
							});
						});

						$('#wp-ai-insert-post').on('click', function () {
							var imgHtml = '<img src="' + res.data.url + '" alt="" class="alignnone size-full wp-image-' + res.data.attachment_id + '" />';

							if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch) {
								var block = wp.blocks.createBlock('core/image', {
									id: res.data.attachment_id,
									url: res.data.url,
									alt: ''
								});
								wp.data.dispatch('core/block-editor').insertBlocks(block);
							} else if (typeof tinymce !== 'undefined' && tinymce.activeEditor) {
								tinymce.activeEditor.execCommand('mceInsertContent', false, imgHtml);
							} else {
								var $textarea = $('#content');
								if ($textarea.length) {
									$textarea.val($textarea.val() + '\n' + imgHtml);
								}
							}

							$(this).text(i18n.inserted).addClass('disabled').prop('disabled', true);
						});
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
						i18n.requestFailed +
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
