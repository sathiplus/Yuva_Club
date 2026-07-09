-- Yuva Club production schema for Azure SQL Database.
-- Run on a blank Azure SQL database.

CREATE TABLE programs (
  id BIGINT IDENTITY(1,1) PRIMARY KEY,
  code NVARCHAR(40) NOT NULL UNIQUE,
  name NVARCHAR(120) NOT NULL,
  min_age TINYINT NULL,
  max_age TINYINT NULL,
  description NVARCHAR(MAX) NULL,
  is_active BIT NOT NULL CONSTRAINT df_programs_active DEFAULT 1,
  created_at DATETIME2 NOT NULL CONSTRAINT df_programs_created DEFAULT SYSUTCDATETIME(),
  updated_at DATETIME2 NOT NULL CONSTRAINT df_programs_updated DEFAULT SYSUTCDATETIME()
);

CREATE TABLE levels (
  id BIGINT IDENTITY(1,1) PRIMARY KEY,
  code NVARCHAR(40) NOT NULL UNIQUE,
  name NVARCHAR(80) NOT NULL,
  display_order INT NOT NULL CONSTRAINT df_levels_order DEFAULT 0,
  certificate_name NVARCHAR(160) NOT NULL,
  meaning NVARCHAR(255) NULL,
  requirements NVARCHAR(MAX) NULL,
  is_active BIT NOT NULL CONSTRAINT df_levels_active DEFAULT 1,
  created_at DATETIME2 NOT NULL CONSTRAINT df_levels_created DEFAULT SYSUTCDATETIME(),
  updated_at DATETIME2 NOT NULL CONSTRAINT df_levels_updated DEFAULT SYSUTCDATETIME()
);

CREATE TABLE users (
  id BIGINT IDENTITY(1,1) PRIMARY KEY,
  email NVARCHAR(190) NOT NULL UNIQUE,
  password_hash NVARCHAR(255) NULL,
  role NVARCHAR(20) NOT NULL CHECK (role IN ('student','parent','admin')),
  display_name NVARCHAR(160) NOT NULL,
  email_verified_at DATETIME2 NULL,
  last_login_at DATETIME2 NULL,
  two_factor_enabled BIT NOT NULL CONSTRAINT df_users_2fa DEFAULT 0,
  status NVARCHAR(20) NOT NULL CONSTRAINT df_users_status DEFAULT 'pending' CHECK (status IN ('pending','active','suspended','disabled')),
  password_reset_token_hash NVARCHAR(255) NULL,
  password_reset_expires_at DATETIME2 NULL,
  email_verification_token_hash NVARCHAR(255) NULL,
  email_verification_expires_at DATETIME2 NULL,
  created_at DATETIME2 NOT NULL CONSTRAINT df_users_created DEFAULT SYSUTCDATETIME(),
  updated_at DATETIME2 NOT NULL CONSTRAINT df_users_updated DEFAULT SYSUTCDATETIME()
);
CREATE INDEX idx_users_role_status ON users(role, status);

CREATE TABLE students (
  id BIGINT IDENTITY(1,1) PRIMARY KEY,
  user_id BIGINT NULL UNIQUE REFERENCES users(id),
  program_id BIGINT NOT NULL REFERENCES programs(id),
  current_level_id BIGINT NULL REFERENCES levels(id),
  yuva_id NVARCHAR(40) NOT NULL UNIQUE,
  first_name NVARCHAR(120) NOT NULL,
  last_name NVARCHAR(120) NOT NULL,
  preferred_name NVARCHAR(120) NULL,
  date_of_birth DATE NULL,
  grade NVARCHAR(60) NULL,
  school NVARCHAR(180) NULL,
  city_state NVARCHAR(180) NULL,
  phone NVARCHAR(40) NULL,
  whatsapp_contact NVARCHAR(120) NULL,
  approval_status NVARCHAR(20) NOT NULL CONSTRAINT df_students_approval DEFAULT 'pending' CHECK (approval_status IN ('pending','approved','rejected','waitlisted')),
  approved_at DATETIME2 NULL,
  created_at DATETIME2 NOT NULL CONSTRAINT df_students_created DEFAULT SYSUTCDATETIME(),
  updated_at DATETIME2 NOT NULL CONSTRAINT df_students_updated DEFAULT SYSUTCDATETIME()
);
CREATE INDEX idx_students_name ON students(last_name, first_name);
CREATE INDEX idx_students_program_status ON students(program_id, approval_status);

