<?php
/**
 * Template Name: Legal
 *
 * Showcase verified RK Shiksha legal documents (DARPAN, UDYAM, etc.) as
 * preview-only cards with no download, no toolbar and watermark overlay.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* ---------- Per-page body class so JS/CSS only run here ---------- */
add_filter( 'body_class', static function ( $classes ) {
	$classes[] = 'rks-legal-page';
	return $classes;
} );

/* ---------- Anti-framing header for this page only ---------- */
add_filter( 'wp_headers', static function ( $headers ) {
	$headers['X-Frame-Options']        = 'SAMEORIGIN';
	$headers['Content-Security-Policy'] = ( $headers['Content-Security-Policy'] ?? '' ) . "frame-ancestors 'self';";
	return $headers;
}, 99 );

get_header();

/* ---------- DATA ---------- */
$docs = [];
if ( class_exists( '\\RKSiksha\\Core\\Admin\\LegalPage' ) ) {
	$docs = \RKSiksha\Core\Admin\LegalPage::get_documents();
}

/* ---------- SVG ICONS ---------- */
$svg_shield = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>';
$svg_doc    = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>';
$svg_eye    = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>';
$svg_check  = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
$svg_lock   = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>';
$svg_close  = '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
?>

<main id="primary" class="rks-legal">

	<!-- ============ HERO ============ -->
	<section class="rks-legal__hero">
		<div class="rks-legal__hero-bg" aria-hidden="true">
			<span class="rks-legal__blob rks-legal__blob--a"></span>
			<span class="rks-legal__blob rks-legal__blob--b"></span>
		</div>
		<div class="rks-container">
			<div class="rks-legal__hero-intro">
				<span class="rks-legal__eyebrow"><?php echo $svg_shield; ?> Verified &middot; Licensed &middot; Trusted</span>
				<h1>Legal <span>Documents</span></h1>
				<p>Rishikul Shiksha is a registered, Government-verified educational institution. The documents below are official records issued by competent authorities — provided here for public verification only.</p>

				<ul class="rks-legal__trust">
					<li><span class="rks-legal__trust-ic"><?php echo $svg_check; ?></span> Issued by competent Govt. authorities</li>
					<li><span class="rks-legal__trust-ic"><?php echo $svg_lock; ?></span> Read-only · No download</li>
					<li><span class="rks-legal__trust-ic"><?php echo $svg_shield; ?></span> Watermarked verified copies</li>
				</ul>
			</div>
		</div>
	</section>

	<!-- ============ DOCUMENTS GRID ============ -->
	<section class="rks-legal__section">
		<div class="rks-container">
			<?php if ( empty( $docs ) ) : ?>
				<div class="rks-legal__empty">
					<div class="rks-legal__empty-ic"><?php echo $svg_doc; ?></div>
					<h3>No documents published yet</h3>
					<p>Administrators can add legal documents from <strong>WP Admin &rarr; Legal</strong>.</p>
				</div>
			<?php else : ?>
				<div class="rks-legal__grid">
					<?php foreach ( $docs as $idx => $doc ) :
						$title       = (string) $doc['title'];
						$desc        = (string) $doc['description'];
						?>
						<article class="rks-legal__card" data-doc-index="<?php echo (int) $idx; ?>">

							<div class="rks-legal__viewer rks-legal__noselect" data-rks-protect>
								<div class="rks-legal__pages rks-legal__pages--thumb" data-rks-preview="<?php echo (int) $idx; ?>" aria-label="<?php echo esc_attr( $title ); ?> preview">
									<div class="rks-legal__loading"><?php echo $svg_doc; ?><span>Loading secure preview...</span></div>
								</div>

								<!-- Watermark overlay stays light so the document remains readable. -->
								<span class="rks-legal__wm" aria-hidden="true"></span>

								<!-- Passive shield kept for legacy anti-save bindings; scrolling/clicks pass through. -->
								<span class="rks-legal__shield" aria-hidden="true"></span>

							</div>

							<div class="rks-legal__meta">
								<h3 class="rks-legal__title"><?php echo esc_html( $title ); ?></h3>
								<?php if ( $desc !== '' ) : ?>
									<p class="rks-legal__desc"><?php echo esc_html( $desc ); ?></p>
								<?php endif; ?>
								<div class="rks-legal__badges">
									<span class="rks-legal__badge rks-legal__badge--verified"><?php echo $svg_check; ?> Verified</span>
									<span class="rks-legal__badge rks-legal__badge--readonly"><?php echo $svg_lock; ?> View-only</span>
								</div>
								<button type="button" class="rks-legal__cta" data-rks-view="<?php echo (int) $idx; ?>">
									<?php echo $svg_eye; ?> View Document
								</button>
							</div>

						</article>
					<?php endforeach; ?>
				</div>

				<div class="rks-legal__notice">
					<span class="rks-legal__notice-ic"><?php echo $svg_lock; ?></span>
					<p>These watermarked documents are provided for verification only. Downloading or redistributing these documents is not permitted. Any unauthorised use will be treated as a violation of our intellectual property rights.</p>
				</div>
			<?php endif; ?>
		</div>
	</section>

