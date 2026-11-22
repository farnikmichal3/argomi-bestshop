-- =============================================================================
-- ArgoMi BestShop · F8 · Admin Identity foundation
-- MySQL 8.0.16+ · InnoDB · utf8mb4
--
-- First, intentionally small Identity foundation: administrator accounts and
-- assignable role names. This is NOT the future ACL permission matrix.
-- No default account or password is created by this migration.
-- =============================================================================

CREATE TABLE admin_user (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email          VARCHAR(254) NOT NULL,
    password_hash  VARCHAR(255) NOT NULL,
    status         ENUM('active','disabled') NOT NULL DEFAULT 'active',
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login_at  DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admin_user_email (email),
    KEY idx_admin_user_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE admin_role (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code        VARCHAR(64) NOT NULL,
    name        VARCHAR(120) NOT NULL,
    is_system   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admin_role_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE admin_user_role (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    admin_user_id  BIGINT UNSIGNED NOT NULL,
    admin_role_id  BIGINT UNSIGNED NOT NULL,
    assigned_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admin_user_role (admin_user_id, admin_role_id),
    KEY idx_admin_user_role_role (admin_role_id),
    CONSTRAINT fk_admin_user_role_user FOREIGN KEY (admin_user_id)
        REFERENCES admin_user (id) ON DELETE CASCADE,
    CONSTRAINT fk_admin_user_role_role FOREIGN KEY (admin_role_id)
        REFERENCES admin_role (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
