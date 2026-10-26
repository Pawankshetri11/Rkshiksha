<?php
/**
 * Template Name: Verify ID Card
 *
 * Public lookup page. Accepts `?id=RKS-ID-...`
 */
if ( ! defined( 'ABSPATH' ) ) exit;

use RKSiksha\Core\Modules\Branches;
use RKSiksha\Core\Modules\IdCard;

$query_id = isset( $_GET['id'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['id'] ) ) : '';
$card     = $query_id ? ( new IdCard() )->get_by_unique_id( $query_id ) : null;
$is_valid = $card && $card['status'] === 'active';
$branch   = $is_valid ? ( new Branches() )->get( (int) $card['branch_id'] ) : null;
$has_search = (bool) $query_id;

$share_url = $is_valid
	? esc_url( add_query_arg( 'id', $card['unique_id'], home_url( '/verify-id/' ) ) )
	: '';

$rks_settings  = (array) get_option( 'rks_settings', [] );
$support_email = (string) ( $rks_settings['support_email'] ?? 'info@rkshiksha.com' );
$support_phone = (string) ( $rks_settings['support_phone'] ?? '+91 8979775664' );
$phone_digits  = preg_replace( '/\D+/', '', $support_phone ?: '918979775664' );
if ( $phone_digits && strlen( $phone_digits ) === 10 ) $phone_digits = '91' . $phone_digits;
$phone_tel     = '+' . $phone_digits;
$phone_display = $support_phone ?: '+91 8979775664';

get_header();
?>

