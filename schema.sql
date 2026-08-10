-- Faye WorkNest Database Schema
CREATE DATABASE IF NOT EXISTS faye_worknest CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE faye_worknest;

-- USERS
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ===================== SCHOOL WORKS =====================
CREATE TABLE school_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_name VARCHAR(150) NOT NULL,
    subject VARCHAR(100),
    description TEXT,
    activity_date DATE,
    activity_time TIME,
    priority ENUM('Low','Medium','High') DEFAULT 'Medium',
    status ENUM('Pending','In Progress','Done') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE school_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    assignment_name VARCHAR(150) NOT NULL,
    subject VARCHAR(100),
    description TEXT,
    teacher VARCHAR(100),
    deadline DATE,
    priority ENUM('Low','Medium','High') DEFAULT 'Medium',
    status ENUM('Pending','In Progress','Done') DEFAULT 'Pending',
    attachment VARCHAR(255) DEFAULT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE school_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_name VARCHAR(150) NOT NULL,
    description TEXT,
    venue VARCHAR(150),
    event_date DATE,
    event_time TIME,
    organizer VARCHAR(100),
    status ENUM('Upcoming','Done','Cancelled') DEFAULT 'Upcoming',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ===================== RESEARCH WORKS =====================
CREATE TABLE research_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_name VARCHAR(150) NOT NULL,
    description TEXT,
    assigned_to VARCHAR(100),
    status ENUM('Pending','In Progress','Done') DEFAULT 'Pending',
    progress INT DEFAULT 0,
    start_date DATE,
    deadline DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE research_deadlines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    deadline_name VARCHAR(150) NOT NULL,
    description TEXT,
    deadline_date DATE,
    status ENUM('Pending','Done') DEFAULT 'Pending',
    reminder VARCHAR(50),
    priority ENUM('Low','Medium','High') DEFAULT 'Medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE research_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    category VARCHAR(100),
    file_path VARCHAR(255),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE research_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    stage_name VARCHAR(100) NOT NULL,
    progress INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ===================== PERSONAL PROJECTS =====================
CREATE TABLE personal_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    project_name VARCHAR(150) NOT NULL,
    description TEXT,
    status ENUM('Not Started','In Progress','On Hold','Completed') DEFAULT 'Not Started',
    progress INT DEFAULT 0,
    deadline DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE project_todos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    task_name VARCHAR(255) NOT NULL,
    is_done TINYINT(1) DEFAULT 0,
    FOREIGN KEY (project_id) REFERENCES personal_projects(id) ON DELETE CASCADE
);

CREATE TABLE project_goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category ENUM('Business','Development','Learning','Other') DEFAULT 'Other',
    goal_name VARCHAR(150) NOT NULL,
    description TEXT,
    target_date DATE,
    progress INT DEFAULT 0,
    status ENUM('Pending','In Progress','Done') DEFAULT 'Pending',
    priority ENUM('Low','Medium','High') DEFAULT 'Medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE project_milestones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    milestone_name VARCHAR(150) NOT NULL,
    milestone_date DATE,
    status ENUM('Pending','Done') DEFAULT 'Pending',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ===================== LEARNING =====================
CREATE TABLE skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    skill_name VARCHAR(100) NOT NULL,
    description TEXT,
    progress INT DEFAULT 0,
    status ENUM('Not Started','Learning','Mastered') DEFAULT 'Not Started',
    level ENUM('Beginner','Intermediate','Advanced') DEFAULT 'Beginner',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE skill_todos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    skill_id INT NOT NULL,
    task_name VARCHAR(255) NOT NULL,
    is_done TINYINT(1) DEFAULT 0,
    FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
);

CREATE TABLE learning_plan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    week_label VARCHAR(50) NOT NULL,
    topic VARCHAR(150) NOT NULL,
    is_done TINYINT(1) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE learning_resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    category VARCHAR(100),
    resource_type ENUM('Website','Course','Book','Video','Documentation') DEFAULT 'Website',
    difficulty ENUM('Beginner','Intermediate','Advanced') DEFAULT 'Beginner',
    estimated_time VARCHAR(50),
    status ENUM('Not Started','In Progress','Completed') DEFAULT 'Not Started',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ===================== CALENDAR (aggregated custom items) =====================
CREATE TABLE calendar_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    item_type ENUM('School','Research','Learning','Projects','Deadline','Other') DEFAULT 'Other',
    item_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ===================== HABITS TO CHANGE =====================
CREATE TABLE habits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    habit_name VARCHAR(100) NOT NULL,
    why_it_matters VARCHAR(255),
    icon VARCHAR(10) DEFAULT '⭐',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE habit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    habit_id INT NOT NULL,
    log_date DATE NOT NULL,
    completed TINYINT(1) DEFAULT 1,
    UNIQUE KEY unique_habit_day (habit_id, log_date),
    FOREIGN KEY (habit_id) REFERENCES habits(id) ON DELETE CASCADE
);

-- ===================== NOTIFICATIONS =====================
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message VARCHAR(255) NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);