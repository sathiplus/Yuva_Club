<?php
require __DIR__ . '/portal-lib.php';

portal_header('Organization Access');
?>
<main>
  <section class="band">
    <div class="form-shell portal-narrow">
      <div class="section-head">
        <p class="eyebrow">Organization Access</p>
        <h1>Organization accounts are invitation-only.</h1>
        <p>Organizations cannot self-register at this stage. YUVA Club platform administrators create organizations, configure subscriptions, enable platform features, and invite organization administrators.</p>
      </div>

      <div class="form-card">
        <h2>Organization Administrator Workflow</h2>
        <p>Master Admin creates the organization, assigns an organization administrator, and sends an email invitation. The invited administrator activates the account and sets a password.</p>
        <p><a class="button primary" href="contact.html">Contact YUVA Club</a> <a class="button ghost" href="admin-login.php">Admin Login</a></p>
      </div>
    </div>
  </section>
</main>
<?php portal_footer(); ?>