CREATE TABLE parents (
  id BIGINT IDENTITY(1,1) PRIMARY KEY,
  user_id BIGINT NULL UNIQUE REFERENCES users(id),
  first_name NVARCHAR(120) NOT NULL,
  last_name NVARCHAR(120) NULL,
  relationship NVARCHAR(80) NULL,
  phone NVARCHAR(40) NULL,
  created_at DATETIME2 NOT NULL CONSTRAINT df_parents_created DEFAULT SYSUTCDATETIME(),
  updated_at DATETIME2 NOT NULL CONSTRAINT df_parents_updated DEFAULT SYSUTCDATETIME()
);

CREATE TABLE student_parents (
  student_id BIGINT NOT NULL REFERENCES students(id) ON DELETE CASCADE,
  parent_id BIGINT NOT NULL REFERENCES parents(id) ON DELETE CASCADE,
  is_primary BIT NOT NULL CONSTRAINT df_student_parents_primary DEFAULT 0,
  consent_status NVARCHAR(20) NOT NULL CONSTRAINT df_student_parents_consent DEFAULT 'pending' CHECK (consent_status IN ('pending','granted','revoked')),
  consent_at DATETIME2 NULL,
  created_at DATETIME2 NOT NULL CONSTRAINT df_student_parents_created DEFAULT SYSUTCDATETIME(),
  PRIMARY KEY (student_id, parent_id)
);

CREATE TABLE registrations (
  id BIGINT IDENTITY(1,1) PRIMARY KEY,
  student_id BIGINT NULL REFERENCES students(id),
  submitted_at DATETIME2 NOT NULL CONSTRAINT df_registrations_submitted DEFAULT SYSUTCDATETIME(),
  status NVARCHAR(20) NOT NULL CONSTRAINT df_registrations_status DEFAULT 'new' CHECK (status IN ('new','reviewing','approved','rejected','waitlisted')),
  student_first_name NVARCHAR(120) NOT NULL,
  student_last_name NVARCHAR(120) NOT NULL,
  preferred_name NVARCHAR(120) NULL,
  date_of_birth DATE NULL,
  age TINYINT NULL,
  program_id BIGINT NULL REFERENCES programs(id),
  grade NVARCHAR(60) NULL,
  school NVARCHAR(180) NULL,
  city_state NVARCHAR(180) NULL,
  parent_name NVARCHAR(180) NOT NULL,
  relationship NVARCHAR(80) NULL,
  parent_email NVARCHAR(190) NOT NULL,
  parent_phone NVARCHAR(40) NULL,
  student_email NVARCHAR(190) NULL,
  student_phone NVARCHAR(40) NULL,
  whatsapp_contact NVARCHAR(120) NULL,
  interests NVARCHAR(MAX) NULL,
  why_join NVARCHAR(MAX) NULL,
  presentation_experience NVARCHAR(MAX) NULL,
  presentation_topics NVARCHAR(MAX) NULL,
  preferred_schedule NVARCHAR(MAX) NULL,
  suggestions NVARCHAR(MAX) NULL,
  code_of_conduct_agreed BIT NOT NULL CONSTRAINT df_registrations_code DEFAULT 0,
  recording_agreed BIT NOT NULL CONSTRAINT df_registrations_recording DEFAULT 0,
  parent_permission_granted BIT NOT NULL CONSTRAINT df_registrations_permission DEFAULT 0,
  ip_address NVARCHAR(64) NULL,
  reviewed_by BIGINT NULL REFERENCES users(id),
  reviewed_at DATETIME2 NULL,
  created_at DATETIME2 NOT NULL CONSTRAINT df_registrations_created DEFAULT SYSUTCDATETIME(),
  updated_at DATETIME2 NOT NULL CONSTRAINT df_registrations_updated DEFAULT SYSUTCDATETIME()
);
CREATE INDEX idx_registrations_status ON registrations(status);
CREATE INDEX idx_registrations_parent_email ON registrations(parent_email);

