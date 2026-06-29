<?php
/**
 * Section: FAQ (§8, accordion) + Final CTA (§9).
 * Copy verbatim from the structure file. #faq is the navbar "Pricing" target;
 * #contact is the primary-CTA target (the Free-consultation button → email for
 * now; a real Contact page is added in Phase 4). Emoji → SVG.
 *
 * @package lavtheme
 */
defined( 'ABSPATH' ) || exit;
?>
<section class="lav-sec" id="faq" aria-labelledby="faq-title">
  <div class="lav-wrap">
    <header class="lav-head lav-head--center lav-reveal">
      <span class="lav-head__eyebrow lav-eyebrow">FAQ</span>
      <h2 class="lav-head__title" id="faq-title">Questions, answered before you ask</h2>
    </header>

    <div class="lav-faq" data-lav-faq>
      <details class="lav-faq__item glass glass--crystal lav-reveal">
        <summary class="lav-faq__q">How is pricing calculated?
          <svg class="lav-faq__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <p class="lav-faq__a">Based on the scope and complexity of the project; you get a transparent estimate before starting, and you don&rsquo;t pay until you approve it.</p>
      </details>

      <details class="lav-faq__item glass glass--crystal lav-reveal">
        <summary class="lav-faq__q">What if I&rsquo;m not happy with the result?
          <svg class="lav-faq__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <p class="lav-faq__a">Payment is milestone-based; you approve each milestone&rsquo;s output before paying.</p>
      </details>

      <details class="lav-faq__item glass glass--crystal lav-reveal">
        <summary class="lav-faq__q">How is this different from a regular agency?
          <svg class="lav-faq__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <p class="lav-faq__a">At an agency, your contact is a salesperson; here it&rsquo;s an experienced engineer who stands on the side of quality, not sales.</p>
      </details>

      <details class="lav-faq__item glass glass--crystal lav-reveal">
        <summary class="lav-faq__q">What if the manager leaves mid-project too?
          <svg class="lav-faq__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <p class="lav-faq__a">The manager has a backup team and owns delivery; the project doesn&rsquo;t get abandoned.</p>
      </details>
    </div>
  </div>
</section>

<section class="lav-sec" id="contact" aria-labelledby="final-title">
  <div class="lav-wrap lav-wrap--narrow">
    <div class="glass glass--deep lav-ctaband lav-reveal">
      <span class="lav-ctaband__glow" aria-hidden="true"></span>
      <div class="lav-ctaband__inner">
        <h2 class="lav-ctaband__title" id="final-title">Hand your idea to one responsible person</h2>
        <p class="lav-ctaband__sub">Start today. The first step costs nothing.</p>
        <a class="lav-btn lav-btn--cta" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">
          Get a free consultation
          <svg class="lav-btn__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </a>
      </div>
    </div>
  </div>
</section>
