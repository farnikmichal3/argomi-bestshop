-- =============================================================================
-- ArgoMi BestShop · F3-34 · Transactional Outbox
-- MySQL 8.0.16+ · InnoDB · utf8mb4
--
-- Zpráva se zapisuje ve stejné databázové transakci jako obchodní změna.
-- Worker krátce zamkne dostupné zprávy, nastaví lease a COMMITne ještě
-- před jakýmkoliv externím voláním. Doručení je at-least-once.
-- =============================================================================

CREATE TABLE outbox_message (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    message_id    CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    event_name    VARCHAR(190) NOT NULL,
    payload       JSON NOT NULL,
    status        ENUM('pending','processing','delivered','dead') NOT NULL DEFAULT 'pending',
    attempts      INT UNSIGNED NOT NULL DEFAULT 0,
    available_at  DATETIME(6) NOT NULL,
    locked_until  DATETIME(6) NULL,
    lock_token    BINARY(16) NULL,
    created_at    DATETIME(6) NOT NULL,
    delivered_at  DATETIME(6) NULL,
    dead_at       DATETIME(6) NULL,
    last_error    VARCHAR(1000) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_outbox_message_id (message_id),
    UNIQUE KEY uq_outbox_lock_token (lock_token),
    KEY idx_outbox_available (status, available_at, id),
    KEY idx_outbox_expired_lease (status, locked_until, id),
    CONSTRAINT ck_outbox_payload_object CHECK (JSON_TYPE(payload) = 'OBJECT'),
    CONSTRAINT ck_outbox_processing_attempt CHECK (status <> 'processing' OR attempts > 0),
    CONSTRAINT ck_outbox_lock_state CHECK (
        (status = 'processing' AND locked_until IS NOT NULL AND lock_token IS NOT NULL)
        OR
        (status <> 'processing' AND locked_until IS NULL AND lock_token IS NULL)
    ),
    CONSTRAINT ck_outbox_terminal_state CHECK (
        (status = 'delivered' AND delivered_at IS NOT NULL AND dead_at IS NULL)
        OR
        (status = 'dead' AND delivered_at IS NULL AND dead_at IS NOT NULL)
        OR
        (status IN ('pending','processing') AND delivered_at IS NULL AND dead_at IS NULL)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