CREATE TABLE sessions (
  id BIGINT IDENTITY(1,1) PRIMARY KEY,
  program_id BIGINT NULL REFERENCES programs(id),
  title NVARCHAR(180) NOT NULL,
  description NVARCHAR(MAX) NULL,
  session_type NVARCHAR(30) NOT NULL CONSTRAINT df_sessions_type DEFAULT 'practice',
  starts_at DATETIME2 NOT NULL,
  ends_at DATETIME2 NULL,
  status NVARCHAR(20) NOT NULL CONSTRAINT df_sessions_status DEFAULT 'draft',
  zoom_url NVARCHAR(600) NULL,
  zoom_meeting_id NVARCHAR(80) NULL,
  zoom_password NVARCHAR(120) NULL,
  scheduler_url NVARCHAR(600) NULL,
  created_by BIGINT NULL REFERENCES users(id),
  created_at DATETIME2 NOT NULL CONSTRAINT df_sessions_created DEFAULT SYSUTCDATETIME(),
  updated_at DATETIME2 NOT NULL CONSTRAINT df_sessions_updated DEFAULT SYSUTCDATETIME()
);

CREATE TABLE topic_categories (
  id BIGINT IDENTITY(1,1) PRIMARY KEY,
  name NVARCHAR(160) NOT NULL UNIQUE,
  display_order INT NOT NULL CONSTRAINT df_topic_categories_order DEFAULT 0,
  is_active BIT NOT NULL CONSTRAINT df_topic_categories_active DEFAULT 1,
  created_at DATETIME2 NOT NULL CONSTRAINT df_topic_categories_created DEFAULT SYSUTCDATETIME()
);

CREATE TABLE topics (
  id BIGINT IDENTITY(1,1) PRIMARY KEY,
  category_id BIGINT NOT NULL REFERENCES topic_categories(id) ON DELETE CASCADE,
  title NVARCHAR(220) NOT NULL,
  slug NVARCHAR(220) NULL UNIQUE,
  description NVARCHAR(MAX) NULL,
  page_url NVARCHAR(400) NULL,
  image_url NVARCHAR(400) NULL,
  is_active BIT NOT NULL CONSTRAINT df_topics_active DEFAULT 1,
  created_at DATETIME2 NOT NULL CONSTRAINT df_topics_created DEFAULT SYSUTCDATETIME(),
  updated_at DATETIME2 NOT NULL CONSTRAINT df_topics_updated DEFAULT SYSUTCDATETIME(),
  CONSTRAINT uq_topics_category_title UNIQUE (category_id, title)
);

CREATE TABLE student_topic_selections (
  id BIGINT IDENTITY(1,1) PRIMARY KEY,
  student_id BIGINT NOT NULL REFERENCES students(id) ON DELETE CASCADE,
  topic_id BIGINT NOT NULL REFERENCES topics(id),
  presentation_date DATE NULL,
  presentation_time TIME NULL,
  status NVARCHAR(30) NOT NULL CONSTRAINT df_topic_selections_status DEFAULT 'pending',
  reviewed_by BIGINT NULL REFERENCES users(id),
  reviewed_at DATETIME2 NULL,
  created_at DATETIME2 NOT NULL CONSTRAINT df_topic_selections_created DEFAULT SYSUTCDATETIME(),
  updated_at DATETIME2 NOT NULL CONSTRAINT df_topic_selections_updated DEFAULT SYSUTCDATETIME(),
  CONSTRAINT uq_topic_selection UNIQUE (student_id, topic_id)
);

