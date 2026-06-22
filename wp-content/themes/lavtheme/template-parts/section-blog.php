<?php
defined( 'ABSPATH' ) || exit;

/**
 * Deterministic gradient thumbnail for posts lacking a featured image.
 */
if ( ! function_exists( 'lavtheme_blog_gradient' ) ) {
	function lavtheme_blog_gradient( $post_id ) {
		$pairs = array(
			array( '#4a9eff', '#2563eb' ),
			array( '#ff6b9d', '#db2777' ),
			array( '#334155', '#0f172a' ),
			array( '#ff7a1a', '#8b5cf6' ),
			array( '#10b981', '#047857' ),
			array( '#f59e0b', '#b45309' ),
			array( '#06b6d4', '#0e7490' ),
			array( '#a855f7', '#6d28d9' ),
		);
		$pair = $pairs[ absint( $post_id ) % count( $pairs ) ];
		$svg  = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 300" preserveAspectRatio="xMidYMid slice">'
			. '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">'
			. '<stop offset="0" stop-color="' . esc_attr( $pair[0] ) . '"/>'
			. '<stop offset="1" stop-color="' . esc_attr( $pair[1] ) . '"/>'
			. '</linearGradient></defs>'
			. '<rect width="240" height="300" fill="#0e0b09"/>'
			. '<rect width="240" height="300" fill="url(#g)" opacity="0.82"/>'
			. '<circle cx="60" cy="60" r="70" fill="#ffffff" opacity="0.07"/>'
			. '<circle cx="200" cy="250" r="80" fill="#000000" opacity="0.10"/>'
			. '</svg>';
		return 'data:image/svg+xml,' . rawurlencode( $svg );
	}
}

/**
 * Build inner markup for the blog carousel from the latest 10 posts.
 * Returns '' when there are no published posts (caller shows the sample).
 */
if ( ! function_exists( 'lavtheme_blog_cards_html' ) ) {
	function lavtheme_blog_cards_html() {
		$q = new WP_Query(
			array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'posts_per_page'      => 10,
				'ignore_sticky_posts' => 1,
				'no_found_rows'       => true,
			)
		);

		if ( ! $q->have_posts() ) {
			wp_reset_postdata();
			return '';
		}

		$out = '';
		$i   = 0;

		while ( $q->have_posts() ) {
			$q->the_post();
			$pid   = get_the_ID();
			$title = get_the_title();
			$link  = get_permalink();

			$sub = has_excerpt( $pid ) ? get_the_excerpt( $pid ) : wp_trim_words( wp_strip_all_tags( get_the_content() ), 16, '…' );

			$pill = 'Blog';
			$cats = get_the_category( $pid );
			if ( ! empty( $cats ) ) {
				$pill = $cats[0]->name;
			} else {
				$tags = get_the_tags( $pid );
				if ( ! empty( $tags ) ) {
					$pill = $tags[0]->name;
				}
			}

			if ( has_post_thumbnail( $pid ) ) {
				// Let WordPress emit width/height + srcset/sizes so the browser can
				// pull a right-sized file (cards render ~300px wide, not 768) — cuts
				// image payload and prevents layout shift.
				$img_html = get_the_post_thumbnail(
					$pid,
					'medium_large',
					array(
						'alt'      => $title,
						'loading'  => 'lazy',
						'decoding' => 'async',
						'sizes'    => '(max-width: 700px) 88vw, 300px',
					)
				);
			} else {
				$img_html = '<img decoding="async" loading="lazy" width="240" height="300" src="' . esc_url( lavtheme_blog_gradient( $pid ) ) . '" alt="' . esc_attr( $title ) . '">';
			}

			$author  = get_the_author();
			$initial = strtoupper( mb_substr( $author, 0, 1 ) );
			$read    = max( 1, (int) ceil( str_word_count( wp_strip_all_tags( get_the_content() ) ) / 200 ) );
			$date    = get_the_date( 'M j' );

			$active = ( 0 === $i ) ? ' is-active' : '';

			$out .= '<a class="glass post' . $active . '" href="' . esc_url( $link ) . '">'
				. '<div class="post-thumb">'
				. '<span class="post-pill">' . esc_html( $pill ) . '</span>'
				. $img_html
				. '</div>'
				. '<div class="post-body">'
				. '<div class="post-title">' . esc_html( $title ) . '</div>'
				. '<p class="post-sub">' . esc_html( $sub ) . '</p>'
				. '<div class="post-foot">'
				. '<span class="post-avatar">' . esc_html( $initial ) . '</span>'
				. '<div><div class="post-who">' . esc_html( $author ) . '</div>'
				. '<div class="post-sub2">' . esc_html( $read . ' min read · ' . $date ) . '</div></div>'
				. '<button class="post-save" type="button" aria-label="' . esc_attr( 'Save ' . $title ) . '" aria-pressed="false">'
				. '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>'
				. '</button>'
				. '</div></div></a>';

			$i++;
		}

		wp_reset_postdata();
		return $out;
	}
}

