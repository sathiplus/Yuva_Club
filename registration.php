<?php
$status = $_GET['status'] ?? '';
$studentId = $_GET['id'] ?? '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Register | Yuva Club</title>
  <meta name="description" content="Register for Yuva Club and select preferred class days and times.">
  <meta property="og:title" content="Register | Yuva Club">
  <meta property="og:description" content="Register for Yuva Club and select preferred class days and times.">
  <meta property="og:image" content="https://yuvaclub.karmabro.com/assets/logo.png">
  <meta property="og:url" content="https://yuvaclub.karmabro.com/registration.php">
  <meta property="og:type" content="website">
  <link rel="icon" href="assets/logo.png" type="image/png">
  <link rel="stylesheet" href="assets/site.css?v=20260614-large-photos">
</head>
<body>
  <header class="site-header">
    <a class="brand" href="index.html" aria-label="Yuva Club home">
      <img src="assets/logo.png" alt="Yuva Club logo" width="78" height="78">
      <span>Yuva Club</span>
    </a>
    <nav class="nav" aria-label="Main navigation">
      <a href="index.html">Home</a>
      <a href="programs.html">Programs</a>
      <a href="curriculum.html">Topics</a>
      <a href="resources.html">Resources</a>
      <a href="stories.html">Stories</a>
      <a href="app.html">App</a>
      <a href="safety.html">Safety</a>
      <a href="registration.php">Register</a>
      <a href="portal-login.php">Student Portal</a>
      <a href="parent-login.php">Parent</a>
      <a href="admin-login.php">Admin</a>
    </nav>
  </header>

  <main>
    <section class="band">
      <div class="form-shell">
        <div class="section-head">
          <p class="eyebrow">Yuva Club Registration</p>
          <h1>Sign In Form</h1>
          <p>Select up to three days and times that may work best for the student. We will use responses to choose the best class schedule.</p>
        </div>

        <?php if ($status === 'success'): ?>
          <div class="form-status success">Thank you. Your registration was submitted successfully<?php echo $studentId !== '' ? ' with Yuva Club ID ' . htmlspecialchars($studentId, ENT_QUOTES, 'UTF-8') : ''; ?>.</div>
        <?php elseif ($status === 'error'): ?>
          <div class="form-status error">Please complete the required fields, choose at least one day/time preference, and accept the agreements.</div>
        <?php endif; ?>

        <form class="form-card" action="submit-registration.php" method="post">
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
            <legend>Preferred Class Days and Times *</legend>
            <p class="form-note">Enter up to three preferences. At least one day and time is required.</p>
            <div class="preference-grid">
              <div class="preference-row">
                <div class="field">
                  <label for="preferred_day_1">First Choice Day *</label>
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
                  <label for="preferred_time_1">First Choice Time *</label>
                  <input id="preferred_time_1" name="preferred_time_1" type="time">
                </div>
              </div>

              <div class="preference-row">
                <div class="field">
                  <label for="preferred_day_2">Second Choice Day</label>
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
                  <label for="preferred_time_2">Second Choice Time</label>
                  <input id="preferred_time_2" name="preferred_time_2" type="time">
                </div>
              </div>

              <div class="preference-row">
                <div class="field">
                  <label for="preferred_day_3">Third Choice Day</label>
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
                  <label for="preferred_time_3">Third Choice Time</label>
                  <input id="preferred_time_3" name="preferred_time_3" type="time">
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

          <button class="button primary" type="submit">Submit Registration</button>
        </form>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <div>
      <strong>Yuva Club</strong>
      <p>A youth leadership development platform that empowers students through research, presentations, discussion, critical thinking, and peer learning.</p>
      <p>&copy; 2026 KarmaBro. All rights reserved.</p>
    </div>
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
  </script>
</body>
</html>