CREATE TABLE presentation_submissions (
  id BIGINT IDENTITY(1,1) PRIMARY KEY,
  student_id BIGINT NOT NULL REFERENCES students(id) ON DELETE CASCADE,
  topic_selection_id BIGINT NULL REFERENCES student_topic_selections(id),
  research_notes NVARCHAR(MAX) NOT NULL,
  sources_used NVARCHAR(MAX) NOT NULL,
  presentation_outline NVARCHAR(MAX) NOT NULL,
  prepared_questions NVARCHAR(MAX) NOT NULL,
  status NVARCHAR(30) NOT NULL CONSTRAINT df_submissions_status DEFAULT 'submitted',
  reviewed_by BIGINT NULL REFERENCES users(id),
  reviewed_at DATETIME2 NULL,
  created_at DATETIME2 NOT NULL CONSTRAINT df_submissions_created DEFAULT SYSUTCDATETIME(),
  updated_at DATETIME2 NOT NULL CONSTRAINT df_submissions_updated DEFAULT SYSUTCDATETIME()
);

CREATE TABLE files (
  id BIGINT IDENTITY(1,1) PRIMARY KEY,
  owner_user_id BIGINT NULL REFERENCES users(id),
  student_id BIGINT NULL REFERENCES students(id),
  submission_id BIGINT NULL REFERENCES presentation_submissions(id),
  purpose NVARCHAR(30) NOT NULL CONSTRAINT df_files_purpose DEFAULT 'other',
  original_name NVARCHAR(255) NOT NULL,
  blob_container NVARCHAR(120) NOT NULL,
  blob_name NVARCHAR(500) NOT NULL,
  public_url NVARCHAR(800) NULL,
  mime_type NVARCHAR(160) NULL,
  size_bytes BIGINT NULL,
  sha256_hash CHAR(64) NULL,
  scan_status NVARCHAR(20) NOT NULL CONSTRAINT df_files_scan DEFAULT 'pending',
  created_at DATETIME2 NOT NULL CONSTRAINT df_files_created DEFAULT SYSUTCDATETIME(),
  CONSTRAINT uq_files_blob UNIQUE (blob_container, blob_name)
);

CREATE TABLE attendance (
  id BIGINT IDENTITY(1,1) PRIMARY KEY,
  session_id BIGINT NOT NULL REFERENCES sessions(id) ON DELETE CASCADE,
  student_id BIGINT NOT NULL REFERENCES students(id) ON DELETE CASCADE,
  status NVARCHAR(20) NOT NULL CONSTRAINT df_attendance_status DEFAULT 'registered',
  duration_minutes INT NULL,
  notes NVARCHAR(MAX) NULL,
  marked_by BIGINT NULL REFERENCES users(id),
  marked_at DATETIME2 NULL,
  created_at DATETIME2 NOT NULL CONSTRAINT df_attendance_created DEFAULT SYSUTCDATETIME(),
  updated_at DATETIME2 NOT NULL CONSTRAINT df_attendance_updated DEFAULT SYSUTCDATETIME(),
  CONSTRAINT uq_attendance_session_student UNIQUE (session_id, student_id)
);

CREATE TABLE evaluations (
  id BIGINT IDENTITY(1,1) PRIMARY KEY,
  student_id BIGINT NOT NULL REFERENCES students(id) ON DELETE CASCADE,
  submission_id BIGINT NULL REFERENCES presentation_submissions(id),
  session_id BIGINT NULL REFERENCES sessions(id),
  evaluator_user_id BIGINT NULL REFERENCES users(id),
  confidence TINYINT NULL,
  voice_clarity TINYINT NULL,
  research_quality TINYINT NULL,
  organization TINYINT NULL,
  creativity TINYINT NULL,
  visual_presentation TINYINT NULL,
  audience_engagement TINYINT NULL,
  question_handling TINYINT NULL,
  leadership TINYINT NULL,
  time_management TINYINT NULL,
  total_score SMALLINT NOT NULL CONSTRAINT df_evaluations_total DEFAULT 0,
  feedback NVARCHAR(MAX) NULL,
  challenge_stage NVARCHAR(80) NOT NULL CONSTRAINT df_evaluations_stage DEFAULT 'Practice Session',
  finalist_status NVARCHAR(30) NOT NULL CONSTRAINT df_evaluations_finalist DEFAULT 'Not Qualified',
  award_status NVARCHAR(120) NULL,
  created_at DATETIME2 NOT NULL CONSTRAINT df_evaluations_created DEFAULT SYSUTCDATETIME(),
  updated_at DATETIME2 NOT NULL CONSTRAINT df_evaluations_updated DEFAULT SYSUTCDATETIME()
);

