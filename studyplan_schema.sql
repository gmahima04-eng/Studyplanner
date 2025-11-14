-- StudyPlan DB schema and sample data
CREATE DATABASE IF NOT EXISTS studyplan_db;
USE studyplan_db;

DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS subjects;
DROP TABLE IF EXISTS tasks;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL -- hashed password
);

CREATE TABLE subjects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(150) NOT NULL,
  color VARCHAR(20) DEFAULT '#60a5fa',
  planned_hours INT DEFAULT 0,
  completed_percent INT DEFAULT 0,
  next_session DATETIME DEFAULT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE tasks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  subject_id INT DEFAULT NULL,
  title VARCHAR(255) NOT NULL,
  priority ENUM('low','medium','high') DEFAULT 'medium',
  due_date DATE DEFAULT NULL,
  done TINYINT(1) DEFAULT 0,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL
);

-- sample user (password is 'password' hashed using PHP password_hash)
INSERT INTO users (name, email, password) VALUES
('Demo Student', 'student@example.com', '$2y$10$e0NRd6G/8BDQ9IYqY9kP1uQX5Wq4C1o6r1B9cQ7g/1b1sN1z7eQeK');

-- sample subjects
INSERT INTO subjects (user_id, title, color, planned_hours, completed_percent, next_session) VALUES
(1, 'Mathematics', '#7dd3fc', 20, 60, DATE_ADD(NOW(), INTERVAL 0 HOUR)),
(1, 'Physics', '#bbf7d0', 15, 53, DATE_ADD(NOW(), INTERVAL 24 HOUR)),
(1, 'Chemistry', '#fde68a', 18, 83, DATE_ADD(NOW(), INTERVAL 1 HOUR)),
(1, 'Computer Science', '#fbcfe8', 25, 40, DATE_ADD(NOW(), INTERVAL 48 HOUR));

-- sample tasks
INSERT INTO tasks (user_id, subject_id, title, priority, due_date, done) VALUES
(1, 1, 'Complete Calculus Assignment', 'high', DATE_ADD(CURDATE(), INTERVAL 2 DAY), 0),
(1, 2, 'Read Chapter 5: Thermodynamics', 'medium', DATE_ADD(CURDATE(), INTERVAL 4 DAY), 0),
(1, 3, 'Lab Report Submission', 'high', DATE_ADD(CURDATE(), INTERVAL 1 DAY), 0);