<section class="rks-verify-page">

	<!-- HERO -->
	<header class="rks-verify-page__hero">
		<div class="rks-container">
			<div class="rks-verify-page__hero-inner">
				<nav class="rks-verify-page__breadcrumb" aria-label="Breadcrumb">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
					<span>/</span>
					<span>Verify ID Card</span>
				</nav>
				<span class="rks-verify-page__eyebrow">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
					Official Verification
				</span>
				<h1 class="rks-verify-page__title">ID Card Verification</h1>
				<p class="rks-verify-page__tagline">
					Confirm the authenticity of any Rishikul Shiksha ID card by entering its unique ID number below.
				</p>
			</div>
		</div>
	</header>

	<!-- VERIFY CARD -->
	<div class="rks-container">
		<div class="rks-verify-card">
			<form method="get" action="<?php echo esc_url( home_url( '/verify-id/' ) ); ?>" class="rks-verify-card__form" role="search">
				<div class="rks-verify-card__input-wrap">
					<svg class="rks-verify-card__input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
					<label for="rks-verify-input" class="rks-sr-only">ID Number</label>
					<input type="text"
					       id="rks-verify-input"
					       name="id"
					       class="rks-verify-card__input"
					       placeholder="RKS-ID-STU-XXXXXX"
					       value="<?php echo esc_attr( $query_id ); ?>"
					       autocomplete="off"
					       spellcheck="false"
					       required
					       <?php echo $has_search ? '' : 'autofocus'; ?>>
				</div>
				<button type="submit" class="rks-btn rks-btn--primary rks-verify-card__submit">Verify</button>
			</form>

			<!-- Recent searches -->
			<div class="rks-verify-card__recent" id="rks-verify-recent" data-verify-base="<?php echo esc_url( home_url( '/verify-id/' ) ); ?>">
				<span class="rks-verify-card__recent-label">Recent:</span>
			</div>

			<?php if ( ! $has_search ) : ?>
			<div class="rks-verify-card__trust">
				<div class="rks-verify-card__trust-item">
					<span class="rks-verify-card__trust-icon">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
					</span>
					<div>
						<span class="rks-verify-card__trust-title">100% Authentic</span>
						<span class="rks-verify-card__trust-sub">Direct from official records</span>
					</div>
				</div>
				<div class="rks-verify-card__trust-item">
					<span class="rks-verify-card__trust-icon">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
					</span>
					<div>
						<span class="rks-verify-card__trust-title">Public Verification</span>
						<span class="rks-verify-card__trust-sub">Available for anyone, anytime</span>
					</div>
				</div>
				<div class="rks-verify-card__trust-item">
					<span class="rks-verify-card__trust-icon">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
					</span>
					<div>
						<span class="rks-verify-card__trust-title">Instant Result</span>
						<span class="rks-verify-card__trust-sub">Verified in under a second</span>
					</div>
				</div>
			</div>
			<?php endif; ?>
		</div>

		<?php if ( $has_search && ! $is_valid ) : ?>
		<!-- INVALID -->
		<div class="rks-verify-result rks-verify-result--invalid" role="alert">
			<div class="rks-verify-result__badge" aria-hidden="true">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>
			</div>
			<div class="rks-verify-result__head">
				<h2 class="rks-verify-result__title">Not Verified</h2>
				<p class="rks-verify-result__lead">
					The ID card <code><?php echo esc_html( $query_id ); ?></code> could not be found, is expired, or has been revoked.
				</p>
			</div>

			<details class="rks-verify-issues">
				<summary>Common reasons this might happen</summary>
				<ul>
					<li>Typo in the ID number — check capitalization and dashes carefully.</li>
					<li>The ID card has expired or has been deactivated.</li>
					<li>This ID does not exist in our official records.</li>
				</ul>
			</details>

			<div class="rks-verify-result__actions">
				<a href="mailto:<?php echo esc_attr( $support_email ); ?>?subject=ID%20Card%20Verification%20Issue%20-%20<?php echo esc_attr( $query_id ); ?>" class="rks-btn rks-btn--primary">Contact Support</a>
				<a href="<?php echo esc_url( home_url( '/verify-id/' ) ); ?>" class="rks-btn rks-btn--ghost">Try Again</a>
			</div>
		</div>

		<?php elseif ( $is_valid ) : ?>
		<!-- VALID -->
		<div class="rks-verify-result rks-verify-result--valid">
			<div class="rks-verify-result__badge" aria-hidden="true">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
			</div>
			<div class="rks-verify-result__head">
				<h2 class="rks-verify-result__title">Verified Authentic</h2>
				<p class="rks-verify-result__lead">
					This ID card is genuine and currently active in the Rishikul Shiksha registry.
				</p>
			</div>

			<!-- Mini ID preview -->
			<div class="rks-verify-result__preview">
				<span class="rks-verify-result__preview-eyebrow">Official ID Card</span>
				<h3 class="rks-verify-result__preview-name"><?php echo esc_html( (string) $card['holder_name'] ); ?></h3>
				<p class="rks-verify-result__preview-sub">
					<?php echo esc_html( (string) $card['holder_role'] ); ?>
					<?php if ( ! empty( $card['holder_type'] ) ) : ?>
						&middot; <?php echo esc_html( ucfirst( str_replace( '_', ' ', (string) $card['holder_type'] ) ) ); ?>
					<?php endif; ?>
				</p>
				<div class="rks-verify-result__preview-meta">
					<span class="rks-status rks-status--active">Active</span>
					<span class="rks-verify-result__preview-date">
						<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
						Issued <?php echo esc_html( date( 'd M Y', strtotime( (string) $card['issue_date'] ) ) ); ?>
					</span>
				</div>
			</div>

			<!-- Meta grid -->
			<div class="rks-verify-result__meta">
				<div class="rks-verify-result__meta-item">
					<span class="rks-verify-result__meta-icon">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18"/></svg>
					</span>
					<div class="rks-verify-result__meta-body">
						<span class="rks-verify-result__meta-label">ID Number</span>
						<span class="rks-verify-result__meta-value"><code><?php echo esc_html( (string) $card['unique_id'] ); ?></code></span>
					</div>
				</div>
				<div class="rks-verify-result__meta-item">
					<span class="rks-verify-result__meta-icon">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
					</span>
					<div class="rks-verify-result__meta-body">
						<span class="rks-verify-result__meta-label">Role</span>
						<span class="rks-verify-result__meta-value"><?php echo esc_html( (string) $card['holder_role'] ); ?></span>
					</div>
				</div>
				<div class="rks-verify-result__meta-item">
					<span class="rks-verify-result__meta-icon">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
					</span>
					<div class="rks-verify-result__meta-body">
						<span class="rks-verify-result__meta-label">Type</span>
						<span class="rks-verify-result__meta-value"><?php echo esc_html( ucfirst( str_replace( '_', ' ', (string) $card['holder_type'] ) ) ); ?></span>
					</div>
				</div>
				<div class="rks-verify-result__meta-item">
					<span class="rks-verify-result__meta-icon">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 9v12M15 9v12"/></svg>
					</span>
					<div class="rks-verify-result__meta-body">
						<span class="rks-verify-result__meta-label">Branch</span>
						<span class="rks-verify-result__meta-value"><?php echo esc_html( $branch ? $branch['name'] : '—' ); ?></span>
					</div>
				</div>
				<div class="rks-verify-result__meta-item">
					<span class="rks-verify-result__meta-icon">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
					</span>
					<div class="rks-verify-result__meta-body">
						<span class="rks-verify-result__meta-label">Issued</span>
						<span class="rks-verify-result__meta-value"><?php echo esc_html( date( 'd M Y', strtotime( (string) $card['issue_date'] ) ) ); ?></span>
					</div>
				</div>
				<?php if ( ! empty( $card['expiry_date'] ) ) : ?>
				<div class="rks-verify-result__meta-item">
					<span class="rks-verify-result__meta-icon">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
					</span>
					<div class="rks-verify-result__meta-body">
						<span class="rks-verify-result__meta-label">Valid Until</span>
						<span class="rks-verify-result__meta-value"><?php echo esc_html( date( 'd M Y', strtotime( (string) $card['expiry_date'] ) ) ); ?></span>
					</div>
				</div>
				<?php endif; ?>
			</div>

			<!-- Actions -->
			<div class="rks-verify-result__actions">
				<a href="<?php echo esc_url( add_query_arg( 'id', $card['unique_id'], home_url( '/idcard/' ) ) ); ?>" class="rks-btn rks-btn--primary" target="_blank" rel="noopener">View Full ID Card &rarr;</a>

				<div class="rks-verify-share" id="rks-share">
					<button type="button" class="rks-btn rks-btn--ghost" id="rks-share-toggle" aria-haspopup="true" aria-expanded="false">
						<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="margin-right:6px;vertical-align:-3px;"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.59 13.51 6.83 3.98M15.41 6.51l-6.82 3.98"/></svg>
						Share Verification
					</button>
					<div class="rks-verify-share__menu" role="menu">
						<a class="rks-verify-share__item" href="https://wa.me/?text=<?php echo rawurlencode( 'Verified ID Card: ' . $share_url ); ?>" target="_blank" rel="noopener" role="menuitem">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8z"/></svg>
							WhatsApp
						</a>
						<a class="rks-verify-share__item" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo rawurlencode( $share_url ); ?>" target="_blank" rel="noopener" role="menuitem">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
							Facebook
						</a>
						<a class="rks-verify-share__item" href="https://twitter.com/intent/tweet?url=<?php echo rawurlencode( $share_url ); ?>&text=<?php echo rawurlencode( 'Verified Authentic ID Card' ); ?>" target="_blank" rel="noopener" role="menuitem">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/></svg>
							Twitter / X
						</a>
						<button type="button" class="rks-verify-share__item" data-rks-copy="<?php echo esc_attr( $share_url ); ?>" role="menuitem">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
							<span class="rks-verify-share__copy-label">Copy Link</span>
						</button>
					</div>
				</div>
			</div>
		</div>
		<?php endif; ?>

		<!-- FAQ -->
		<section class="rks-verify-faq" aria-label="Frequently Asked Questions">
			<header class="rks-verify-faq__head">
				<span class="rks-verify-faq__eyebrow">FAQ</span>
				<h2 class="rks-verify-faq__title">Common Questions</h2>
			</header>
			<div class="rks-verify-faq__list">
				<details class="rks-verify-faq__item">
					<summary class="rks-verify-faq__q">
						<span class="rks-verify-faq__q-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg></span>
						How does ID verification work?
					</summary>
					<div class="rks-verify-faq__a">
						Every Rishikul Shiksha ID card carries a unique ID number printed on it. Enter that number above and we check it against our official registry in real time. If it matches an active card, you'll see the holder's name, role, and branch.
					</div>
				</details>
				<details class="rks-verify-faq__item">
					<summary class="rks-verify-faq__q">
						<span class="rks-verify-faq__q-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>
						Is this an official record?
					</summary>
					<div class="rks-verify-faq__a">
						Yes. Results displayed here come directly from the Rishikul Shiksha ID registry. A "Verified Authentic" result confirms the card was issued by us and is currently active.
					</div>
				</details>
				<details class="rks-verify-faq__item">
					<summary class="rks-verify-faq__q">
						<span class="rks-verify-faq__q-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></span>
						What if my ID isn't verified?
					</summary>
					<div class="rks-verify-faq__a">
						First double-check the ID for typos. If it still fails, the card may have expired or been deactivated. Contact <a href="mailto:<?php echo esc_attr( $support_email ); ?>"><?php echo esc_attr( $support_email ); ?></a> and we'll investigate.
					</div>
				</details>
				<details class="rks-verify-faq__item">
					<summary class="rks-verify-faq__q">
						<span class="rks-verify-faq__q-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg></span>
						Can I download the ID card?
					</summary>
					<div class="rks-verify-faq__a">
						The holder can download the official PDF from their dashboard after logging in. Public verification only shows summary details — full downloads are restricted for security.
					</div>
				</details>
			</div>
		</section>

		<!-- HELP CARD -->
		<aside class="rks-verify-help">
			<div class="rks-verify-help__inner">
				<div>
					<h3 class="rks-verify-help__title">Need help with verification?</h3>
					<p class="rks-verify-help__text">Our team responds within 24 hours on business days.</p>
				</div>
				<div class="rks-verify-help__actions">
					<a href="mailto:<?php echo esc_attr( $support_email ); ?>" class="rks-verify-help__action rks-verify-help__action--email">
						<svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16" aria-hidden="true"><path d="M22 6c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6zm-2 0-8 5L4 6h16zm0 12H4V8l8 5 8-5v10z"/></svg>
						Email
					</a>
					<a href="tel:<?php echo esc_attr( $phone_tel ); ?>" class="rks-verify-help__action rks-verify-help__action--call">
						<svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16" aria-hidden="true"><path d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56a.977.977 0 0 0-1.01.24l-1.57 1.97c-2.83-1.35-5.48-3.9-6.89-6.83l1.95-1.66c.27-.28.35-.67.24-1.02-.37-1.11-.56-2.3-.56-3.53 0-.54-.45-.99-.99-.99H4.19C3.65 3 3 3.24 3 3.99 3 13.28 10.73 21 20.01 21c.71 0 .99-.63.99-1.18v-3.45c0-.54-.45-.99-.99-.99z"/></svg>
						Call <?php echo esc_html( $phone_display ); ?>
					</a>
					<a href="https://wa.me/<?php echo esc_attr( $phone_digits ); ?>" target="_blank" rel="noopener" class="rks-verify-help__action rks-verify-help__action--wa">
						<svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16" aria-hidden="true"><path d="M.057 24l1.687-6.163a11.867 11.867 0 0 1-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.817 11.817 0 0 1 8.413 3.488 11.824 11.824 0 0 1 3.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 0 1-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884a9.86 9.86 0 0 0 1.516 5.26L3.5 19.85l1.654-1.657zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.296-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
						WhatsApp
					</a>
				</div>
			</div>
		</aside>
	</div>