CREATE TABLE badges (
  id BIGINT IDENTITY(1,1) PRIMARY KEY,
  code NVARCHAR(80) NOT NULL UNIQUE,
  name NVARCHAR(120) NOT NULL,
  description NVARCHAR(MAX) NULL,
  icon_url NVARCHAR(400) NULL,
  is_active BIT NOT NULL CONSTRAINT df_badges_active DEFAULT 1,
  created_at DATETIME2 NOT NULL CONSTRAINT df_badges_created DEFAULT SYSUTCDATETIME()
);

CREATE TABLE student_badges (
  student_id BIGINT NOT NULL REFERENCES students(id) ON DELETE CASCADE,
  badge_id BIGINT NOT NULL REFERENCES badges(id) ON DELETE CASCADE,
  awarded_by BIGINT NULL REFERENCES users(id),
  awarded_at DATETIME2 NOT NULL CONSTRAINT df_student_badges_awarded DEFAULT SYSUTCDATETIME(),
  notes NVARCHAR(MAX) NULL,
  PRIMARY KEY (student_id, badge_id)
);

CREATE TABLE student_points (
  id BIGINT IDENTITY(1,1) PRIMARY KEY,
  student_id BIGINT NOT NULL REFERENCES students(id) ON DELETE CASCADE,
  points INT NOT NULL,
  tokens INT NOT NULL CONSTRAINT df_student_points_tokens DEFAULT 0,
  reason NVARCHAR(180) NOT NULL,
  source_type NVARCHAR(80) NULL,
  source_id BIGINT NULL,
  awarded_by BIGINT NULL REFERENCES users(id),
  created_at DATETIME2 NOT NULL CONSTRAINT df_student_points_created DEFAULT SYSUTCDATETIME()
);

CREATE TABLE certificates (
  id BIGINT IDENTITY(1,1) PRIMARY KEY,
  student_id BIGINT NOT NULL REFERENCES students(id) ON DELETE CASCADE,
  level_id BIGINT NULL REFERENCES levels(id),
  certificate_number NVARCHAR(80) NOT NULL UNIQUE,
  title NVARCHAR(180) NOT NULL,
  status NVARCHAR(20) NOT NULL CONSTRAINT df_certificates_status DEFAULT 'draft',
  issued_by BIGINT NULL REFERENCES users(id),
  issued_at DATETIME2 NULL,
  file_id BIGINT NULL REFERENCES files(id),
  verification_code NVARCHAR(120) NOT NULL UNIQUE,
  created_at DATETIME2 NOT NULL CONSTRAINT df_certificates_created DEFAULT SYSUTCDATETIME(),
  updated_at DATETIME2 NOT NULL CONSTRAINT df_certificates_updated DEFAULT SYSUTCDATETIME()
);

CREATE TABLE safety_reports (
  id BIGINT IDENTITY(1,1) PRIMARY KEY,
  report_number NVARCHAR(80) NOT NULL UNIQUE,
  student_id BIGINT NULL REFERENCES students(id),
  reported_by_user_id BIGINT NULL REFERENCES users(id),
  program_id BIGINT NULL REFERENCES programs(id),
  report_type NVARCHAR(120) NOT NULL,
  message NVARCHAR(MAX) NOT NULL,
  status NVARCHAR(20) NOT NULL CONSTRAINT df_safety_reports_status DEFAULT 'open',
  assigned_to BIGINT NULL REFERENCES users(id),
  resolution_notes NVARCHAR(MAX) NULL,
  ip_address NVARCHAR(64) NULL,
  submitted_at DATETIME2 NOT NULL CONSTRAINT df_safety_reports_submitted DEFAULT SYSUTCDATETIME(),
  resolved_at DATETIME2 NULL
);

