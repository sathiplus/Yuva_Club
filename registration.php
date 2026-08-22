<?php
declare(strict_types=1);

require __DIR__ . '/portal-lib.php';

$status = $_GET['status'] ?? '';
$studentId = $_GET['id'] ?? '';
$registrationId = $_GET['registration'] ?? '';
$registrationFlash = registration_flash_take();
$validationReason = (string) ($_GET['reason'] ?? ($registrationFlash['reason'] ?? ''));
$validationMessages = [
    'incomplete-schedule-pair' => 'Complete both the day and time for each availability row, or clear the row.',
    'missing-required-field' => 'Complete all required fields before submitting the registration.',
    'invalid-or-missing-age' => 'Enter a valid date of birth so we can calculate eligibility.',
    'age-below-minimum' => 'YUVA Club registration is for students age 13 and older.',
    'age-above-maximum' => 'YUVA Club registration is currently for students age 21 and younger.',
    'missing-agreement' => 'Accept all three agreements before submitting the registration.',
    'persistence-failure' => 'We could not save the registration. Please try again or contact support.',
    'empty-generated-student-id' => 'We could not generate the student ID. Please try again or contact support.',
    'weak-password' => 'Password must be at least 8 characters and include uppercase, lowercase, number, and special character.',
    'password-mismatch' => 'Password and confirmation must match.',
];
$validationMessage = $validationMessages[$validationReason] ?? '';
$registrationFlashValues = is_array($registrationFlash['values'] ?? null) ? $registrationFlash['values'] : [];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Register | Yuva Club</title>
  <meta name="description" content="Create a YUVA Club student account and choose learning interests, presentation goals, and optional availability preferences.">
  <meta property="og:title" content="Register | Yuva Club">
  <meta property="og:description" content="Create a YUVA Club student account and choose learning interests, presentation goals, and optional availability preferences.">
  <meta property="og:image" content="https://www.yuvaclub.app/assets/logo.png">
  <meta property="og:url" content="https://www.yuvaclub.app/registration.php">
  <meta property="og:type" content="website">
  <link rel="canonical" href="https://www.yuvaclub.app/registration.php">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="YUVA Club Registration">
  <meta name="twitter:description" content="Create a YUVA Club student account.">
  <meta name="twitter:image" content="https://www.yuvaclub.app/assets/logo.png">
  <script type="application/ld+json">{"@context":"https://schema.org","@type":"EducationalOrganization","name":"YUVA Club","url":"https://www.yuvaclub.app","description":"Empowering Young Minds to Learn, Lead and Inspire."}</script>
  <link rel="icon" href="assets/logo.png" type="image/png">
  <link rel="stylesheet" href="assets/site.css?v=release-1.0.2-20260802">
  <link rel="stylesheet" href="assets/public-site.css?v=release-1.0.2-20260802">
  <script src="assets/app.js?v=release-1.0.2-20260802" defer></script>