$lavtheme_blog_cards = lavtheme_blog_cards_html();
?><section class="block" id="blog"><div class="block-head"><div><div class="kicker">Insights</div><h2 class="block-title">Blog</h2></div></div><div class="blog-carousel"><button class="blog-arrow blog-arrow-prev" id="blogPrev" type="button" aria-label="Previous posts"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg></button><div class="blog-viewport" id="blogViewport"><div class="blog-track" id="blogTrack" role="region" aria-label="Blog posts carousel" tabindex="0"><?php if ( '' !== $lavtheme_blog_cards ) { echo $lavtheme_blog_cards; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ } else { ?><a class="glass post is-active" href="#blog"><div class="post-thumb"><span class="post-pill">Latest AI Tools</span><img decoding="async" src="data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%20viewBox%3D%220%200%20240%20300%22%20preserveAspectRatio%3D%22xMidYMid%20slice%22%3E%20%3Cdefs%3E%3ClinearGradient%20id%3D%22tai%22%20x1%3D%220%22%20y1%3D%220%22%20x2%3D%221%22%20y2%3D%221%22%3E%3Cstop%20offset%3D%220%22%20stop-color%3D%22%234a9eff%22/%3E%3Cstop%20offset%3D%221%22%20stop-color%3D%22%232563eb%22/%3E%3C/linearGradient%3E%3C/defs%3E%20%3Crect%20width%3D%22240%22%20height%3D%22300%22%20fill%3D%22%230e0b09%22/%3E%20%3Crect%20width%3D%22240%22%20height%3D%22300%22%20fill%3D%22url%28%23tai%29%22%20opacity%3D%220.82%22/%3E%20%3Ccircle%20cx%3D%2260%22%20cy%3D%2260%22%20r%3D%2270%22%20fill%3D%22%23ffffff%22%20opacity%3D%220.07%22/%3E%20%3Ccircle%20cx%3D%22200%22%20cy%3D%22250%22%20r%3D%2280%22%20fill%3D%22%23000000%22%20opacity%3D%220.10%22/%3E%20%3Cg%20transform%3D%22translate%28120%20150%29%22%20stroke%3D%22%23ffffff%22%20stroke-width%3D%227%22%20fill%3D%22none%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%20opacity%3D%220.95%22%3E%20%3Ccircle%20cx%3D%220%22%20cy%3D%220%22%20r%3D%2234%22/%3E%3Cpath%20d%3D%22M0%20-50%20v10%20M0%2040%20v10%20M-50%200%20h10%20M40%200%20h10%20M-36%20-36%20l8%208%20M28%2028%20l8%208%20M36%20-36%20l-8%208%20M-28%2028%20l-8%208%22/%3E%20%3C/g%3E%20%3C/svg%3E" alt="5 AI tools worth your stack this quarter"></div><div class="post-body"><div class="post-title">5 AI tools worth your stack this quarter</div><p class="post-sub">Field-tested picks that actually save hours, not hype.</p><div class="post-foot"><span class="post-avatar">S</span><div><div class="post-who">Sara K.</div><div class="post-sub2">6 min read · May 28</div></div><button class="post-save" aria-label="Save article" aria-pressed="false"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg></button></div></div></a><a class="glass post" href="#blog"><div class="post-thumb"><span class="post-pill">Creative Websites</span><img decoding="async" src="data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%20viewBox%3D%220%200%20240%20300%22%20preserveAspectRatio%3D%22xMidYMid%20slice%22%3E%20%3Cdefs%3E%3ClinearGradient%20id%3D%22tweb%22%20x1%3D%220%22%20y1%3D%220%22%20x2%3D%221%22%20y2%3D%221%22%3E%3Cstop%20offset%3D%220%22%20stop-color%3D%22%23ff6b9d%22/%3E%3Cstop%20offset%3D%221%22%20stop-color%3D%22%23db2777%22/%3E%3C/linearGradient%3E%3C/defs%3E%20%3Crect%20width%3D%22240%22%20height%3D%22300%22%20fill%3D%22%230e0b09%22/%3E%20%3Crect%20width%3D%22240%22%20height%3D%22300%22%20fill%3D%22url%28%23tweb%29%22%20opacity%3D%220.82%22/%3E%20%3Ccircle%20cx%3D%2260%22%20cy%3D%2260%22%20r%3D%2270%22%20fill%3D%22%23ffffff%22%20opacity%3D%220.07%22/%3E%20%3Ccircle%20cx%3D%22200%22%20cy%3D%22250%22%20r%3D%2280%22%20fill%3D%22%23000000%22%20opacity%3D%220.10%22/%3E%20%3Cg%20transform%3D%22translate%28120%20150%29%22%20stroke%3D%22%23ffffff%22%20stroke-width%3D%227%22%20fill%3D%22none%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%20opacity%3D%220.95%22%3E%20%3Crect%20x%3D%22-44%22%20y%3D%22-34%22%20width%3D%2288%22%20height%3D%2268%22%20rx%3D%2210%22/%3E%3Cpath%20d%3D%22M-44%20-14%20h88%22/%3E%3Ccircle%20cx%3D%22-32%22%20cy%3D%22-24%22%20r%3D%223%22%20fill%3D%22%23ffffff%22%20stroke%3D%22none%22/%3E%3Ccircle%20cx%3D%22-22%22%20cy%3D%22-24%22%20r%3D%223%22%20fill%3D%22%23ffffff%22%20stroke%3D%22none%22/%3E%20%3C/g%3E%20%3C/svg%3E" alt="Sites that break the grid — and still convert"></div><div class="post-body"><div class="post-title">Sites that break the grid — and still convert</div><p class="post-sub">How unconventional layouts keep usability intact.</p><div class="post-foot"><span class="post-avatar">D</span><div><div class="post-who">Devran</div><div class="post-sub2">8 min read · May 21</div></div><button class="post-save" aria-label="Save article" aria-pressed="false"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg></button></div></div></a><a class="glass post" href="#blog"><div class="post-thumb"><span class="post-pill">Hacker Biographies</span><img decoding="async" src="data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%20viewBox%3D%220%200%20240%20300%22%20preserveAspectRatio%3D%22xMidYMid%20slice%22%3E%20%3Cdefs%3E%3ClinearGradient%20id%3D%22thacker%22%20x1%3D%220%22%20y1%3D%220%22%20x2%3D%221%22%20y2%3D%221%22%3E%3Cstop%20offset%3D%220%22%20stop-color%3D%22%23334155%22/%3E%3Cstop%20offset%3D%221%22%20stop-color%3D%22%230f172a%22/%3E%3C/linearGradient%3E%3C/defs%3E%20%3Crect%20width%3D%22240%22%20height%3D%22300%22%20fill%3D%22%230e0b09%22/%3E%20%3Crect%20width%3D%22240%22%20height%3D%22300%22%20fill%3D%22url%28%23thacker%29%22%20opacity%3D%220.82%22/%3E%20%3Ccircle%20cx%3D%2260%22%20cy%3D%2260%22%20r%3D%2270%22%20fill%3D%22%23ffffff%22%20opacity%3D%220.07%22/%3E%20%3Ccircle%20cx%3D%22200%22%20cy%3D%22250%22%20r%3D%2280%22%20fill%3D%22%23000000%22%20opacity%3D%220.10%22/%3E%20%3Cg%20transform%3D%22translate%28120%20150%29%22%20stroke%3D%22%23ffffff%22%20stroke-width%3D%227%22%20fill%3D%22none%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%20opacity%3D%220.95%22%3E%20%3Cpath%20d%3D%22M-30%20-28%20l-18%2028%2018%2028%20M30%20-28%20l18%2028%20-18%2028%20M10%20-34%20l-20%2068%22/%3E%20%3C/g%3E%20%3C/svg%3E" alt="The self-taught engineers who shaped the web"></div><div class="post-body"><div class="post-title">The self-taught engineers who shaped the web</div><p class="post-sub">Lessons from coders who learned outside the classroom.</p><div class="post-foot"><span class="post-avatar">M</span><div><div class="post-who">Mehdi R.</div><div class="post-sub2">11 min read · May 14</div></div><button class="post-save" aria-label="Save article" aria-pressed="false"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg></button></div></div></a><a class="glass post" href="#blog"><div class="post-thumb"><span class="post-pill">Top Developers</span><img decoding="async" src="data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%20viewBox%3D%220%200%20240%20300%22%20preserveAspectRatio%3D%22xMidYMid%20slice%22%3E%20%3Cdefs%3E%3ClinearGradient%20id%3D%22tdev%22%20x1%3D%220%22%20y1%3D%220%22%20x2%3D%221%22%20y2%3D%221%22%3E%3Cstop%20offset%3D%220%22%20stop-color%3D%22%23ff7a1a%22/%3E%3Cstop%20offset%3D%221%22%20stop-color%3D%22%238b5cf6%22/%3E%3C/linearGradient%3E%3C/defs%3E%20%3Crect%20width%3D%22240%22%20height%3D%22300%22%20fill%3D%22%230e0b09%22/%3E%20%3Crect%20width%3D%22240%22%20height%3D%22300%22%20fill%3D%22url%28%23tdev%29%22%20opacity%3D%220.82%22/%3E%20%3Ccircle%20cx%3D%2260%22%20cy%3D%2260%22%20r%3D%2270%22%20fill%3D%22%23ffffff%22%20opacity%3D%220.07%22/%3E%20%3Ccircle%20cx%3D%22200%22%20cy%3D%22250%22%20r%3D%2280%22%20fill%3D%22%23000000%22%20opacity%3D%220.10%22/%3E%20%3Cg%20transform%3D%22translate%28120%20150%29%22%20stroke%3D%22%23ffffff%22%20stroke-width%3D%227%22%20fill%3D%22none%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%20opacity%3D%220.95%22%3E%20%3Cpath%20d%3D%22M-22%20-34%20a40%2040%200%20100%2068%20M22%20-34%20a40%2040%200%20110%2068%20M-10%2038%20h20%22/%3E%20%3C/g%3E%20%3C/svg%3E" alt="Dev habits that quietly compound over years"></div><div class="post-body"><div class="post-title">Dev habits that quietly compound over years</div><p class="post-sub">Small daily routines behind consistently great engineers.</p><div class="post-foot"><span class="post-avatar">S</span><div><div class="post-who">Sara K.</div><div class="post-sub2">7 min read · May 7</div></div><button class="post-save" aria-label="Save article" aria-pressed="false"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg></button></div></div></a><?php } ?></div></div><button class="blog-arrow blog-arrow-next" id="blogNext" type="button" aria-label="Next posts"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></button><div class="blog-dots" id="blogDots" role="tablist" aria-label="Carousel pages"></div></div></section>