CREATE TABLE activity_logs (
  id BIGINT IDENTITY(1,1) PRIMARY KEY,
  actor_user_id BIGINT NULL REFERENCES users(id),
  actor_role NVARCHAR(40) NULL,
  action NVARCHAR(120) NOT NULL,
  entity_type NVARCHAR(80) NULL,
  entity_id BIGINT NULL,
  metadata NVARCHAR(MAX) NULL CHECK (metadata IS NULL OR ISJSON(metadata) = 1),
  ip_address NVARCHAR(64) NULL,
  user_agent NVARCHAR(500) NULL,
  created_at DATETIME2 NOT NULL CONSTRAINT df_activity_logs_created DEFAULT SYSUTCDATETIME()
);

CREATE TABLE email_notifications (
  id BIGINT IDENTITY(1,1) PRIMARY KEY,
  recipient_user_id BIGINT NULL REFERENCES users(id),
  recipient_email NVARCHAR(190) NOT NULL,
  template_key NVARCHAR(120) NOT NULL,
  subject NVARCHAR(255) NOT NULL,
  status NVARCHAR(20) NOT NULL CONSTRAINT df_email_notifications_status DEFAULT 'queued',
  provider_message_id NVARCHAR(190) NULL,
  error_message NVARCHAR(MAX) NULL,
  metadata NVARCHAR(MAX) NULL CHECK (metadata IS NULL OR ISJSON(metadata) = 1),
  queued_at DATETIME2 NOT NULL CONSTRAINT df_email_notifications_queued DEFAULT SYSUTCDATETIME(),
  sent_at DATETIME2 NULL
);

INSERT INTO programs (code, name, min_age, max_age, description)
VALUES ('school_yuva', 'School Yuva', 13, 17, 'Yuva Club program for students ages 13-17.'),
       ('college_yuva', 'College Yuva', 18, 21, 'Yuva Club program for students ages 18-21.');

INSERT INTO levels (code, name, display_order, certificate_name, meaning, requirements)
VALUES ('explorer', 'Explorer', 1, 'Yuva Explorer Certificate', 'Learn and participate', 'Complete onboarding, attend sessions, join discussions, and select topics.'),
       ('speaker', 'Speaker', 2, 'Yuva Speaker Certificate', 'Research and present', 'Research topics, upload notes or slides, present, and answer questions.'),
       ('leader', 'Leader', 3, 'Yuva Leader Certificate', 'Lead and organize', 'Lead discussions, support sessions, participate consistently, and receive approval.'),
       ('mentor', 'Mentor', 4, 'Yuva Mentor Certificate', 'Coach and represent', 'Coach newer members, provide constructive feedback, support events, and serve the community.');

INSERT INTO badges (code, name, description)
VALUES ('first_presentation', 'First Presentation', 'Awarded after the first completed presentation.'),
       ('five_presentations', 'Five Presentations', 'Awarded after five completed presentations.'),
       ('master_presenter', 'Master Presenter', 'Awarded after ten completed presentations.'),
       ('leadership_hours', 'Leadership Hours', 'Awarded for meaningful service and leadership hours.'),
       ('consistent_attendance', 'Consistent Attendance', 'Awarded for consistent session attendance.'),
       ('feedback_reviewed', 'Feedback Reviewed', 'Awarded when mentor or teacher feedback has been reviewed.'),
       ('strong_presentation_score', 'Strong Presentation Score', 'Awarded for high rubric performance.'),
       ('challenge_finalist', 'Challenge Finalist', 'Awarded for finalist status in a challenge.'),
       ('challenge_champion', 'Challenge Champion', 'Awarded for champion status in a challenge.');


