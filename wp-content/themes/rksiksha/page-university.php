<?php
/**
 * Template Name: University Counselling Page
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$rks_settings = (array) get_option( 'rks_settings', [] );
if ( class_exists( '\\RKSiksha\\Core\\Admin\\Settings' ) ) {
	$universities = ! empty( $rks_settings['universities'] ) ? (array) $rks_settings['universities'] : \RKSiksha\Core\Admin\Settings::get_default_universities();
} else {
	$universities = [
		[
			'name'        => 'Maya Devi University',
			'badge'       => 'MDU',
			'tag'         => 'Private University',
			'location'    => 'Selaqui, Dehradun',
			'recognition' => 'UGC Approved',
			'color'       => 'linear-gradient(135deg,#6c3fc5,#a855f7)',
		],
		[
			'name'        => 'Dev Bhoomi Uttarakhand University',
			'badge'       => 'DBUU',
			'tag'         => 'Private University',
			'location'    => 'Manduwala, Dehradun',
			'recognition' => 'UGC, AICTE, PCI',
			'color'       => 'linear-gradient(135deg,#1a7f5a,#34d399)',
		],
		[
			'name'        => 'JB Institute of Technology',
			'badge'       => 'JBIT',
			'tag'         => 'Institute',
			'location'    => 'Dehradun, Uttarakhand',
			'recognition' => 'AICTE Approved',
			'color'       => 'linear-gradient(135deg,#c94f1a,#f97316)',
		],
		[
			'name'        => 'Shri Guru Ram Rai University',
			'badge'       => 'SGRR',
			'tag'         => 'Private University',
			'location'    => 'Patel Nagar, Dehradun',
			'recognition' => 'UGC, NMC, AYUSH',
			'color'       => 'linear-gradient(135deg,#1a5fa8,#38bdf8)',
		],
		[
			'name'        => 'Uttaranchal University',
			'badge'       => 'UU',
			'tag'         => 'Private University',
			'location'    => 'Arcadia Grant, Dehradun',
			'recognition' => 'UGC, BCI, PCI, AICTE',
			'color'       => 'linear-gradient(135deg,#a8391a,#ef4444)',
		],
		[
			'name'        => 'Graphic Era (Deemed to be University)',
			'badge'       => 'GEU',
			'tag'         => 'Deemed University',
			'location'    => 'Clement Town, Dehradun',
			'recognition' => 'UGC Recognised',
			'color'       => 'linear-gradient(135deg,#2c7a7a,#22d3ee)',
		],
	];
}

get_header();
?>

<!-- A. HERO -->
<section class="rks-univ__hero">
    <div class="rks-univ__hero-blob rks-univ__hero-blob--1" aria-hidden="true"></div>
    <div class="rks-univ__hero-blob rks-univ__hero-blob--2" aria-hidden="true"></div>
    <div class="rks-container rks-univ__hero-inner">
        <div class="rks-univ__hero-copy">
            <nav class="rks-univ__breadcrumb" aria-label="Breadcrumb">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
                <span class="rks-univ__crumb-sep">/</span>
                <span>University Counselling</span>
            </nav>
            <span class="rks-eyebrow">Free Admission Counselling</span>
            <h1 class="rks-univ__hero-title">Find your perfect <span>Dehradun university</span> &mdash; with free expert counselling</h1>
            <p class="rks-univ__hero-sub">Hand-picked counselling for Dehradun's top 6 universities. From shortlisting to confirmed seat &mdash; we handle every step, free.</p>
            <div class="rks-univ__hero-ctas">
                <a href="#inquiry" class="rks-univ__btn rks-univ__btn--primary">Book Free Counselling
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </a>
                <a href="#colleges" class="rks-univ__btn rks-univ__btn--ghost">View Universities</a>
            </div>
            <div class="rks-univ__hero-mini">
                <div class="rks-univ__hero-mini-item">
                    <strong>6</strong>
                    <span>Top Universities</span>
                </div>
                <div class="rks-univ__hero-mini-divider"></div>
                <div class="rks-univ__hero-mini-item">
                    <strong>5000+</strong>
                    <span>Students Placed</span>
                </div>
                <div class="rks-univ__hero-mini-divider"></div>
                <div class="rks-univ__hero-mini-item">
                    <strong>100%</strong>
                    <span>Free</span>
                </div>
            </div>
        </div>
        <div class="rks-univ__hero-media">
            <div class="rks-univ__hero-frame">
                <img
                    src="<?php echo esc_url( get_template_directory_uri() . '/assets/university-students.png' ); ?>"
                    alt="University students celebrating graduation"
                    loading="lazy"
                    class="rks-univ__hero-img"
                />
            </div>
            <div class="rks-univ__hero-badge">
                <div class="rks-univ__hero-badge-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2 2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                </div>
                <div>
                    <strong>21 Days</strong>
                    <span>Avg. Time to Admission</span>
                </div>
            </div>
            <div class="rks-univ__hero-pill">
                <span class="rks-univ__hero-pill-dot"></span>
                Counsellors Online Now
            </div>
        </div>
    </div>
</section>

<!-- B. INQUIRY FORM (moved up) -->
<section id="inquiry" class="rks-univ__form-wrap">
    <div class="rks-container">
        <div class="rks-univ__form-grid">
            <aside class="rks-univ__why-card">
                <header>
                    <span class="rks-eyebrow">Why Choose Us</span>
                    <h2>5 reasons students trust us</h2>
                </header>
                <ul class="rks-univ__why-list">
                    <li>
                        <span class="rks-univ__why-ic" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
                        </span>
                        <div>
                            <strong>100% Free Counselling</strong>
                            <p>No fees, no obligations &mdash; expert guidance, fully free.</p>
                        </div>
                    </li>
                    <li>
                        <span class="rks-univ__why-ic" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
                        </span>
                        <div>
                            <strong>Personalised Shortlist</strong>
                            <p>University picks matched to your scores, budget &amp; goals.</p>
                        </div>
                    </li>
                    <li>
                        <span class="rks-univ__why-ic" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h6"/></svg>
                        </span>
                        <div>
                            <strong>Paperwork Done For You</strong>
                            <p>Applications, documents, verifications &mdash; we handle it all.</p>
                        </div>
                    </li>
                    <li>
                        <span class="rks-univ__why-ic" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                        </span>
                        <div>
                            <strong>Scholarship Tracking</strong>
                            <p>30%+ of our students get partial fee waivers we flag for them.</p>
                        </div>
                    </li>
                    <li>
                        <span class="rks-univ__why-ic" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13.5a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2.69h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.69 2.81a2 2 0 0 1-.45 2.11L7.91 10.5a16 16 0 0 0 5.61 5.61l1.27-.77a2 2 0 0 1 2.11-.45c.91.33 1.85.57 2.81.69A2 2 0 0 1 22 17.92z"/></svg>
                        </span>
                        <div>
                            <strong>Direct University Access</strong>
                            <p>Counsellor hotline to every partner campus &mdash; faster decisions.</p>
                        </div>
                    </li>
                </ul>
                <a href="https://wa.me/918979775664?text=<?php echo urlencode( 'Hi, I want free university admission counselling.' ); ?>" target="_blank" rel="noopener" class="rks-univ__why-wa">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M.057 24l1.687-6.163a11.867 11.867 0 0 1-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.817 11.817 0 0 1 8.413 3.488 11.824 11.824 0 0 1 3.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 0 1-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884a9.86 9.86 0 0 0 1.516 5.26L3.5 19.85l1.654-1.657zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.296-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                    Prefer WhatsApp? Chat now
                </a>
            </aside>
            <?php
            $rks_lead_status = isset( $_GET['lead'] ) ? sanitize_key( wp_unslash( (string) $_GET['lead'] ) ) : '';
            if ( 'success' === $rks_lead_status ) :
            ?>
                <div class="rks-univ__alert rks-univ__alert--success" style="padding:14px 18px;border-radius:10px;background:#e8f7ee;border:1px solid #b6e3c6;color:#1b6a3a;margin-bottom:18px;">
                    <strong>Thanks!</strong> We&rsquo;ve received your request and will reach out within 24 hours.
                </div>
            <?php elseif ( 'error' === $rks_lead_status ) : ?>
                <div class="rks-univ__alert rks-univ__alert--error" style="padding:14px 18px;border-radius:10px;background:#fdecec;border:1px solid #f5b5b5;color:#8a1c1c;margin-bottom:18px;">
                    <strong>Sorry,</strong> we couldn&rsquo;t save your request. Please check your details and try again.
                </div>
            <?php endif; ?>
            <form class="rks-univ__form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST">
                <input type="hidden" name="action" value="rks_submit_counselling">
                <?php wp_nonce_field( 'rks_submit_counselling', '_rks_nonce' ); ?>
                <input type="text" name="rks_hp" value="" style="position:absolute;left:-9999px;" tabindex="-1" autocomplete="off" aria-hidden="true">
                <div class="rks-univ__field-row">
                    <div class="rks-univ__field">
                        <label for="rks-univ-name">Student Name *</label>
                        <input type="text" id="rks-univ-name" name="student_name" required>
                    </div>
                    <div class="rks-univ__field">
                        <label for="rks-univ-phone">Phone *</label>
                        <input type="tel" id="rks-univ-phone" name="phone" required>
                    </div>
                </div>
                <div class="rks-univ__field-row">
                    <div class="rks-univ__field">
                        <label for="rks-univ-email">Email *</label>
                        <input type="email" id="rks-univ-email" name="email" required>
                    </div>
                    <div class="rks-univ__field">
                        <label for="rks-univ-city">City</label>
                        <input type="text" id="rks-univ-city" name="city">
                    </div>
                </div>
                <div class="rks-univ__field-row">
                    <div class="rks-univ__field">
                        <label for="rks-univ-qual">12th % / Last Qualification</label>
                        <input type="text" id="rks-univ-qual" name="qualification" placeholder="e.g. 78% / B.Sc">
                    </div>
                    <div class="rks-univ__field">
                        <label for="rks-univ-stream">Stream of Interest</label>
                        <select id="rks-univ-stream" name="stream">
                            <option value="">Select Stream</option>
                            <option>Engineering</option>
                            <option>Management</option>
                            <option>BCA / MCA</option>
                            <option>Medical</option>
                            <option>Arts</option>
                            <option>Law</option>
                            <option>Other</option>
                        </select>
                    </div>
                </div>
                <div class="rks-univ__field">
                    <label for="rks-univ-colleges">Preferred Universities</label>
                    <select id="rks-univ-colleges" name="preferred_colleges">
                        <option value="">Select University</option>
                        <?php foreach ( $universities as $u ) : ?>
                            <option value="<?php echo esc_attr( $u['name'] ); ?>">
                                <?php echo esc_html( $u['name'] . ( ! empty( $u['badge'] ) ? ' (' . $u['badge'] . ')' : '' ) ); ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="Not Sure / Need Advice">Not Sure / Need Advice</option>
                    </select>
                </div>
                <div class="rks-univ__field">
                    <label for="rks-univ-msg">Message <span class="rks-univ__opt">(optional)</span></label>
                    <textarea id="rks-univ-msg" name="message" rows="3"></textarea>
                </div>
                <button type="submit" class="rks-univ__btn rks-univ__btn--primary rks-univ__submit">Book Free Counselling Session
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </button>
            </form>
        </div>
    </div>
</section>

<!-- C. WHY OUR COUNSELLING -->
<section class="rks-univ__why">
    <div class="rks-container">
        <div class="rks-section-head">
            <span class="rks-eyebrow">Why Choose Us</span>
            <h2>Counselling Designed Around Your Future</h2>
            <p>From a quick chat to a confirmed admission letter — we walk every step with you.</p>
        </div>
        <div class="rks-univ__why-grid">
            <div class="rks-univ__feature">
                <div class="rks-univ__feature-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
                </div>
                <h3>Personalized Guidance</h3>
                <p>One-on-one sessions tailored to your interests, scores, and budget — no generic advice.</p>
            </div>
            <div class="rks-univ__feature">
                <div class="rks-univ__feature-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h4l3-9 4 18 3-9h4"/></svg>
                </div>
                <h3>Career Assessment</h3>
                <p>Aptitude and interest profiling to match you with the course where you'll truly thrive.</p>
            </div>
            <div class="rks-univ__feature">
                <div class="rks-univ__feature-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h6"/></svg>
                </div>
                <h3>Paperwork Support</h3>
                <p>From registration forms to document verification — we handle the boring bits for you.</p>
            </div>
            <div class="rks-univ__feature">
                <div class="rks-univ__feature-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="6"/><path d="M9 14l-2 8 5-3 5 3-2-8"/></svg>
                </div>
                <h3>Scholarship Assistance</h3>
                <p>We track every applicable scholarship and education loan so you save on fees from day one.</p>
            </div>
        </div>
    </div>
</section>

<!-- E. PARTNER UNIVERSITIES -->
<section id="colleges" class="rks-univ__colleges">
    <div class="rks-container">
        <div class="rks-section-head">
            <span class="rks-eyebrow">Our Network</span>
            <h2>Top Universities in Dehradun We Work With</h2>
            <p>Direct counsellor access, current intake info, and verified seat availability — all in one place.</p>
        </div>
        <div class="rks-univ__colleges-grid">

            <?php foreach ( $universities as $u ) :
                $u_grad = ! empty( $u['color'] ) ? (string) $u['color'] : 'linear-gradient(135deg,#6c3fc5,#a855f7)';
                // Legacy hex fallback
                if ( str_starts_with( $u_grad, '#' ) ) {
                    $u_grad = 'linear-gradient(135deg,' . $u_grad . ',' . $u_grad . 'cc)';
                }
            ?>
                <article class="rks-univ__college">
                    <div class="rks-univ__college-head">
                        <div class="rks-univ__badge" style="background:<?php echo esc_attr( $u_grad ); ?>;"><?php echo esc_html( $u['badge'] ?: 'UNIV' ); ?></div>
                        <?php if ( ! empty( $u['tag'] ) ) : ?>
                            <span class="rks-univ__tag"><?php echo esc_html( $u['tag'] ); ?></span>
                        <?php endif; ?>
                    </div>
                    <h3><?php echo esc_html( $u['name'] ); ?></h3>
                    <?php if ( ! empty( $u['location'] ) ) : ?>
                        <p class="rks-univ__college-loc">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <?php echo esc_html( $u['location'] ); ?>
                        </p>
                    <?php endif; ?>
                    <?php if ( ! empty( $u['recognition'] ) ) : ?>
                        <ul class="rks-univ__college-info">
                            <li><span>Recognised</span><strong><?php echo esc_html( $u['recognition'] ); ?></strong></li>
                        </ul>
                    <?php endif; ?>
                    <a href="#inquiry" class="rks-univ__college-link rks-univ__college-link--grad" style="--card-grad:<?php echo esc_attr( $u_grad ); ?>">Get Counselled
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
                    </a>
                </article>
            <?php endforeach; ?>

        </div>
    </div>
</section>

<!-- F. ADMISSION PROCESS -->
<section class="rks-univ__process">
    <div class="rks-container">
        <div class="rks-section-head">
            <span class="rks-eyebrow">Our Process</span>
            <h2>From First Call to Confirmed Admission</h2>
            <p>Five clear steps. Zero confusion. Average time-to-admission: under 21 days.</p>
        </div>
        <ol class="rks-univ__timeline">
            <li class="rks-univ__step">
                <div class="rks-univ__step-num">1</div>
                <h3>Free Initial Counselling Session</h3>
                <p>Tell us your scores, interests, and budget. We listen first.</p>
            </li>
            <li class="rks-univ__step">
                <div class="rks-univ__step-num">2</div>
                <h3>Career &amp; Course Assessment</h3>
                <p>Aptitude profiling and a shortlist of streams that genuinely fit you.</p>
            </li>
            <li class="rks-univ__step">
                <div class="rks-univ__step-num">3</div>
                <h3>University Selection &amp; Comparison</h3>
                <p>Side-by-side fees, placements, and campus comparison of your top picks.</p>
            </li>
            <li class="rks-univ__step">
                <div class="rks-univ__step-num">4</div>
                <h3>Application &amp; Document Support</h3>
                <p>We file forms, verify documents, and prep you for entrance interviews.</p>
            </li>
            <li class="rks-univ__step">
                <div class="rks-univ__step-num">5</div>
                <h3>Admission Confirmation &amp; Onboarding</h3>
                <p>Confirmed seat, fee structure walkthrough, hostel guidance — done.</p>
            </li>
        </ol>
    </div>
</section>

<!-- G. SUCCESS STORIES -->
<section class="rks-univ__stories">
    <div class="rks-container">
        <div class="rks-section-head">
            <span class="rks-eyebrow">Success Stories</span>
            <h2>Students Who Found Their University With Us</h2>
        </div>
        <div class="rks-univ__stories-grid">
            <article class="rks-univ__story">
                <div class="rks-univ__story-quote" aria-hidden="true">&ldquo;</div>
                <div class="rks-univ__story-tag">B.Tech CSE at Dev Bhoomi Uttarakhand University</div>
                <p>I was confused between three universities and the team helped me compare placements line by line. Finalized DBUU and got the application sorted in a week.</p>
                <div class="rks-univ__story-author">
                    <div class="rks-univ__story-avatar">AN</div>
                    <div>
                        <strong>Aman Negi</strong>
                        <span>Batch of 2023</span>
                    </div>
                </div>
            </article>
            <article class="rks-univ__story">
                <div class="rks-univ__story-quote" aria-hidden="true">&ldquo;</div>
                <div class="rks-univ__story-tag">BAMS at Dev Bhoomi Uttarakhand University</div>
                <p>Got a 30% scholarship I didn't even know existed at DBUU. The counsellor flagged it during our second session and helped me apply.</p>
                <div class="rks-univ__story-author">
                    <div class="rks-univ__story-avatar">RT</div>
                    <div>
                        <strong>Rohit Thapa</strong>
                        <span>Batch of 2024</span>
                    </div>
                </div>
            </article>
            <article class="rks-univ__story">
                <div class="rks-univ__story-quote" aria-hidden="true">&ldquo;</div>
                <div class="rks-univ__story-tag">BBA at Maya Devi University</div>
                <p>I was exhausted from researching universities on my own. The Rishikul Shiksha team shortlisted three options for me, compared the fees, and even arranged hostel visits. My admission at Maya Devi went through smoothly &mdash; no confusion at all.</p>
                <div class="rks-univ__story-author">
                    <div class="rks-univ__story-avatar">SR</div>
                    <div>
                        <strong>Sneha Rawat</strong>
                        <span>Batch of 2025</span>
                    </div>
                </div>
            </article>
        </div>
    </div>
</section>

<!-- H. SCHOLARSHIPS BANNER -->
<section class="rks-univ__scholar">
    <div class="rks-container">
        <div class="rks-univ__scholar-inner">
            <div class="rks-univ__scholar-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2 2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
            </div>
            <div class="rks-univ__scholar-copy">
                <span class="rks-univ__scholar-tag">DBUU Scholarship</span>
                <h2>Dev Bhoomi Uttarakhand University Scholarship</h2>
                <p>Up to <strong>100% scholarship</strong> available for eligible students at DBUU across all 120+ programmes &mdash; merit-based, sports, defence wards and need-based categories. Our counsellors help you apply and track every applicable waiver.</p>
                <a href="#inquiry" class="rks-univ__scholar-link">Check DBUU Scholarship Eligibility &rarr;</a>
            </div>
        </div>
    </div>
</section>

<!-- H2. PROGRAMMES WE COUNSEL FOR -->
<section class="rks-univ__dbuu">
    <div class="rks-container">
        <div class="rks-section-head">
            <span class="rks-eyebrow">Programmes We Cover</span>
            <h2>Shaping Your Future with 120+ Programmes</h2>
            <p>Complete programme list across our partner universities &mdash; we counsel for admission to every one of them, free.</p>
        </div>
        <div class="rks-univ__dbuu-grid">

            <article class="rks-univ__dbuu-card">
                <h3>Engineering</h3>
                <ul>
                    <li class="is-strong">B.Tech</li>
                    <li>Computer Science &amp; Engg.</li>
                    <li>AI &amp; Machine Learning</li>
                    <li>Cyber Security &amp; Forensics</li>
                    <li>Data Science*</li>
                    <li>AI and Data Science*</li>
                    <li>Civil / Mechanical / Aerospace</li>
                    <li>Electronics &amp; Communication</li>
                    <li>Electrical Engineering</li>
                    <li class="is-strong">M.Tech</li>
                    <li>Computer Science &amp; Engg.</li>
                    <li>Structural / Geotechnical Engg.</li>
                    <li>Transportation Engg.</li>
                    <li>Digital Communication</li>
                    <li>Energy Tech. / Thermal / Production</li>
                    <li>Smart Grid</li>
                </ul>
            </article>

            <article class="rks-univ__dbuu-card">
                <h3>Computer Application</h3>
                <ul>
                    <li class="is-strong">BCA / BCA with research</li>
                    <li>Core</li>
                    <li>Cyber Security</li>
                    <li>Data Science</li>
                    <li>Full Stack Development*</li>
                    <li>AI &amp; Data Science*</li>
                    <li class="is-strong">B.Sc IT (Information Technology)</li>
                    <li class="is-strong">B.Sc CS (AI &amp; Data Science)</li>
                    <li class="is-strong">MCA</li>
                    <li>AI &amp; Data Science*</li>
                </ul>
            </article>

            <article class="rks-univ__dbuu-card">
                <h3>Polytechnic (UBTER)</h3>
                <ul>
                    <li class="is-strong">Diploma</li>
                    <li>CSE / EE / CE</li>
                    <li>ME (Automobile / Production)</li>
                </ul>
            </article>

            <article class="rks-univ__dbuu-card">
                <h3>Management &amp; Commerce</h3>
                <ul>
                    <li class="is-strong">BBA / BBA with research</li>
                    <li>HR / IB / Marketing / Finance</li>
                    <li>Business Analytics*</li>
                    <li>Digital Marketing*</li>
                    <li>Fintech &amp; Digital Banking*</li>
                    <li>Aviation &amp; Airport Management</li>
                    <li>AI and Data Science</li>
                    <li class="is-strong">B.Com (Hons)</li>
                    <li class="is-strong">MBA</li>
                    <li>Core HR / Marketing / Finance / IB / Logistics &amp; Supply Chain</li>
                    <li>Business Analytics*</li>
                    <li>Digital Marketing*</li>
                    <li>Agribusiness</li>
                    <li>AI and Data Science</li>
                </ul>
            </article>

            <article class="rks-univ__dbuu-card">
                <h3>Architecture, Planning &amp; Design</h3>
                <ul>
                    <li class="is-strong">Diploma in Architecture</li>
                    <li class="is-strong">B. Arch</li>
                    <li class="is-strong">B. Design</li>
                    <li>Graphic Design</li>
                    <li>Interior Design / Digital Product Design</li>
                    <li>Game Design &amp; Animation*</li>
                    <li class="is-strong">B.Sc Animation &amp; Gaming</li>
                    <li class="is-strong">M.Sc Animation &amp; Multimedia</li>
                    <li class="is-strong">M. Plan</li>
                    <li class="is-strong">M. Design</li>
                    <li>Interior Design</li>
                    <li>Digital Product Design / UI &amp; UX*</li>
                </ul>
            </article>

            <article class="rks-univ__dbuu-card">
                <h3>Journalism &amp; Liberal Arts</h3>
                <ul>
                    <li class="is-strong">BA (Hons) / with research</li>
                    <li>Journalism &amp; Mass Comm.</li>
                    <li>English / Psychology</li>
                    <li class="is-strong">Bachelor of Fine Arts (BFA) / MFA</li>
                    <li class="is-strong">Fashion Design</li>
                    <li>Diploma / B. Design / B.Sc</li>
                    <li class="is-strong">MA</li>
                    <li>Journalism &amp; Mass Com. / English</li>
                    <li>Fashion Design &amp; Merchandising</li>
                </ul>
            </article>

            <article class="rks-univ__dbuu-card">
                <h3>Hotel Management &amp; Tourism</h3>
                <ul>
                    <li class="is-strong">Diploma (with 3+ specializations)</li>
                    <li>Food Production</li>
                    <li>Bakery &amp; Confectionery</li>
                    <li>Food &amp; Beverage Service</li>
                    <li class="is-strong">BHM / MHM</li>
                    <li class="is-strong">BBA &mdash; Tourism &amp; Event Management</li>
                    <li class="is-strong">B.Sc Hospitality &amp; Hotel Management</li>
                </ul>
            </article>

            <article class="rks-univ__dbuu-card">
                <h3>Agriculture</h3>
                <ul>
                    <li class="is-strong">B.Sc (Hons)</li>
                    <li>Agriculture</li>
                    <li>Forestry</li>
                    <li class="is-strong">M.Sc</li>
                    <li>Agronomy / Pathology</li>
                    <li>Genetics &amp; Plant Breeding</li>
                    <li>Horticulture (Vegetable Science)</li>
                </ul>
            </article>

            <article class="rks-univ__dbuu-card">
                <h3>Pharmacy</h3>
                <ul>
                    <li class="is-strong">D.Pharm</li>
                    <li class="is-strong">B.Pharm</li>
                    <li class="is-strong">Pharm.D</li>
                    <li class="is-strong">M.Pharm</li>
                    <li>Pharmaceutics</li>
                    <li>Pharmacology</li>
                    <li class="is-strong">M.Sc (Pharmaceutical Chemistry)</li>
                </ul>
            </article>

            <article class="rks-univ__dbuu-card">
                <h3>Ayurveda &amp; Hospital</h3>
                <ul>
                    <li class="is-strong">BAMS</li>
                </ul>
            </article>

            <article class="rks-univ__dbuu-card">
                <h3>Paramedical Science</h3>
                <ul>
                    <li class="is-strong">BPT</li>
                    <li class="is-strong">BMRIT</li>
                    <li class="is-strong">BMLT</li>
                    <li class="is-strong">B.Sc</li>
                    <li>Operation Theatre</li>
                    <li>Optometry</li>
                    <li>Medical Microbiology</li>
                    <li class="is-strong">BHA</li>
                    <li class="is-strong">MHA</li>
                    <li class="is-strong">Master of Public Health (MPH)</li>
                </ul>
            </article>

            <article class="rks-univ__dbuu-card">
                <h3>Nursing</h3>
                <ul>
                    <li class="is-strong">GNM</li>
                    <li class="is-strong">B.Sc Nursing</li>
                </ul>
            </article>

            <article class="rks-univ__dbuu-card">
                <h3>Allied Sciences</h3>
                <ul>
                    <li class="is-strong">B.Sc (Hons) / with research</li>
                    <li>Forensic Science</li>
                    <li>Food Technology</li>
                    <li>Biotechnology / Microbiology</li>
                    <li>Chemistry / Zoology</li>
                    <li class="is-strong">B. Tech &mdash; Biotechnology</li>
                    <li class="is-strong">M.Sc</li>
                    <li>Food Technology</li>
                    <li>Biotechnology</li>
                    <li>Microbiology</li>
                    <li>Forensic Science</li>
                    <li>Industrial Chemistry</li>
                </ul>
            </article>

            <article class="rks-univ__dbuu-card">
                <h3>Law</h3>
                <ul>
                    <li class="is-strong">Three Years Degree Program</li>
                    <li>LLB</li>
                    <li class="is-strong">Five Years Integrated Degree Program</li>
                    <li>B.A. LLB (Hons.)</li>
                    <li>B.B.A. LLB (Hons.)</li>
                    <li>B.Com. LLB (Hons.)</li>
                </ul>
            </article>

        </div>
        <div class="rks-univ__dbuu-cta">
            <a href="#inquiry" class="rks-univ__btn rks-univ__btn--primary">Apply for Admission
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
            <span class="rks-univ__dbuu-note">* Programmes marked with asterisk are in collaboration with industry partners. Availability varies by university.</span>
        </div>
    </div>
</section>

<!-- J. FAQ -->
<section class="rks-univ__faq">
    <div class="rks-container">
        <div class="rks-section-head">
            <span class="rks-eyebrow">FAQs</span>
            <h2>Things Parents &amp; Students Ask Us</h2>
        </div>
        <div class="rks-univ__faq-list">
            <details class="rks-univ__faq-item">
                <summary>Is the counselling really free?</summary>
                <p>Yes, completely free. We are paid by partner universities only after a student confirms admission, so there's zero cost to you or your family for the counselling itself.</p>
            </details>
            <details class="rks-univ__faq-item">
                <summary>Which Dehradun universities do you work with?</summary>
                <p>We partner with six top institutions: Maya Devi University, Dev Bhoomi Uttarakhand University (DBUU), JB Institute of Technology (JBIT), Shri Guru Ram Rai University (SGRR), Uttaranchal University, and Graphic Era.</p>
            </details>
            <details class="rks-univ__faq-item">
                <summary>Can you help with scholarship applications?</summary>
                <p>Absolutely. We track government, university merit, and need-based scholarships. About 30% of our students receive a partial fee waiver of 10–50%.</p>
            </details>
            <details class="rks-univ__faq-item">
                <summary>Do you assist with hostel and accommodation?</summary>
                <p>Yes. We coordinate hostel applications on campus and also share verified PG/flat options near each university for outstation students.</p>
            </details>
            <details class="rks-univ__faq-item">
                <summary>Is there support for outstation students?</summary>
                <p>Definitely. Most of our applicants come from outside Uttarakhand. We handle everything online — video counselling, document courier, hostel pre-booking.</p>
            </details>
            <details class="rks-univ__faq-item">
                <summary>When should I start the counselling process?</summary>
                <p>Ideally right after your 12th board exam, or by April–May for the upcoming July session. Earlier you start, better the seat options and scholarship chances.</p>
            </details>
        </div>
    </div>
</section>

<!-- K. FINAL CTA -->
<section class="rks-univ__final-cta">
    <div class="rks-container">
        <div class="rks-univ__final-inner">
            <div class="rks-univ__final-copy">
                <span class="rks-eyebrow">Still have questions?</span>
                <h2>Your dream university is one call away</h2>
                <p>Free expert counsellors, ready when you are. No commitment &mdash; just clarity.</p>
            </div>
            <div class="rks-univ__final-actions">
                <a href="#inquiry" class="rks-univ__btn rks-univ__btn--white">Schedule Free Session
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </a>
                <a href="tel:+918979775664" class="rks-univ__btn rks-univ__btn--ghost-light">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13.5a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2.69h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.69 2.81a2 2 0 0 1-.45 2.11L7.91 10.5a16 16 0 0 0 5.61 5.61l1.27-.77a2 2 0 0 1 2.11-.45c.91.33 1.85.57 2.81.69A2 2 0 0 1 22 17.92z"/></svg>
                    +91 89797 75664
                </a>
            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>
