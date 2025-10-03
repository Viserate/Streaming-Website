-- Users
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(64) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','user') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Videos
CREATE TABLE IF NOT EXISTS videos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  filename VARCHAR(255) NOT NULL,
  tags TEXT,
  status ENUM('draft','published') NOT NULL DEFAULT 'draft',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Analytics
CREATE TABLE IF NOT EXISTS analytics_events (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  session_id VARCHAR(64) NOT NULL,
  user_id INT NULL,
  event_type ENUM('page_view','video_watch','time_spent') NOT NULL,
  video_id INT NULL,
  duration_seconds INT NULL,
  user_agent VARCHAR(255) NULL,
  ip_addr VARCHAR(64) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (created_at),
  INDEX (event_type),
  INDEX (video_id),
  INDEX (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create default admin (update password after first login)
-- Use tools/make_password.php to generate a password_hash if needed.
INSERT INTO users (username, password_hash, role)
VALUES ('admin', '$2y$10$S8yfgJ6l2/5bRvS8JUsM3u3eI9oQZkZ7oQuJ5QY6XwqV9mL7lF0hG', 'admin') -- password: admin123
ON DUPLICATE KEY UPDATE username=username;