</section>

<script>
(function(){
	var KEY = 'rksVerifyRecent_id';
	var MAX = 3;
	var currentId = <?php echo wp_json_encode( $query_id ); ?>;
	var isValid   = <?php echo $is_valid ? 'true' : 'false'; ?>;

	function readList(){
		try { return JSON.parse(localStorage.getItem(KEY)) || []; } catch(e){ return []; }
	}
	function writeList(l){
		try { localStorage.setItem(KEY, JSON.stringify(l.slice(0, MAX))); } catch(e){}
	}
	if (currentId && isValid) {
		var list = readList().filter(function(x){ return x !== currentId; });
		list.unshift(currentId);
		writeList(list);
	}
	var box = document.getElementById('rks-verify-recent');
	if (box) {
		var base = box.getAttribute('data-verify-base') || '';
		var items = readList().filter(function(x){ return x && x !== currentId; });
		if (items.length) {
			items.forEach(function(id){
				var a = document.createElement('a');
				a.className = 'rks-verify-card__recent-chip';
				a.href = base + '?id=' + encodeURIComponent(id);
				a.textContent = id;
				box.appendChild(a);
			});
			box.classList.add('is-visible');
		}
	}

	var share = document.getElementById('rks-share');
	if (share) {
		var btn = document.getElementById('rks-share-toggle');
		btn.addEventListener('click', function(e){
			e.stopPropagation();
			share.classList.toggle('is-open');
			btn.setAttribute('aria-expanded', share.classList.contains('is-open') ? 'true' : 'false');
		});
		document.addEventListener('click', function(e){
			if (!share.contains(e.target)) {
				share.classList.remove('is-open');
				btn.setAttribute('aria-expanded', 'false');
			}
		});
		var copyBtn = share.querySelector('[data-rks-copy]');
		if (copyBtn) {
			copyBtn.addEventListener('click', function(){
				var url = copyBtn.getAttribute('data-rks-copy');
				var label = copyBtn.querySelector('.rks-verify-share__copy-label');
				if (navigator.clipboard) {
					navigator.clipboard.writeText(url).then(function(){
						if (label) { var old = label.textContent; label.textContent = 'Copied!'; setTimeout(function(){ label.textContent = old; }, 1500); }
					});
				}
			});
		}
	}
})();
</script>

<?php get_footer(); ?>
