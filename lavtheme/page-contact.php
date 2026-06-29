<?php
/**
 * Template for the Contact / "Free consultation" page (slug: contact).
 * WordPress auto-selects this via the page-{slug}.php convention.
 * Form + handler live in inc/contact.php.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

get_header();

$lav_sent = isset( $_GET['sent'] ) && '1' === $_GET['sent']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$lav_err  = isset( $_GET['err'] ) ? sanitize_key( wp_unslash( $_GET['err'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>
<section class="lav-sec" id="contact" aria-labelledby="contact-title">
  <div class="lav-wrap">
    <header class="lav-head lav-head--center">
      <span class="lav-head__eyebrow lav-eyebrow">Free consultation</span>
      <h1 class="lav-head__title" id="contact-title">Hand your idea to one responsible person</h1>
      <p class="lav-head__intro">Tell us what you want to build. A veteran developer reviews it, scopes a milestone plan, and gives you a transparent estimate &mdash; before you pay anything.</p>
    </header>

    <?php if ( $lav_sent ) : ?>

      <div class="lav-wrap--narrow" style="margin-inline:auto">
        <div class="glass glass--tint-blue lav-success" role="status">
          <span class="lav-ico lav-ico--lg lav-ico--teal"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg></span>
          <h2 class="lav-card__title">Thank you &mdash; your request is in.</h2>
          <p class="lav-card__body">We&rsquo;ll get back to you within one business day. No payment moves until you approve each milestone.</p>
          <a class="lav-btn lav-btn--glass" href="<?php echo esc_url( home_url( '/' ) ); ?>">Back to home</a>
        </div>
      </div>

    <?php else : ?>

      <div class="lav-split">
        <div class="lav-reveal">
          <ul class="lav-criteria">
            <li>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
              One accountable manager, from first meeting to final delivery
            </li>
            <li>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v18l3-2 2 2 2-2 2 2 2-2 3 2V8z"/><path d="M14 2v6h6"/></svg>
              A transparent estimate before any work starts
            </li>
            <li>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
              Milestone-based, secure payment &mdash; you approve, then pay
            </li>
          </ul>
        </div>

        <div class="lav-reveal">
          <?php if ( $lav_err ) : ?>
            <p class="lav-err" role="alert" style="margin-bottom:var(--lav-space-3)">
              <?php echo 'fields' === $lav_err ? 'Please fill in your name, a valid email, and a message.' : 'Something went wrong &mdash; please try again.'; ?>
            </p>
          <?php endif; ?>
          <?php echo lavzen_contact_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- form markup is built from safe, escaped parts. ?>
        </div>
      </div>

    <?php endif; ?>
  </div>
</section>
<?php
get_footer();
