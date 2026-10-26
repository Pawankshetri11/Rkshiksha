<?php
/**
 * Front Page Template — Rishikul Shiksha
 * Compact design aligned with about/coaching/contact/university pages.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$svg_check    = '<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
$svg_arrow    = '<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>';
$svg_trophy   = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/></svg>';
$svg_users    = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>';
$svg_book     = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>';
$svg_globe    = '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>';
$svg_building = '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 22v-4h6v4"/></svg>';
$svg_cap      = '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/></svg>';
$svg_shield   = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>';
$svg_clock    = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';
$svg_zap      = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>';
$svg_badge    = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="15" r="6"/><path d="M9 11l-2-9h10l-2 9"/><path d="M12 15v6"/></svg>';
$svg_wallet   = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/></svg>';
$svg_target   = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>';
$svg_star     = '<svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';
?>

<main id="primary" class="rks-home">

    <!-- ============ HERO ============ -->
    <section class="rks-home__hero">
        <div class="rks-home__hero-bg" aria-hidden="true">
            <span class="rks-home__blob rks-home__blob--a"></span>
            <span class="rks-home__blob rks-home__blob--b"></span>
        </div>
        <div class="rks-container rks-home__hero-grid">
            <div class="rks-home__hero-text">
                <span class="rks-eyebrow">India&rsquo;s Trusted Learning Platform</span>
                <h1>Learn skills that <span>shape your future</span> &mdash; online or in person.</h1>
                <p>Expert-led courses, hands-on coaching and free university counselling. One trusted partner for every step of your learning journey.</p>
                <div class="rks-home__hero-actions">
                    <a href="<?php echo esc_url( home_url( '/courses/' ) ); ?>" class="rks-home__btn rks-home__btn--primary">Explore Courses <?php echo $svg_arrow; ?></a>
                    <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="rks-home__btn rks-home__btn--ghost">Talk to a Counsellor</a>
                </div>
                <ul class="rks-home__hero-ticks">
                    <li><span><?php echo $svg_check; ?></span> 10,000+ learners</li>
                    <li><span><?php echo $svg_check; ?></span> Verified certificates</li>
                    <li><span><?php echo $svg_check; ?></span> Mentor support</li>
                </ul>
            </div>
            <aside class="rks-home__hero-media">
                <div class="rks-home__hero-orbit" aria-hidden="true">
                    <span class="rks-home__hero-ring rks-home__hero-ring--1"></span>
                    <span class="rks-home__hero-ring rks-home__hero-ring--2"></span>
                    <span class="rks-home__hero-dot rks-home__hero-dot--1"></span>
                    <span class="rks-home__hero-dot rks-home__hero-dot--2"></span>
                    <span class="rks-home__hero-dot rks-home__hero-dot--3"></span>
                </div>
                <div class="rks-home__hero-frame">
                    <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/hero-img.png' ); ?>" alt="Student learning at Rishikul Shiksha" loading="eager">
                </div>
                <div class="rks-home__hero-float rks-home__hero-float--badge">
                    <span class="rks-home__hero-float-ic"><?php echo $svg_trophy; ?></span>
                    <div>
                        <small>Trusted by</small>
                        <strong>10,000+ students</strong>
                    </div>
                </div>
                <div class="rks-home__hero-float rks-home__hero-float--rating">
                    <span class="rks-home__hero-float-star">&#9733;</span>
                    <div>
                        <strong>4.9 / 5</strong>
                        <small>Student rating</small>
                    </div>
                </div>
                <div class="rks-home__hero-chip rks-home__hero-chip--excel" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="3" y1="9" x2="21" y2="9"/><path d="M14 14l4 4M18 14l-4 4"/></svg>
                    <span>Excel</span>
                </div>
                <div class="rks-home__hero-chip rks-home__hero-chip--tally" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><line x1="7" y1="9" x2="7" y2="15"/><line x1="11" y1="9" x2="11" y2="15"/><line x1="15" y1="9" x2="15" y2="15"/><line x1="19" y1="9" x2="19" y2="11"/></svg>
                    <span>Tally</span>
                </div>
                <div class="rks-home__hero-chip rks-home__hero-chip--word" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="13" y2="17"/></svg>
                    <span>MS Word</span>
                </div>
                <div class="rks-home__hero-chip rks-home__hero-chip--typing" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><line x1="6" y1="10" x2="6" y2="10"/><line x1="10" y1="10" x2="10" y2="10"/><line x1="14" y1="10" x2="14" y2="10"/><line x1="18" y1="10" x2="18" y2="10"/><line x1="7" y1="14" x2="17" y2="14"/></svg>
                    <span>Typing</span>
                </div>
                <div class="rks-home__hero-pill" aria-hidden="true">
                    <span class="rks-home__hero-pill-dot"></span>
                    Live Classes Now
                </div>
                <div class="rks-home__hero-platform" aria-hidden="true"></div>
            </aside>
        </div>
    </section>

    <!-- ============ STATS STRIP ============ -->
    <section class="rks-home__stats">
        <div class="rks-container">
            <div class="rks-home__stats-grid">
                <div>
                    <span class="rks-home__stats-ic"><?php echo $svg_users; ?></span>
                    <strong>10,000+</strong>
                    <small>Active Students</small>
                </div>
                <div>
                    <span class="rks-home__stats-ic"><?php echo $svg_book; ?></span>
                    <strong>200+</strong>
                    <small>Quality Courses</small>
                </div>
                <div>
                    <span class="rks-home__stats-ic"><?php echo $svg_trophy; ?></span>
                    <strong>50+</strong>
                    <small>Expert Mentors</small>
                </div>
                <div>
                    <span class="rks-home__stats-ic"><?php echo $svg_target; ?></span>
                    <strong>98%</strong>
                    <small>Success Rate</small>
                </div>
            </div>
        </div>
    </section>

    <!-- ============ NOTICES ============ -->
    <?php
    $rks_home_notices = [];
    if ( class_exists( '\RKSiksha\Core\Modules\Notices' ) ) {
        $rks_home_notices = \RKSiksha\Core\Modules\Notices::get_active( 5 );
    }
    ?>
    <?php if ( ! empty( $rks_home_notices ) ) : ?>
    <section class="rks-home__notices" aria-labelledby="rks-home-notices-title">
        <div class="rks-container">
            <div class="rks-home__notices-shell">
                <header class="rks-home__notices-head">
                    <div class="rks-home__notices-head-main">
                        <span class="rks-eyebrow">Notice Board</span>
                        <h2 id="rks-home-notices-title">Latest Notices</h2>
                    </div>
                    <p>Important updates, admissions, events and downloadable information for students.</p>
                </header>
                <div class="rks-home__notices-list">
                    <?php foreach ( $rks_home_notices as $notice ) : ?>
                        <?php
                        $notice_id     = (int) $notice->ID;
                        $is_important  = (bool) get_post_meta( $notice_id, \RKSiksha\Core\Modules\Notices::M_IMPORTANT, true );
                        $link_text     = (string) get_post_meta( $notice_id, \RKSiksha\Core\Modules\Notices::M_LINK_TEXT, true );
                        $link_url      = (string) get_post_meta( $notice_id, \RKSiksha\Core\Modules\Notices::M_LINK_URL, true );
                        $attachment_id = (int) get_post_meta( $notice_id, \RKSiksha\Core\Modules\Notices::M_ATTACHMENT, true );
                        $attachment_url = $attachment_id ? (string) wp_get_attachment_url( $attachment_id ) : '';
                        $attachment_label = '';

                        if ( $attachment_url ) {
                            $attachment_label = get_the_title( $attachment_id );
                            if ( '' === $attachment_label ) {
                                $attachment_label = basename( (string) wp_parse_url( $attachment_url, PHP_URL_PATH ) );
                            }
                        }

                        if ( '' !== $link_url && '/' === $link_url[0] && ( ! isset( $link_url[1] ) || '/' !== $link_url[1] ) ) {
                            $link_url = home_url( $link_url );
                        }

                        $notice_text = trim( (string) get_the_excerpt( $notice ) );
                        if ( '' === $notice_text ) {
                            $notice_text = wp_trim_words( wp_strip_all_tags( (string) $notice->post_content ), 26, '...' );
                        }

                        $link_text = '' !== $link_text ? $link_text : 'Read More';
                        $link_external = '' !== $link_url && 0 !== strpos( $link_url, home_url( '/' ) );
                        ?>
                        <article class="rks-home__notice<?php echo $is_important ? ' rks-home__notice--important' : ''; ?>">
                            <div class="rks-home__notice-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19V6a2 2 0 0 1 2-2h12v13H6a2 2 0 0 0-2 2Z"/><path d="M8 8h6"/><path d="M8 12h7"/><path d="M6 17h12"/></svg>
                            </div>
                            <div class="rks-home__notice-body">
                                <div class="rks-home__notice-meta">
                                    <time datetime="<?php echo esc_attr( get_the_date( 'c', $notice_id ) ); ?>"><?php echo esc_html( get_the_date( 'd M Y', $notice_id ) ); ?></time>
                                    <?php if ( $is_important ) : ?>
                                        <span>Important</span>
                                    <?php endif; ?>
                                </div>
                                <h3><?php echo esc_html( get_the_title( $notice_id ) ); ?></h3>
                                <?php if ( '' !== $notice_text ) : ?>
                                    <p><?php echo esc_html( $notice_text ); ?></p>
                                <?php endif; ?>
                                <?php if ( '' !== $link_url || '' !== $attachment_url ) : ?>
                                    <div class="rks-home__notice-actions">
                                        <?php if ( '' !== $link_url ) : ?>
                                            <a href="<?php echo esc_url( $link_url ); ?>" class="rks-home__notice-link"<?php echo $link_external ? ' target="_blank" rel="noopener"' : ''; ?>>
                                                <?php echo esc_html( $link_text ); ?> <?php echo $svg_arrow; ?>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ( '' !== $attachment_url ) : ?>
                                            <a href="<?php echo esc_url( $attachment_url ); ?>" class="rks-home__notice-file" target="_blank" rel="noopener">
                                                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7Z"/><path d="M14 2v5h5"/><path d="M9 14h6"/><path d="M9 18h4"/></svg>
                                                <?php echo esc_html( $attachment_label ?: 'Attachment' ); ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ============ PATHWAYS ============ -->
    <section class="rks-home__paths">
        <div class="rks-container">
            <header class="rks-home__head">
                <span class="rks-eyebrow">What We Offer</span>
                <h2>Three pathways, one promise</h2>
                <p>Pick the way you want to learn &mdash; we&rsquo;ll meet you there.</p>
            </header>
            <div class="rks-home__paths-grid">
                <article class="rks-home__path rks-home__path--mint">
                    <span class="rks-home__path-ic"><?php echo $svg_globe; ?></span>
                    <h3>Online Learning Platform</h3>
                    <p>Self-paced and live courses in coding, design, business and exam prep &mdash; learn anytime, anywhere.</p>
                    <a href="<?php echo esc_url( home_url( '/courses/' ) ); ?>" class="rks-home__path-link">Browse courses <?php echo $svg_arrow; ?></a>
                </article>
                <article class="rks-home__path rks-home__path--red">
                    <span class="rks-home__path-ic"><?php echo $svg_building; ?></span>
                    <h3>Offline Coaching</h3>
                    <p>Hands-on classroom training at our Vikasnagar campus &mdash; Tally, web dev, computer fundamentals and more.</p>
                    <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="rks-home__path-link">Visit our centre <?php echo $svg_arrow; ?></a>
                </article>
                <article class="rks-home__path rks-home__path--teal">
                    <span class="rks-home__path-ic"><?php echo $svg_cap; ?></span>
                    <h3>University Counselling</h3>
                    <p>Free expert counselling for Dehradun&rsquo;s top 6 universities &mdash; admissions, documents, scholarships.</p>
                    <a href="<?php echo esc_url( home_url( '/university-counselling/' ) ); ?>" class="rks-home__path-link">Get guidance <?php echo $svg_arrow; ?></a>
                </article>
            </div>
        </div>
    </section>

    <!-- ============ POPULAR COURSES ============ -->
    <section class="rks-home__courses" id="courses">
        <div class="rks-container">
            <header class="rks-home__head">
                <span class="rks-eyebrow">Trending Now</span>
                <h2>Popular Courses</h2>
                <p>Most enrolled courses by our community this month.</p>
            </header>
            <div class="rks-home__courses-grid">
                <?php
                // First: courses WITH a featured image, ordered by students DESC.
                $rks_with_thumb = get_posts( [
                    'post_type'      => 'rks_course',
                    'post_status'    => 'publish',
                    'posts_per_page' => 6,
                    'orderby'        => 'meta_value_num',
                    'meta_key'       => '_rks_course_students_count',
                    'order'          => 'DESC',
                    'meta_query'     => [
                        [ 'key' => '_thumbnail_id', 'compare' => 'EXISTS' ],
                    ],
                    'fields'         => 'ids',
                    'no_found_rows'  => true,
                ] );
                $rks_need = 6 - count( $rks_with_thumb );
                $rks_without_thumb = [];
                if ( $rks_need > 0 ) {
                    $rks_without_thumb = get_posts( [
                        'post_type'      => 'rks_course',
                        'post_status'    => 'publish',
                        'posts_per_page' => $rks_need,
                        'orderby'        => 'meta_value_num',
                        'meta_key'       => '_rks_course_students_count',
                        'order'          => 'DESC',
                        'post__not_in'   => $rks_with_thumb,
                        'fields'         => 'ids',
                        'no_found_rows'  => true,
                    ] );
                }
                $rks_pop_ids = array_slice( array_merge( $rks_with_thumb, $rks_without_thumb ), 0, 6 );
                $rks_pop_q = ! empty( $rks_pop_ids ) ? new WP_Query( [
                    'post_type'      => 'rks_course',
                    'post_status'    => 'publish',
                    'post__in'       => $rks_pop_ids,
                    'orderby'        => 'post__in',
                    'posts_per_page' => 6,
                    'no_found_rows'  => true,
                ] ) : new WP_Query();
                $rks_thumb_palette = [ 'web', 'data', 'design', 'biz' ];
                $rks_thumb_idx     = 0;
                if ( $rks_pop_q->have_posts() ) :
                    while ( $rks_pop_q->have_posts() ) : $rks_pop_q->the_post();
                        $pid       = get_the_ID();
                        $has_thumb = has_post_thumbnail( $pid );
                        $rating    = (float) get_post_meta( $pid, '_rks_course_rating', true );
                        $students  = (int)   get_post_meta( $pid, '_rks_course_students_count', true );
                        $is_paid   = (bool)  get_post_meta( $pid, '_rks_course_is_paid', true );
                        $price     = (float) get_post_meta( $pid, '_rks_course_price', true );
                        $short     = (string) get_post_meta( $pid, '_rks_course_short_desc', true );
                        if ( '' === $short ) { $short = get_the_excerpt(); }
                        $terms     = get_the_terms( $pid, 'rks_course_cat' );
                        $cat_name  = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : 'Course';
                        $thumb_cls = 'rks-home__course-thumb--' . $rks_thumb_palette[ $rks_thumb_idx % count( $rks_thumb_palette ) ];
                        $rks_thumb_idx++;
                ?>
                <article class="rks-home__course">
                    <div class="rks-home__course-thumb <?php echo esc_attr( $thumb_cls ); ?><?php echo $has_thumb ? ' rks-home__course-thumb--has-image' : ''; ?>">
                        <?php if ( $has_thumb ) : ?>
                            <img src="<?php echo esc_url( get_the_post_thumbnail_url( $pid, 'medium_large' ) ); ?>" alt="<?php echo esc_attr( get_the_title( $pid ) ); ?>" loading="lazy">
                        <?php endif; ?>
                        <span class="rks-home__course-cat"><?php echo esc_html( $cat_name ); ?></span>
                    </div>
                    <div class="rks-home__course-body">
                        <div class="rks-home__course-meta">
                            <span><?php echo $svg_star; ?> <?php echo esc_html( $rating > 0 ? number_format( $rating, 1 ) : '4.7' ); ?></span>
                            <span><?php echo esc_html( number_format( $students ) ); ?> students</span>
                        </div>
                        <h3><?php the_title(); ?></h3>
                        <p><?php echo esc_html( wp_trim_words( $short, 18, '…' ) ); ?></p>
                        <div class="rks-home__course-foot">
                            <span class="rks-home__course-price"><?php echo $is_paid ? '&#8377;' . number_format( $price, 0 ) : 'Free'; ?></span>
                            <a href="<?php the_permalink(); ?>" class="rks-home__course-link">View Course <?php echo $svg_arrow; ?></a>
                        </div>
                    </div>
                </article>
                <?php
                    endwhile;
                    wp_reset_postdata();
                endif;
                ?>
            </div>
            <div class="rks-home__courses-more">
                <a href="<?php echo esc_url( home_url( '/courses/' ) ); ?>" class="rks-home__btn rks-home__btn--ghost">View All Courses <?php echo $svg_arrow; ?></a>
            </div>
        </div>
    </section>

    <!-- ============ FOUNDER NOTE ============ -->
    <?php $rks_home_uploads = wp_get_upload_dir(); ?>
    <section class="rks-home__founder">
        <div class="rks-container rks-home__founder-grid">
            <div class="rks-home__founder-media">
                <div class="rks-home__founder-photo">
                    <img src="<?php echo esc_url( trailingslashit( $rks_home_uploads['baseurl'] ) . 'chairman.png' ); ?>" alt="Ravi Belwal, Chairman of Rishikul Shiksha">
                </div>
                <span class="rks-home__founder-badge">
                    <span class="rks-home__founder-badge-dot" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l2.4 2.4L18 4l1.6 3.6L23 9.6l-1.6 3.4L23 16.4 19.6 18 18 21.6 14.4 21 12 23 9.6 21 6 21.6 4.4 18 1 16.4l1.6-3.4L1 9.6 4.4 8 6 4.4 9.6 4z"/><polyline points="9 12 11 14 15 10"/></svg>
                    </span>
                    Chairman's Note
                </span>
            </div>
            <div class="rks-home__founder-text">
                <span class="rks-eyebrow">A Note From Our Founder</span>
                <h2>Built for learners who deserve more than just a syllabus.</h2>
                <p class="rks-home__founder-quote">&ldquo;Education changed my life. I wanted to build a place where it could change someone else&rsquo;s &mdash; without the price tag I had to pay. Every student at Rishikul Shiksha matters, and every small win is ours to celebrate.&rdquo;</p>
                <div class="rks-home__founder-sign">
                    <strong>Ravi Belwal</strong>
                    <small>Founder &amp; Chairman, Rishikul Shiksha</small>
                </div>
                <div class="rks-home__founder-actions">
                    <a href="<?php echo esc_url( home_url( '/about/' ) ); ?>" class="rks-home__btn rks-home__btn--primary">Read Our Story <?php echo $svg_arrow; ?></a>
                    <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="rks-home__btn rks-home__btn--ghost">Visit Our Centre</a>
                </div>
            </div>
        </div>
    </section>

    <!-- ============ WHY US ============ -->
    <section class="rks-home__why">
        <div class="rks-container">
            <header class="rks-home__head">
                <span class="rks-eyebrow">Why Rishikul Shiksha</span>
                <h2>Built around learners, not curriculums</h2>
            </header>
            <div class="rks-home__why-grid">
                <div class="rks-home__why-item">
                    <span class="rks-home__why-ic"><?php echo $svg_shield; ?></span>
                    <div>
                        <h4>Verified Certificates</h4>
                        <p>Publicly verifiable with a unique code &mdash; trusted by recruiters.</p>
                    </div>
                </div>
                <div class="rks-home__why-item">
                    <span class="rks-home__why-ic"><?php echo $svg_users; ?></span>
                    <div>
                        <h4>Real Mentor Support</h4>
                        <p>1-on-1 doubt sessions and WhatsApp groups &mdash; not chatbots.</p>
                    </div>
                </div>
                <div class="rks-home__why-item">
                    <span class="rks-home__why-ic"><?php echo $svg_clock; ?></span>
                    <div>
                        <h4>Learn At Your Pace</h4>
                        <p>Lifetime access to all materials. Study whenever you want.</p>
                    </div>
                </div>
                <div class="rks-home__why-item">
                    <span class="rks-home__why-ic"><?php echo $svg_wallet; ?></span>
                    <div>
                        <h4>Fair Pricing</h4>
                        <p>Easy installments and scholarship spots for serious learners.</p>
                    </div>
                </div>
                <div class="rks-home__why-item">
                    <span class="rks-home__why-ic"><?php echo $svg_badge; ?></span>
                    <div>
                        <h4>Industry-Aligned</h4>
                        <p>Curriculum refreshed every 6 months with practising professionals.</p>
                    </div>
                </div>
                <div class="rks-home__why-item">
                    <span class="rks-home__why-ic"><?php echo $svg_zap; ?></span>
                    <div>
                        <h4>Hybrid By Design</h4>
                        <p>Mix online lessons with offline practice at partner centres.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============ TESTIMONIALS ============ -->
    <section class="rks-home__tm">
        <div class="rks-container">
            <header class="rks-home__head">
                <span class="rks-eyebrow">Student Stories</span>
                <h2>What our learners say</h2>
            </header>
            <div class="rks-home__tm-grid">
                <article class="rks-home__tm-card">
                    <div class="rks-home__tm-quote" aria-hidden="true">&ldquo;</div>
                    <div class="rks-home__tm-stars"><?php echo str_repeat( $svg_star, 5 ); ?></div>
                    <p>I finished Tally Prime and ERP 9.0 here last year and within two months I was working as a junior accountant at a CA firm in Rajpur Road. Anil sir made GST entries feel really simple.</p>
                    <footer>
                        <div class="rks-home__tm-avatar">P</div>
                        <div>
                            <strong>Pankaj Negi</strong>
                            <small>Tally Prime &amp; ERP 9.0 &middot; Batch of 2024</small>
                        </div>
                    </footer>
                </article>
                <article class="rks-home__tm-card">
                    <div class="rks-home__tm-quote" aria-hidden="true">&ldquo;</div>
                    <div class="rks-home__tm-stars"><?php echo str_repeat( $svg_star, 5 ); ?></div>
                    <p>I cleared the Uttarakhand government typing test in my second attempt after joining the typing batch here. My English is now 42 WPM and Hindi (Krutidev) 32 WPM &mdash; the daily speed drills really helped.</p>
                    <footer>
                        <div class="rks-home__tm-avatar">A</div>
                        <div>
                            <strong>Anjali Bisht</strong>
                            <small>Hindi &amp; English Typing &middot; Joined 2024</small>
                        </div>
                    </footer>
                </article>
                <article class="rks-home__tm-card">
                    <div class="rks-home__tm-quote" aria-hidden="true">&ldquo;</div>
                    <div class="rks-home__tm-stars"><?php echo str_repeat( $svg_star, 5 ); ?></div>
                    <p>After the DTP and CorelDraw course I started taking small design jobs for shops near my area &mdash; wedding cards, visiting cards, banners. Now I run my own print shop in Premnagar.</p>
                    <footer>
                        <div class="rks-home__tm-avatar">V</div>
                        <div>
                            <strong>Vipin Rawat</strong>
                            <small>DTP &amp; CorelDraw &middot; Batch of 2023</small>
                        </div>
                    </footer>
                </article>
            </div>
        </div>
    </section>

    <!-- ============ HOW IT WORKS ============ -->
    <section class="rks-home__steps">
        <div class="rks-container">
            <header class="rks-home__head">
                <span class="rks-eyebrow">How It Works</span>
                <h2>From signup to certified in four simple steps</h2>
                <p>Whether you choose online or offline &mdash; the journey is the same: clear, guided and built for outcomes.</p>
            </header>
            <ol class="rks-home__steps-list">
                <li class="rks-home__step">
                    <div class="rks-home__step-num">1</div>
                    <h3>Pick Your Path</h3>
                    <p>Choose an online course, offline class, or a free university counselling session.</p>
                </li>
                <li class="rks-home__step">
                    <div class="rks-home__step-num">2</div>
                    <h3>Get Mentored</h3>
                    <p>Live classes, recorded lessons, and 1-on-1 doubt sessions with real mentors.</p>
                </li>
                <li class="rks-home__step">
                    <div class="rks-home__step-num">3</div>
                    <h3>Build &amp; Practice</h3>
                    <p>Hands-on projects, quizzes and assessments &mdash; learn by doing, not memorising.</p>
                </li>
                <li class="rks-home__step">
                    <div class="rks-home__step-num">4</div>
                    <h3>Get Certified</h3>
                    <p>Earn a verifiable certificate recruiters can validate with a single code.</p>
                </li>
            </ol>
        </div>
    </section>

    <!-- ============ FAQ ============ -->
    <section class="rks-home__faq">
        <div class="rks-container">
            <header class="rks-home__head">
                <span class="rks-eyebrow">FAQs</span>
                <h2>Quick answers to common questions</h2>
            </header>
            <div class="rks-home__faq-list">
                <details class="rks-home__faq-item">
                    <summary>How do I enrol in a course?</summary>
                    <p>Pick a course from the catalogue, click Enrol Now, and complete the payment. You&rsquo;ll get instant access in your student dashboard.</p>
                </details>
                <details class="rks-home__faq-item">
                    <summary>Do I get a certificate after completion?</summary>
                    <p>Yes. Every completed course awards a verifiable certificate with a unique code that recruiters can validate publicly on our site.</p>
                </details>
                <details class="rks-home__faq-item">
                    <summary>Is there mentor support during the course?</summary>
                    <p>Absolutely. You get access to live doubt sessions, WhatsApp mentor groups, and 1-on-1 guidance whenever you&rsquo;re stuck.</p>
                </details>
                <details class="rks-home__faq-item">
                    <summary>Can I attend offline classes too?</summary>
                    <p>Yes. Our Vikasnagar campus runs offline batches for Tally, web development, and computer fundamentals. Check the Coaching page for schedule.</p>
                </details>
                <details class="rks-home__faq-item">
                    <summary>Is university counselling really free?</summary>
                    <p>Completely free. We&rsquo;re paid by partner universities only after a student confirms admission, so there&rsquo;s no cost to you for the counselling itself.</p>
                </details>
                <details class="rks-home__faq-item">
                    <summary>Do you offer EMI or instalment payments?</summary>
                    <p>Yes, most paid courses support easy instalments. You can also apply for our merit and need-based scholarship spots.</p>
                </details>
            </div>
        </div>
    </section>

    <!-- ============ CTA ============ -->
    <section class="rks-home__cta">
        <div class="rks-container">
            <div class="rks-home__cta-card">
                <div>
                    <span class="rks-eyebrow rks-home__cta-eyebrow">Ready to Begin?</span>
                    <h2>Start your learning journey today</h2>
                    <p>Browse our courses or talk to a counsellor &mdash; either way, we&rsquo;ll help you figure out the next step.</p>
                </div>
                <div class="rks-home__cta-actions">
                    <a href="<?php echo esc_url( home_url( '/register/' ) ); ?>" class="rks-home__btn rks-home__btn--white">Get Started Free <?php echo $svg_arrow; ?></a>
                    <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="rks-home__btn rks-home__btn--ghost-light">Talk to Us</a>
                </div>
            </div>
        </div>
    </section>

</main>

<?php get_footer(); ?>
