-- Add optional notice features without removing or rewriting existing rows.
ALTER TABLE notices
    ADD COLUMN status ENUM('draft', 'published') NOT NULL DEFAULT 'published',
    ADD COLUMN views INT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN attachment_path VARCHAR(255) NULL,
    ADD COLUMN attachment_original_name VARCHAR(255) NULL;

CREATE TABLE activity_log (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_attempts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(30) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempted_at DATETIME NOT NULL,
    was_successful TINYINT(1) NOT NULL DEFAULT 0,
    INDEX idx_login_user_time (username, attempted_at),
    INDEX idx_login_ip_time (ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;