</main>

<!-- ============ INLINE VIEWER MODAL ============ -->
<?php if ( ! empty( $docs ) ) : ?>
<div class="rks-legal__modal" id="rks-legal-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-label="Legal document viewer">
	<button class="rks-legal__modal-close" type="button" aria-label="Close">
		<?php echo $svg_close; ?>
	</button>
	<div class="rks-legal__modal-stage rks-legal__noselect" data-rks-protect>
		<div class="rks-legal__modal-frame">
			<div class="rks-legal__pages rks-legal__pages--full" id="rks-legal-modal-pages" aria-label="Document preview"></div>
			<span class="rks-legal__wm rks-legal__wm--lg" aria-hidden="true"></span>
			<span class="rks-legal__shield" aria-hidden="true"></span>
		</div>
		<div class="rks-legal__modal-meta">
			<strong id="rks-legal-modal-title"></strong>
			<small id="rks-legal-modal-desc"></small>
		</div>
	</div>
</div>

<!-- ============ TOAST ============ -->
<div class="rks-legal__toast" id="rks-legal-toast" aria-live="polite"></div>

<script>
(function(){
	'use strict';
	// Hard gate: only run on the legal page.
	if (!document.body.classList.contains('rks-legal-page')) return;

	var DOCS = <?php echo wp_json_encode( array_map( static function ( $d ) {
		return [
			'url'   => \RKSiksha\Core\Admin\LegalPage::document_proxy_url( $d ),
			'title' => (string) $d['title'],
			'desc'  => (string) $d['description'],
		];
	}, $docs ) ); ?>;
	var PDF_WORKER = <?php echo wp_json_encode( get_template_directory_uri() . '/assets/js/vendor/pdfjs/pdf.worker.min.js?ver=3.11.174' ); ?>;

	var modal       = document.getElementById('rks-legal-modal');
	var modalPages  = document.getElementById('rks-legal-modal-pages');
	var modalTitle  = document.getElementById('rks-legal-modal-title');
	var modalDesc   = document.getElementById('rks-legal-modal-desc');
	var modalClose  = modal ? modal.querySelector('.rks-legal__modal-close') : null;
	var toastEl     = document.getElementById('rks-legal-toast');
	var toastTimer  = null;
	var pdfCache    = {};

	if (window.pdfjsLib) {
		window.pdfjsLib.GlobalWorkerOptions.workerSrc = PDF_WORKER;
	}

	/* ---------- Toast helper ---------- */
	function toast(msg) {
		if (!toastEl) return;
		toastEl.textContent = msg;
		toastEl.classList.add('is-visible');
		clearTimeout(toastTimer);
		toastTimer = setTimeout(function(){
			toastEl.classList.remove('is-visible');
		}, 2200);
	}

	function escapeText(text) {
		return String(text || '').replace(/[&<>"']/g, function(ch){
			return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[ch];
		});
	}

	function showLoading(container, message) {
		if (!container) return;
		container.innerHTML = '<div class="rks-legal__loading"><?php echo str_replace( "'", "\\'", $svg_doc ); ?><span>' + escapeText(message || 'Loading secure preview...') + '</span></div>';
	}

	function showError(container, message) {
		if (!container) return;
		container.innerHTML = '<div class="rks-legal__pdf-fallback"><?php echo str_replace( "'", "\\'", $svg_doc ); ?><span>' + escapeText(message || 'Preview unavailable.') + '</span></div>';
	}

	function stampWatermark(ctx, canvas, large) {
		var text = 'RISHIKUL SHIKSHA';
		ctx.save();
		ctx.setTransform(1, 0, 0, 1, 0, 0);
		ctx.globalAlpha = large ? 0.10 : 0.12;
		ctx.fillStyle = '#2C687B';
		ctx.font = '800 ' + (large ? 24 : 16) + 'px Arial, sans-serif';
		ctx.textAlign = 'center';
		ctx.textBaseline = 'middle';
		ctx.translate(canvas.width / 2, canvas.height / 2);
		ctx.rotate(-Math.PI / 6);
		var gapX = large ? 820 : 640;
		var gapY = large ? 360 : 280;
		for (var y = -canvas.height; y <= canvas.height; y += gapY) {
			for (var x = -canvas.width; x <= canvas.width; x += gapX) {
				ctx.fillText(text, x, y);
			}
		}
		ctx.restore();
	}

	function pdfFor(doc) {
		if (!window.pdfjsLib) {
			return Promise.reject(new Error('PDF viewer library is not available.'));
		}
		if (!pdfCache[doc.url]) {
			pdfCache[doc.url] = window.pdfjsLib.getDocument({
				url: doc.url,
				disableAutoFetch: false,
				disableStream: false,
				rangeChunkSize: 262144
			}).promise;
		}
		return pdfCache[doc.url];
	}

	function renderPage(pdf, holder, pageNo, options) {
		if (!holder || holder.dataset.rendered || holder.dataset.rendering) return;
		holder.dataset.rendering = '1';
		holder.classList.add('is-rendering');
		return pdf.getPage(pageNo).then(function(page){
			var base = page.getViewport({ scale: 1 });
			var available = Math.max(240, (holder.clientWidth || holder.parentNode.clientWidth || 720) - 24);
			var scale = available / base.width;
			if (!options.full) {
				scale = Math.min(scale, 0.72);
			}
			var viewport = page.getViewport({ scale: scale });
			var dpr = Math.min(window.devicePixelRatio || 1, 2);
			var canvas = document.createElement('canvas');
			var ctx = canvas.getContext('2d', { alpha: false });
			canvas.width = Math.floor(viewport.width * dpr);
			canvas.height = Math.floor(viewport.height * dpr);
			canvas.style.width = Math.floor(viewport.width) + 'px';
			canvas.style.height = Math.floor(viewport.height) + 'px';
			ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
			holder.innerHTML = '';
			holder.appendChild(canvas);
			var label = document.createElement('span');
			label.className = 'rks-legal__page-num';
			label.textContent = 'Page ' + pageNo + ' of ' + pdf.numPages;
			holder.appendChild(label);
			return page.render({ canvasContext: ctx, viewport: viewport }).promise.then(function(){
				stampWatermark(ctx, canvas, !!options.full);
				holder.dataset.rendered = '1';
				holder.classList.remove('is-rendering');
			});
		}).catch(function(){
			holder.classList.remove('is-rendering');
			holder.innerHTML = '<div class="rks-legal__pdf-fallback"><?php echo str_replace( "'", "\\'", $svg_doc ); ?><span>Page could not be loaded.</span></div>';
		});
	}

	function buildPages(pdf, container, options) {
		var count = options.full ? pdf.numPages : 1;
		container.innerHTML = '';
		container.dataset.totalPages = String(pdf.numPages);
		var holders = [];
		for (var i = 1; i <= count; i++) {
			var holder = document.createElement('div');
			holder.className = 'rks-legal__page';
			holder.dataset.page = String(i);
			holder.innerHTML = '<span class="rks-legal__page-wait">Page ' + i + '</span>';
			container.appendChild(holder);
			holders.push(holder);
		}
		if (!holders.length) return;
		if ('IntersectionObserver' in window) {
			var root = options.full ? container.closest('.rks-legal__modal-frame') : null;
			var io = new IntersectionObserver(function(entries){
				entries.forEach(function(entry){
					if (!entry.isIntersecting) return;
					var holder = entry.target;
					io.unobserve(holder);
					renderPage(pdf, holder, parseInt(holder.dataset.page, 10), options);
				});
			}, { root: root, rootMargin: '700px 0px' });
			holders.forEach(function(holder){ io.observe(holder); });
		} else {
			holders.forEach(function(holder){
				renderPage(pdf, holder, parseInt(holder.dataset.page, 10), options);
			});
		}
		renderPage(pdf, holders[0], 1, options);
	}

	function renderDocument(doc, container, options) {
		if (!container || !doc) return;
		showLoading(container, options.full ? 'Loading secure document...' : 'Loading secure preview...');
		pdfFor(doc).then(function(pdf){
			buildPages(pdf, container, options);
		}).catch(function(){
			showError(container, 'Preview unavailable in this browser.');
		});
	}

	document.querySelectorAll('[data-rks-preview]').forEach(function(container){
		var i = parseInt(container.getAttribute('data-rks-preview'), 10) || 0;
		renderDocument(DOCS[i], container, { full: false });
	});

	/* ---------- Modal open/close ---------- */
	function openModal(i) {
		var d = DOCS[i];
		if (!d) return;
		if (modalTitle) modalTitle.textContent = d.title;
		if (modalDesc) modalDesc.textContent = d.desc || '';
		if (modalPages) renderDocument(d, modalPages, { full: true });
		modal.classList.add('is-open');
		modal.setAttribute('aria-hidden', 'false');
		document.body.style.overflow = 'hidden';
	}
	function closeModal() {
		if (!modal) return;
		modal.classList.remove('is-open');
		modal.setAttribute('aria-hidden', 'true');
		if (modalPages) modalPages.innerHTML = '';
		document.body.style.overflow = '';
	}
	document.querySelectorAll('[data-rks-view]').forEach(function(btn){
		btn.addEventListener('click', function(){
			openModal(parseInt(btn.getAttribute('data-rks-view'), 10) || 0);
		});
	});
	if (modalClose) modalClose.addEventListener('click', closeModal);
	if (modal) {
		modal.addEventListener('click', function(e){
			if (e.target === modal) closeModal();
		});
	}
	document.addEventListener('keydown', function(e){
		if (e.key === 'Escape' && modal && modal.classList.contains('is-open')) closeModal();
	});

	/* ---------- Anti-right-click ---------- */
	document.addEventListener('contextmenu', function(e){
		// Allow right-click on normal form fields (no inputs on this page anyway, but be safe)
		var t = e.target;
		if (t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA')) return;
		e.preventDefault();
		toast('Right-click is disabled on this page.');
	}, true);

	/* ---------- Bind capture-phase handlers directly on every PDF shield ---------- */
	function bindShield(el) {
		if (!el || el._rksBound) return;
		el._rksBound = true;
		el.addEventListener('contextmenu', function(e){
			e.preventDefault();
			e.stopPropagation();
			toast('Right-click is disabled on this document.');
		}, true);
		el.addEventListener('mousedown', function(e){
			if (e.button === 2) {
				e.preventDefault();
				e.stopPropagation();
			}
		}, true);
		el.addEventListener('dragstart', function(e){ e.preventDefault(); }, true);
	}
	document.querySelectorAll('.rks-legal__shield').forEach(bindShield);
	// In case the modal shield is added dynamically later
	var bindObserver = new MutationObserver(function(){
		document.querySelectorAll('.rks-legal__shield').forEach(bindShield);
	});
	bindObserver.observe(document.body, { childList: true, subtree: true });

	/* ---------- Block selection / drag / save shortcuts ---------- */
	document.addEventListener('dragstart', function(e){ e.preventDefault(); });
	document.addEventListener('selectstart', function(e){
		// Allow text selection inside modal meta, but not on the document viewer
		var t = e.target;
		if (t && t.closest && t.closest('[data-rks-protect]')) e.preventDefault();
	});

	document.addEventListener('keydown', function(e){
		var k = e.key;
		var ctrl = e.ctrlKey || e.metaKey;

		if (ctrl && ['p', 's', 'u'].indexOf(k.toLowerCase()) !== -1) {
			e.preventDefault();
			toast('This action is disabled on this page.');
			return;
		}
		if (ctrl && e.shiftKey && ['s', 'p'].indexOf(k.toLowerCase()) !== -1) {
			e.preventDefault();
			toast('This action is disabled on this page.');
		}
	});

})();
</script>
<?php endif; ?>

<?php get_footer(); ?>
