-- Aquaculture Scientific author portal schema (MySQL/MariaDB, utf8mb4)
-- Import this file into the database you created in Hostinger hPanel/phpMyAdmin.

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(32) NOT NULL,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  given_name VARCHAR(100) NOT NULL,
  family_name VARCHAR(100) NOT NULL,
  institution VARCHAR(255) NULL,
  orcid VARCHAR(80) NULL,
  role ENUM('author','editor','admin') NOT NULL DEFAULT 'author',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_username (username),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS submissions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  public_id VARCHAR(32) NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(500) NOT NULL,
  abstract_text MEDIUMTEXT NOT NULL,
  cover_letter MEDIUMTEXT NULL,
  article_type VARCHAR(120) NOT NULL,
  subject_area VARCHAR(120) NOT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'Received',
  editorial_note TEXT NULL,
  mail_status ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
  mail_error VARCHAR(500) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_submissions_public_id (public_id),
  KEY ix_submissions_user_created (user_id, created_at),
  KEY ix_submissions_status (status),
  CONSTRAINT fk_submissions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS submission_files (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  submission_id BIGINT UNSIGNED NOT NULL,
  file_kind ENUM('manuscript','supplementary','revision') NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  relative_path VARCHAR(500) NOT NULL,
  mime_type VARCHAR(190) NULL,
  size_bytes BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_submission_files_submission (submission_id),
  CONSTRAINT fk_submission_files_submission FOREIGN KEY (submission_id) REFERENCES submissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS submission_history (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  submission_id BIGINT UNSIGNED NOT NULL,
  old_status VARCHAR(40) NULL,
  new_status VARCHAR(40) NOT NULL,
  note TEXT NULL,
  changed_by BIGINT UNSIGNED NULL,
  changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_submission_history_submission (submission_id, changed_at),
  CONSTRAINT fk_submission_history_submission FOREIGN KEY (submission_id) REFERENCES submissions(id) ON DELETE CASCADE,
  CONSTRAINT fk_submission_history_user FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
