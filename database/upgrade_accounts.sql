-- Select digital_notice_board as the default schema in Workbench before running.
-- Existing accounts keep their data and become active by the column default.
ALTER TABLE users
    ADD COLUMN full_name VARCHAR(60) NULL,
    ADD COLUMN email VARCHAR(254) NULL,
    ADD UNIQUE INDEX uq_users_email (email),
    ADD COLUMN status ENUM('pending', 'active', 'rejected', 'disabled') NOT NULL DEFAULT 'active',
    ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0;

CREATE TABLE password_resets (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    request_ip VARCHAR(45) NOT NULL,
    INDEX idx_password_resets_user_expiry (user_id, expires_at, used_at),
    CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;