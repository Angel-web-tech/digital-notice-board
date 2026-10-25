CREATE DATABASE IF NOT EXISTS digital_notice_board CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE digital_notice_board;

SET time_zone = '+05:30';

CREATE TABLE IF NOT EXISTS users (
	user_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	username VARCHAR(30) NOT NULL UNIQUE,
	full_name VARCHAR(60) NULL,
	email VARCHAR(254) NULL UNIQUE,
	password_hash VARCHAR(255) NOT NULL,
	role VARCHAR(20) NOT NULL DEFAULT 'admin',
	status ENUM('pending', 'active', 'rejected', 'disabled') NOT NULL DEFAULT 'active',
	must_change_password TINYINT(1) NOT NULL DEFAULT 0,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS password_resets (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	user_id INT UNSIGNED NOT NULL,
	token_hash CHAR(64) NOT NULL UNIQUE,
	expires_at DATETIME NOT NULL,
	used_at DATETIME NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	request_ip VARCHAR(45) NOT NULL,
	INDEX idx_password_resets_user_expiry (user_id, expires_at, used_at),
	CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notices (
	notice_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	title VARCHAR(150) NOT NULL,
	description VARCHAR(2000) NOT NULL,
	category VARCHAR(40) NOT NULL,
	department VARCHAR(40) NOT NULL,
	priority ENUM('normal', 'urgent') NOT NULL DEFAULT 'normal',
	status ENUM('draft', 'published') NOT NULL DEFAULT 'published',
	is_pinned TINYINT(1) NOT NULL DEFAULT 0,
	views INT UNSIGNED NOT NULL DEFAULT 0,
	attachment_path VARCHAR(255) NULL,
	attachment_original_name VARCHAR(255) NULL,
	publish_date DATETIME NOT NULL,
	expiry_date DATETIME NOT NULL,
	created_by INT UNSIGNED NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	CONSTRAINT fk_notices_created_by FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL,
	CONSTRAINT chk_notice_date_order CHECK (expiry_date > publish_date),
	INDEX idx_notices_category (category),
	INDEX idx_notices_department (department),
	INDEX idx_notices_publish_date (publish_date),
	INDEX idx_notices_expiry_date (expiry_date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS activity_log (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	user_id INT UNSIGNED NULL,
	username VARCHAR(30) NOT NULL,
	action VARCHAR(40) NOT NULL,
	notice_id INT UNSIGNED NULL,
	notice_title VARCHAR(150) NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	INDEX idx_activity_created (created_at),
	INDEX idx_activity_user_action (user_id, action),
	INDEX idx_activity_notice (notice_id),
	CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS login_attempts (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	username VARCHAR(30) NOT NULL,
	ip_address VARCHAR(45) NOT NULL,
	attempted_at DATETIME NOT NULL,
	was_successful TINYINT(1) NOT NULL DEFAULT 0,
	INDEX idx_login_user_time (username, attempted_at),
	INDEX idx_login_ip_time (ip_address, attempted_at)
) ENGINE=InnoDB;

INSERT INTO notices (title, description, category, department, priority, is_pinned, publish_date, expiry_date) VALUES
('Semester registration deadline', 'Complete online registration for the upcoming semester through the student portal. Contact the academic office if you need help.', 'Academics', 'All Departments', 'urgent', 1, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_ADD(NOW(), INTERVAL 5 DAY)),
('Mid-semester examination timetable', 'The mid-semester examination timetable is available from the examination cell. Please check your department notice board for room assignments.', 'Examinations', 'CSE', 'normal', 0, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL 20 DAY)),
('Annual cultural festival registrations', 'Student registrations are open for music, theatre, dance and art events. Register with the student activities office.', 'Events', 'All Departments', 'normal', 0, DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_ADD(NOW(), INTERVAL 12 DAY)),
('Summer software internship applications', 'Apply with your resume and a short statement of interest. Shortlisted students will be contacted by the placement cell.', 'Internships', 'CSE', 'normal', 0, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL 18 DAY)),
('Campus placement aptitude session', 'A compulsory aptitude preparation session for final-year students will be held in the central seminar hall.', 'Placements', 'All Departments', 'urgent', 1, DATE_SUB(NOW(), INTERVAL 1 HOUR), DATE_ADD(NOW(), INTERVAL 3 DAY)),
('Introduction to embedded systems workshop', 'Join a practical workshop covering microcontrollers, sensors and a guided hardware lab. Seats are limited.', 'Workshops', 'ECE', 'normal', 0, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_ADD(NOW(), INTERVAL 10 DAY)),
('Library hours during the break', 'The central library will follow revised weekday hours during the break. Borrowed books can be returned at the ground-floor desk.', 'General Announcements', 'All Departments', 'normal', 0, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_ADD(NOW(), INTERVAL 30 DAY)),
('Concrete testing laboratory schedule', 'Civil engineering laboratory groups should review the updated concrete testing schedule and bring their lab records.', 'Academics', 'Civil', 'normal', 0, DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_ADD(NOW(), INTERVAL 8 DAY)),
('Electrical safety examination notice', 'The electrical safety assessment will take place in the power systems laboratory. Carry your campus identity card.', 'Examinations', 'EEE', 'urgent', 0, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_ADD(NOW(), INTERVAL 4 DAY)),
('Mechanical design showcase', 'Final-year mechanical engineering students will present their capstone prototypes in the design studio.', 'Events', 'Mechanical', 'normal', 0, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_ADD(NOW(), INTERVAL 9 DAY)),
('Robotics workshop for beginners', 'A beginner-friendly robotics session introduces chassis design, motor control and basic sensor integration.', 'Workshops', 'CSE', 'normal', 0, DATE_ADD(NOW(), INTERVAL 4 DAY), DATE_ADD(NOW(), INTERVAL 12 DAY)),
('Previous year scholarship application reminder', 'The application window for this scholarship has closed. Students can contact the student support office for future opportunities.', 'General Announcements', 'All Departments', 'normal', 0, DATE_SUB(NOW(), INTERVAL 30 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
('Industry internship orientation', 'An orientation for selected internship students will be conducted by the placement cell before the summer programme begins.', 'Internships', 'ECE', 'normal', 0, DATE_ADD(NOW(), INTERVAL 3 DAY), DATE_ADD(NOW(), INTERVAL 14 DAY)),
('Graduate trainee placement drive', 'Eligible students should submit their updated resume and academic details before attending the company briefing.', 'Placements', 'EEE', 'urgent', 0, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL 6 DAY));