</head>
<body class="horizon-home horizon-registration">
  <a class="public-skip-link" href="#main-content">Skip to main content</a>
  <header class="site-header horizon-header" data-public-header>
    <a class="brand horizon-brand" href="index.html" aria-label="YUVA Club home">
      <img src="assets/logo-public.webp" alt="" width="58" height="58">
      <span><strong>YUVA</strong> Club</span>
    </a>
    <button class="public-menu-button" type="button" aria-expanded="false" aria-controls="public-navigation">
      <span class="sr-only">Open navigation</span><span aria-hidden="true"></span><span aria-hidden="true"></span><span aria-hidden="true"></span>
    </button>
    <nav class="nav horizon-nav" id="public-navigation" aria-label="Main navigation">
      <a href="index.html">Home</a>
      <a href="programs.html">Programs</a>
      <a href="programs.html#how-it-works">How It Works</a>
      <a href="resources.html">Resources</a>
      <a href="about.html">About</a>
      <details class="public-login-menu">
        <summary>Login <span aria-hidden="true">&#9662;</span></summary>
        <div class="public-login-panel">
          <a href="portal-login.php"><strong>Student Portal</strong><span>Continue your leadership journey</span></a>
          <a href="parent-login.php"><strong>Parent Portal</strong><span>See how your child is growing</span></a>
          <a href="admin-login.php"><strong>Organization Admin</strong><span>Manage your organization</span></a>
          <a href="admin-login.php"><strong>Master Admin</strong><span>Manage the YUVA platform</span></a>
        </div>
      </details>
      <a class="nav-register" href="registration.php">Register</a>
    </nav>
  </header>

  <main id="main-content">
    <section class="registration-hero" aria-labelledby="registration-title">
      <div class="horizon-container registration-hero-grid">
        <div><p class="horizon-kicker">Registration is free</p><h1 id="registration-title">Start Your Leadership Journey</h1><p>Take the first step toward greater confidence, clearer communication, stronger thinking, and purposeful leadership.</p></div>
        <div class="registration-included"><p class="card-label">Your free experience includes</p><ul><li>First two presentations</li><li>Practice Studio</li><li>Presentation Studio</li><li>Leadership Journey</li><li>Challenges</li></ul><p>AI Mentor is not included in the free experience.</p></div>
      </div>
    </section>
    <section class="registration-path" aria-labelledby="registration-path-title"><div class="horizon-container"><h2 id="registration-path-title">A clear path from interest to approval.</h2><ol><li><span>01</span><strong>Choose your path</strong><p>Students continue below. Organizations request a demo.</p></li><li><span>02</span><strong>Understand what is included</strong><p>Begin free with the core YUVA experience.</p></li><li><span>03</span><strong>Complete registration</strong><p>Use the existing secure student form.</p></li><li><span>04</span><strong>Receive the next step</strong><p>Registration remains subject to the current approval workflow.</p></li></ol><p class="organization-path-note"><strong>Representing a school or organization?</strong> <a href="demo-request.php">Request a School or Organization Demo <span aria-hidden="true">→</span></a></p></div></section>
    <section class="registration-form-section">
      <div class="form-shell horizon-registration-shell">
        <div class="section-head">
          <p class="eyebrow">Student registration</p>
          <h2>Tell us about the student.</h2>
          <p>Register for a lifelong Yuva Club ID, create your student login, and tell us about your learning interests and presentation goals.</p>
        </div>

        <?php if ($status === 'success'): ?>
          <?php if ($registrationId !== ''): ?>
            <div class="form-status success">Thank you. Your registration was submitted successfully. We will review it and send the Yuva Club ID after approval.</div>
          <?php else: ?>
            <div class="form-status success">Thank you. Your registration was submitted successfully<?php echo $studentId !== '' ? ' with Yuva Club ID ' . htmlspecialchars($studentId, ENT_QUOTES, 'UTF-8') : ''; ?>.</div>
          <?php endif; ?>
        <?php elseif ($status === 'error'): ?>
          <div class="form-status error" role="alert" aria-live="assertive"><?php echo e($validationMessage !== '' ? $validationMessage : 'Please review the highlighted fields and try again.'); ?></div>
        <?php elseif ($status === 'password-error'): ?>
          <div class="form-status error" role="alert" aria-live="assertive"><?php echo e($validationMessage !== '' ? $validationMessage : 'Review the password fields and try again.'); ?></div>
        <?php elseif ($status === 'security-error'): ?>
          <div class="form-status error">This form expired. Please try again.</div>
        <?php endif; ?>

        <form class="form-card" action="submit-registration.php" method="post">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="form_name" value="Yuva Club Registration">

          <h2>Student Information & Contact</h2>
          <div class="field-grid">
            <div class="field">
              <label for="student_first_name">Student First Name *</label>
              <input id="student_first_name" name="student_first_name" type="text" required autocomplete="given-name">
            </div>

            <div class="field">
              <label for="student_last_name">Student Last Name *</label>
              <input id="student_last_name" name="student_last_name" type="text" required autocomplete="family-name">
            </div>

            <div class="field">
              <label for="preferred_name">Preferred Name</label>
              <input id="preferred_name" name="preferred_name" type="text">
            </div>

            <div class="field">
              <label for="date_of_birth">Date of Birth *</label>
              <input id="date_of_birth" name="date_of_birth" type="date" required>
            </div>

            <div class="field">
              <label for="age">Age</label>
              <input id="age" name="age" type="number" min="1" max="30" readonly>
            </div>

            <div class="field">
              <label for="program_group">Membership Group</label>
              <input id="program_group" name="program_group" type="text" readonly placeholder="Auto-selected by age">
            </div>

            <div class="field">
              <label for="grade">Grade *</label>
              <select id="grade" name="grade" required>
                <option value="">Select grade</option>
                <option>8th Grade</option>
                <option>9th Grade</option>
                <option>10th Grade</option>
                <option>11th Grade</option>
                <option>12th Grade</option>
                <option>College 1st Year</option>
                <option>College 2nd Year</option>
                <option>College 3rd Year</option>
                <option>College 4th Year</option>
              </select>
            </div>

            <div class="field">
              <label for="school">School *</label>
              <input id="school" name="school" type="text" required>
            </div>

            <div class="field">
              <label for="city_state">City/State *</label>
              <input id="city_state" name="city_state" type="text" required placeholder="City, State">
            </div>
            <div class="field">
              <label for="student_email">Student Email</label>
              <input id="student_email" name="student_email" type="email" autocomplete="email">
            </div>

            <div class="field">
              <label for="account_password">Create Password *</label>
              <input id="account_password" name="account_password" type="password" minlength="8" required autocomplete="new-password" aria-describedby="password_help">
              <p class="form-note" id="password_help">Use at least 8 characters with uppercase, lowercase, number, and special character.</p>
            </div>

            <div class="field">
              <label for="account_password_confirm">Confirm Password *</label>
              <input id="account_password_confirm" name="account_password_confirm" type="password" minlength="8" required autocomplete="new-password">
            </div>

            <div class="field">
              <label for="student_phone">Student Phone Number</label>
              <input id="student_phone" name="student_phone" type="tel" autocomplete="tel">
            </div>

            <div class="field">
              <label for="whatsapp_contact">WhatsApp Username / Number</label>
              <input id="whatsapp_contact" name="whatsapp_contact" type="text" placeholder="Username or phone number">
            </div>
          </div>

          <h2>Parent/Guardian Information</h2>
          <div class="field-grid">
            <div class="field">
              <label for="parent_name">Parent/Guardian Name *</label>
              <input id="parent_name" name="parent_name" type="text" required autocomplete="name">
            </div>

            <div class="field">
              <label for="relationship">Relationship *</label>
              <select id="relationship" name="relationship" required>
                <option value="">Select relationship</option>
                <option>Mother</option>
                <option>Father</option>
                <option>Guardian</option>
                <option>Grandparent</option>
                <option>Other</option>
              </select>
            </div>

            <div class="field">
              <label for="parent_email">Parent Email *</label>
              <input id="parent_email" name="parent_email" type="email" required autocomplete="email">
            </div>

            <div class="field">
              <label for="parent_phone">Parent Phone Number *</label>
              <input id="parent_phone" name="parent_phone" type="tel" required autocomplete="tel">
            </div>
          </div>

          <fieldset class="choice-group">
            <legend>Interests</legend>
            <div class="choice-grid">
              <label><input type="checkbox" name="interests[]" value="Leadership & Inspiration"> Leadership & Inspiration</label>
              <label><input type="checkbox" name="interests[]" value="Science & Technology"> Science & Technology</label>
              <label><input type="checkbox" name="interests[]" value="Business & Entrepreneurship"> Business & Entrepreneurship</label>
              <label><input type="checkbox" name="interests[]" value="History & Civilization"> History & Civilization</label>
              <label><input type="checkbox" name="interests[]" value="Geography & Cultures"> Geography & Cultures</label>
              <label><input type="checkbox" name="interests[]" value="Environment"> Environment</label>
              <label><input type="checkbox" name="interests[]" value="Health & Wellness"> Health & Wellness</label>
              <label><input type="checkbox" name="interests[]" value="Books & Literature"> Books & Literature</label>
              <label><input type="checkbox" name="interests[]" value="Arts & Creativity"> Arts & Creativity</label>
              <label><input type="checkbox" name="interests[]" value="Sports"> Sports</label>
              <label><input type="checkbox" name="interests[]" value="Digital Skills"> Digital Skills</label>
              <label><input type="checkbox" name="interests[]" value="Communication"> Communication</label>
              <label><input type="checkbox" name="interests[]" value="Community & Service"> Community & Service</label>
              <label><input type="checkbox" name="interests[]" value="Career Exploration"> Career Exploration</label>
            </div>
            <div class="field">
              <label for="interest_other">Other Interest</label>
              <input id="interest_other" name="interest_other" type="text">
            </div>
          </fieldset>

          <h2>Participation</h2>
          <div class="field">
            <label for="join_reason">Why do you want to join Yuva Club? *</label>
            <textarea id="join_reason" name="join_reason" required></textarea>
          </div>

          <div class="field-grid">
            <div class="field">
              <label for="presentation_experience">Have you given presentations before? *</label>
              <select id="presentation_experience" name="presentation_experience" required>
                <option value="">Select one</option>
                <option>Yes</option>
                <option>No</option>
                <option>A little</option>
              </select>
            </div>

            <div class="field">
              <label for="presentation_topics">What topics are you interested in presenting? *</label>
              <textarea id="presentation_topics" name="presentation_topics" required></textarea>
            </div>
          </div>

          <fieldset class="choice-group">
            <legend>Availability Preferences</legend>
            <p class="form-note">Optional. Share general availability so YUVA Club or your organization can recommend events, mentor sessions, and presentation opportunities later.</p>
            <div class="preference-grid">
              <div class="preference-row">
                <div class="field">
                  <label for="preferred_day_1">First Availability Day</label>
                  <select id="preferred_day_1" name="preferred_day_1">
                    <option value="">Select day</option>
                    <option>Monday</option>
                    <option>Tuesday</option>
                    <option>Wednesday</option>
                    <option>Thursday</option>
                    <option>Friday</option>
                    <option>Saturday</option>
                    <option>Sunday</option>
                  </select>
                </div>
                <div class="field">
                  <label for="preferred_time_1">First Availability Time</label>
                  <input id="preferred_time_1" name="preferred_time_1" type="time" aria-describedby="schedule_error_1">
                  <span id="schedule_error_1" class="form-note schedule-error" hidden>Choose both a day and time, or clear this row.</span>
                </div>
              </div>

              <div class="preference-row">
                <div class="field">
                  <label for="preferred_day_2">Second Availability Day</label>
                  <select id="preferred_day_2" name="preferred_day_2">
                    <option value="">Select day</option>
                    <option>Monday</option>
                    <option>Tuesday</option>
                    <option>Wednesday</option>
                    <option>Thursday</option>
                    <option>Friday</option>
                    <option>Saturday</option>
                    <option>Sunday</option>
                  </select>
                </div>
                <div class="field">
                  <label for="preferred_time_2">Second Availability Time</label>
                  <input id="preferred_time_2" name="preferred_time_2" type="time" aria-describedby="schedule_error_2">
                  <span id="schedule_error_2" class="form-note schedule-error" hidden>Choose both a day and time, or clear this row.</span>
                </div>
              </div>

              <div class="preference-row">
                <div class="field">
                  <label for="preferred_day_3">Third Availability Day</label>
                  <select id="preferred_day_3" name="preferred_day_3">
                    <option value="">Select day</option>
                    <option>Monday</option>
                    <option>Tuesday</option>
                    <option>Wednesday</option>
                    <option>Thursday</option>
                    <option>Friday</option>
                    <option>Saturday</option>
                    <option>Sunday</option>
                  </select>
                </div>
                <div class="field">
                  <label for="preferred_time_3">Third Availability Time</label>
                  <input id="preferred_time_3" name="preferred_time_3" type="time" aria-describedby="schedule_error_3">
                  <span id="schedule_error_3" class="form-note schedule-error" hidden>Choose both a day and time, or clear this row.</span>
                </div>
              </div>
            </div>
          </fieldset>

          <div class="field">
            <label for="suggestions">Any Other Suggestions?</label>
            <textarea id="suggestions" name="suggestions" placeholder="Share schedule suggestions, age group preference, topics, or questions."></textarea>
          </div>

          <fieldset class="choice-group">
            <legend>Agreements *</legend>
            <div class="choice-stack">
              <label><input type="checkbox" name="agree_code" value="Yes" required> I agree to follow the Yuva Club Code of Conduct.</label>
              <label><input type="checkbox" name="agree_recording" value="Yes" required> I understand that Yuva Club sessions may be recorded for educational purposes.</label>
              <label><input type="checkbox" name="agree_parent_permission" value="Yes" required> I have my parent/guardian's permission to participate.</label>
            </div>
          </fieldset>

          <p class="form-note identity-onboarding-notice">After registration, you can choose a YUVA Handle and Avatar for challenges and leaderboards.</p>

          <button class="button primary" type="submit">Submit Registration</button>
        </form>
      </div>
    </section>
    <section class="registration-premium" aria-labelledby="premium-title"><div class="horizon-container public-story-split"><div><p class="horizon-kicker">Continue growing when ready</p><h2 id="premium-title">Premium extends the leadership journey.</h2></div><div class="story-copy"><p>Premium can include AI Mentor, unlimited presentations, advanced coaching, certificates, a leadership portfolio, parent insights, and additional premium features.</p><p>The free experience remains a meaningful place to begin.</p></div></div></section>
  </main>
  <footer class="site-footer horizon-footer">
    <div class="horizon-container footer-grid">
      <div class="footer-brand"><a class="brand horizon-brand" href="index.html" aria-label="YUVA Club home"><img src="assets/logo-public.webp" alt="" width="54" height="54"><span><strong>YUVA</strong> Club</span></a><p>Building Tomorrow&rsquo;s Leaders, One Voice at a Time.</p><strong class="footer-motto">Discover. Communicate. Lead. Inspire.</strong></div>
      <div><h2>Explore</h2><a href="programs.html">Programs</a><a href="programs.html#how-it-works">How It Works</a><a href="resources.html">Resources</a><a href="about.html">About</a><a href="faq.html">FAQ</a></div>
      <div><h2>Join</h2><a href="registration.php">Register</a><a href="partners.html">Partner With YUVA</a><a href="portal-login.php">Student Portal</a><a href="parent-login.php">Parent Portal</a></div>
      <div><h2>Trust</h2><a href="privacy.html">Privacy Policy</a><a href="terms.html">Terms of Service</a><a href="safety.html">Child Safety</a><a href="safety.html">Accessibility</a><a href="contact.html">Contact</a></div>
    </div>
    <div class="horizon-container footer-bottom"><p>&copy; 2026 YUVA Club. All rights reserved.</p><p>YUVA Club &middot; Lead. Communicate. Think. Impact.</p></div>
  </footer>
  <script>
    const dobInput = document.getElementById('date_of_birth');
    const ageInput = document.getElementById('age');
    const groupInput = document.getElementById('program_group');

    function updateAge() {
      if (!dobInput.value) {
        ageInput.value = '';
        groupInput.value = '';
        return;
      }

      const today = new Date();
      const dob = new Date(dobInput.value + 'T00:00:00');
      let age = today.getFullYear() - dob.getFullYear();
      const monthDiff = today.getMonth() - dob.getMonth();

      if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
        age--;
      }

      ageInput.value = age >= 0 ? age : '';
      if (age >= 18 && age <= 21) {
        groupInput.value = 'College Yuva (Ages 18-21)';
      } else if (age >= 13 && age <= 17) {
        groupInput.value = 'School Yuva (Ages 13-17)';
      } else if (age >= 0) {
        groupInput.value = 'Not eligible: Yuva Club is for ages 13-21';
      } else {
        groupInput.value = '';
      }
    }

    dobInput.addEventListener('change', updateAge);
    updateAge();

    const registrationFlash = <?php echo json_encode($registrationFlashValues, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    Object.entries(registrationFlash).forEach(([name, value]) => {
      const fields = document.querySelectorAll(`[name="${CSS.escape(name)}"]`);
      fields.forEach((field) => {
        if (field.type === 'checkbox') {
          field.checked = Array.isArray(value) ? value.includes(field.value) : value === field.value;
        } else {
          field.value = value;
        }
      });
    });
    updateAge();

    const validationReason = <?php echo json_encode($validationReason, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const focusTargets = {
      'incomplete-schedule-pair': 'preferred_day_1',
      'missing-required-field': 'student_first_name',
      'invalid-or-missing-age': 'date_of_birth',
      'age-below-minimum': 'date_of_birth',
      'age-above-maximum': 'date_of_birth',
      'missing-agreement': 'agree_code',
      'weak-password': 'account_password',
      'password-mismatch': 'account_password_confirm',
    };
    const focusTarget = document.getElementById(focusTargets[validationReason] || '');
    if (focusTarget && validationReason !== '') {
      focusTarget.focus({ preventScroll: true });
      focusTarget.scrollIntoView({ block: 'center', behavior: 'smooth' });
    }

    document.querySelectorAll('[id^="preferred_day_"]').forEach((dayField) => {
      const index = dayField.id.replace('preferred_day_', '');
      const timeField = document.getElementById(`preferred_time_${index}`);
      if (!timeField) return;
      const validateSchedulePair = () => {
        const incomplete = (dayField.value !== '') !== (timeField.value !== '');
        const message = document.getElementById(`schedule_error_${index}`);
        dayField.setCustomValidity(incomplete ? 'Choose both a day and time, or clear this availability row.' : '');
        timeField.setCustomValidity(incomplete ? 'Choose both a day and time, or clear this availability row.' : '');
        if (message) message.hidden = !incomplete;
      };
      dayField.addEventListener('change', validateSchedulePair);
      timeField.addEventListener('change', validateSchedulePair);
      validateSchedulePair();
    });
  </script>
</body>
</html>
