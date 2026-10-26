/* Rishikul Shiksha — Settings page media picker + gradient swatch */
(function ($) {
	'use strict';

	$(function () {

		/* ─── UPI QR Image picker ─── */
		var frame;
		var $hidden  = $('#rks_upi_qr_image_id');
		var $preview = $('#rks_upi_qr_preview');
		var $remove  = $('#rks_upi_qr_remove');

		$('#rks_upi_qr_pick').on('click', function (e) {
			e.preventDefault();
			if (frame) { frame.open(); return; }
			frame = wp.media({
				title: 'Select UPI QR Image',
				button: { text: 'Use this image' },
				multiple: false,
				library: { type: 'image' }
			});
			frame.on('select', function () {
				var att = frame.state().get('selection').first().toJSON();
				$hidden.val(att.id);
				$preview.attr('src', att.sizes && att.sizes.medium ? att.sizes.medium.url : att.url).show();
				$remove.show();
			});
			frame.open();
		});

		$remove.on('click', function (e) {
			e.preventDefault();
			$hidden.val(0);
			$preview.attr('src', '').hide();
			$(this).hide();
		});

		/* ─── Franchise Brochure picker ─── */
		var brochureFrame;
		var $bHidden = $('#rks_brochure_id');
		var $bMeta   = $('#rks_brochure_meta');
		var $bName   = $('#rks_brochure_name');
		var $bLink   = $('#rks_brochure_link');
		var $bRemove = $('#rks_brochure_remove');

		$('#rks_brochure_pick').on('click', function (e) {
			e.preventDefault();
			if (brochureFrame) { brochureFrame.open(); return; }
			brochureFrame = wp.media({
				title: 'Select / Upload Franchise Brochure',
				button: { text: 'Use this file' },
				multiple: false,
				library: { type: ['application/pdf'] }
			});
			brochureFrame.on('select', function () {
				var att = brochureFrame.state().get('selection').first().toJSON();
				$bHidden.val(att.id);
				$bName.text(att.filename || att.title || 'Brochure');
				$bLink.attr('href', att.url);
				$bMeta.show();
				$bRemove.show();
			});
			brochureFrame.open();
		});

		$bRemove.on('click', function (e) {
			e.preventDefault();
			$bHidden.val(0);
			$bMeta.hide();
			$(this).hide();
		});

		/* ─── Per-row preset gradient swatch selection ─── */
		$(document).on('change', '#rks-universities-table input[type="radio"][name^="univ_color_swatch_"]', function () {
			var $radio = $(this);
			var $row   = $radio.closest('tr.rks-univ-row');

			// Reset all swatches in this row
			$row.find('.rks-preset-swatch').css('border-color', 'transparent').css('transform', '').removeClass('is-selected');

			// Highlight selected swatch
			$radio.siblings('.rks-preset-swatch').css('border-color', '#2271b1').addClass('is-selected');

			// Update the gradient name label
			var grad  = $radio.val();
			var label = $radio.closest('label').attr('title') || '';
			$row.find('small').text(label);

			// Update the hidden color input for this row
			$row.find('.rks-univ-color-input').val(grad);
		});

		/* ─── Swatch hover effect ─── */
		$(document).on('mouseenter', '.rks-preset-swatch', function () {
			$(this).css('transform', 'scale(1.2)');
		}).on('mouseleave', '.rks-preset-swatch', function () {
			$(this).css('transform', '');
		});

		/* ─── University rows repeater ─── */
		var defaultGrad = 'linear-gradient(135deg,#6c3fc5,#a855f7)';

		// Preset gradients list (must match PHP get_preset_gradients keys)
		var presets = [
			{ grad: 'linear-gradient(135deg,#6c3fc5,#a855f7)', label: 'Purple Amethyst' },
			{ grad: 'linear-gradient(135deg,#1a5fa8,#38bdf8)', label: 'Ocean Blue' },
			{ grad: 'linear-gradient(135deg,#1a7f5a,#34d399)', label: 'Emerald Green' },
			{ grad: 'linear-gradient(135deg,#c94f1a,#f97316)', label: 'Sunrise Orange' },
			{ grad: 'linear-gradient(135deg,#a8391a,#ef4444)', label: 'Deep Red' },
			{ grad: 'linear-gradient(135deg,#2c7a7a,#22d3ee)', label: 'Teal Aqua' },
			{ grad: 'linear-gradient(135deg,#7c3d8c,#f472b6)', label: 'Pink Violet' },
			{ grad: 'linear-gradient(135deg,#1e3a5f,#6366f1)', label: 'Midnight Indigo' },
			{ grad: 'linear-gradient(135deg,#854d0e,#facc15)', label: 'Golden Dusk' },
			{ grad: 'linear-gradient(135deg,#164e63,#0ea5e9)', label: 'Navy Sky' },
			{ grad: 'linear-gradient(135deg,#365314,#84cc16)', label: 'Forest Lime' },
			{ grad: 'linear-gradient(135deg,#3b1a6e,#e879f9)', label: 'Galaxy Purple' }
		];

		function buildSwatches(rowIdx, selectedGrad) {
			var html = '<input type="hidden" name="univ_color[]" class="rks-univ-color-input" value="' + selectedGrad + '">';
			html += '<div style="display:flex;flex-wrap:wrap;gap:6px;align-items:center;">';
			presets.forEach(function (p) {
				var isSelected = p.grad === selectedGrad;
				var rid = 'rks_univ_' + rowIdx + '_' + p.grad.replace(/[^a-z0-9]/gi, '').substring(0, 12);
				var border = isSelected ? '#2271b1' : 'transparent';
				var cls = 'rks-preset-swatch' + (isSelected ? ' is-selected' : '');
				html +=
					'<label title="' + p.label + '" style="cursor:pointer;">' +
					'<input type="radio" name="univ_color_swatch_' + rowIdx + '" value="' + p.grad + '" id="' + rid + '"' + (isSelected ? ' checked' : '') + ' style="position:absolute;opacity:0;width:0;height:0;">' +
					'<span class="' + cls + '" style="display:block;width:28px;height:28px;border-radius:6px;background:' + p.grad + ';border:3px solid ' + border + ';box-shadow:0 1px 4px rgba(0,0,0,.2);transition:border-color .12s,transform .12s;"></span>' +
					'</label>';
			});
			html += '</div>';
			var activeName = presets.find(function (p) { return p.grad === selectedGrad; });
			html += '<small style="display:block;margin-top:5px;color:#888;font-size:11px;">' + (activeName ? activeName.label : 'Custom') + '</small>';
			return html;
		}

		var rowCount = $('.rks-univ-row').length;

		$('#rks-add-university-row').on('click', function (e) {
			e.preventDefault();
			rowCount++;
			var rowHtml =
				'<tr class="rks-univ-row">' +
				'<td style="color:#999;font-size:12px;vertical-align:top;padding-top:12px;">' + rowCount + '</td>' +
				'<td><input type="text" name="univ_name[]" value="" class="regular-text" required style="width:100%;"></td>' +
				'<td><input type="text" name="univ_badge[]" value="" class="small-text" style="width:100%;"></td>' +
				'<td><input type="text" name="univ_tag[]" value="" class="regular-text" style="width:100%;"></td>' +
				'<td><input type="text" name="univ_location[]" value="" class="regular-text" style="width:100%;"></td>' +
				'<td><input type="text" name="univ_recognition[]" value="" class="regular-text" style="width:100%;"></td>' +
				'<td>' + buildSwatches(rowCount, defaultGrad) + '</td>' +
				'<td><button type="button" class="button button-link-delete rks-remove-university-row">Remove</button></td>' +
				'</tr>';
			$('#rks-universities-table tbody').append(rowHtml);
		});

		$(document).on('click', '.rks-remove-university-row', function (e) {
			e.preventDefault();
			$(this).closest('tr').remove();
		});

	});
})(jQuery);
