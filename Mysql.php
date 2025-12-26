CREATE DATABASE IF NOT EXISTS university_groups;
USE university_groups;

DROP TABLE IF EXISTS members;
DROP TABLE IF EXISTS student_groups;

CREATE TABLE student_groups (
  id INT AUTO_INCREMENT PRIMARY KEY,
  group_name VARCHAR(100) NOT NULL
);

CREATE TABLE members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  group_id INT NOT NULL,
  member_name VARCHAR(100) NOT NULL,
  member_role VARCHAR(100) NOT NULL,
  CONSTRAINT fk_group
    FOREIGN KEY (group_id) REFERENCES student_groups(id)
    ON DELETE CASCADE
);
