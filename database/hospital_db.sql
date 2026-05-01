-- ============================================================
-- Hospital Appointment Management System - Database Schema
-- Database: hospital_db
-- Version: 1.0.0 (Production Ready)
-- ============================================================

-- Create database
CREATE DATABASE IF NOT EXISTS hospital_db 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

USE hospital_db;

-- ============================================================
-- 1. ROLES TABLE (RBAC Foundation)
-- ============================================================
CREATE TABLE IF NOT EXISTS roles (
    role_id         INT PRIMARY KEY AUTO_INCREMENT,
    role_name       VARCHAR(50) NOT NULL UNIQUE,
    role_slug       VARCHAR(50) NOT NULL UNIQUE,
    description     VARCHAR(255),
    permissions     JSON,
    is_active       TINYINT(1) DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert core roles
INSERT INTO roles (role_name, role_slug, description, permissions) VALUES
('Administrator', 'admin', 'Full system control', 
 '{"users":["create","read","update","delete"],"appointments":["create","read","update","delete","all"],"departments":["create","read","update","delete"],"reports":["read"],"settings":["read","update"]}'),
('Doctor', 'doctor', 'Medical practitioner', 
 '{"appointments":["read","update","assigned"],"patients":["read","update"],"medical_records":["create","read","update"],"schedule":["read","update"]}'),
('Receptionist', 'receptionist', 'Front desk management', 
 '{"appointments":["create","read","update","all"],"patients":["create","read","update"],"walk_ins":["create","read","update"]}'),
('Patient', 'patient', 'Patient portal access', 
 '{"appointments":["create","read","own"],"medical_history":["read","own"],"profile":["read","update","own"]}');

-- ============================================================
-- 2. DEPARTMENTS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS departments (
    dept_id         INT PRIMARY KEY AUTO_INCREMENT,
    dept_name       VARCHAR(100) NOT NULL,
    dept_code       VARCHAR(20) UNIQUE,
    description     TEXT,
    location        VARCHAR(100),
    is_active       TINYINT(1) DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO departments (dept_name, dept_code, description, location) VALUES
('General Medicine', 'GM001', 'General medical consultations and primary care', 'Building A, Floor 1'),
('Cardiology', 'CD001', 'Heart and cardiovascular system specialists', 'Building B, Floor 2'),
('Neurology', 'NE001', 'Brain and nervous system specialists', 'Building B, Floor 3'),
('Orthopedics', 'OR001', 'Musculoskeletal system specialists', 'Building C, Floor 1'),
('Pediatrics', 'PE001', 'Children and adolescents medical care', 'Building A, Floor 2'),
('Dermatology', 'DE001', 'Skin, hair, and nail specialists', 'Building C, Floor 2'),
('Gynecology', 'GY001', 'Women reproductive health specialists', 'Building A, Floor 3'),
('ENT', 'ENT01', 'Ear, Nose, and Throat specialists', 'Building C, Floor 3');

-- ============================================================
-- 3. USERS TABLE (All roles in one table for simplicity & performance)
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    user_id         INT PRIMARY KEY AUTO_INCREMENT,
    role_id         INT NOT NULL,
    email           VARCHAR(100) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    first_name      VARCHAR(100) NOT NULL,
    last_name       VARCHAR(100) NOT NULL,
    phone           VARCHAR(20),
    date_of_birth   DATE,
    gender          ENUM('male','female','other') DEFAULT 'other',
    address         TEXT,
    city            VARCHAR(50),
    country         VARCHAR(50) DEFAULT 'USA',
    zip_code        VARCHAR(20),
    
    -- Role-specific fields (nullable, populated based on role)
    dept_id         INT,                    -- For doctors
    specialization  VARCHAR(100),           -- For doctors
    license_number  VARCHAR(50),            -- For doctors
    qualification   VARCHAR(255),         -- For doctors
    experience_years INT,                  -- For doctors
    consultation_fee DECIMAL(10,2),         -- For doctors
    
    avatar          VARCHAR(255),
    
    -- Account status
    is_active       TINYINT(1) DEFAULT 1,
    is_verified     TINYINT(1) DEFAULT 0,
    email_verified_at TIMESTAMP NULL,
    last_login      TIMESTAMP NULL,
    last_login_ip   VARCHAR(45),
    
    -- Security
    failed_login_attempts INT DEFAULT 0,
    locked_until    TIMESTAMP NULL,
    remember_token  VARCHAR(255),
    
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE RESTRICT,
    FOREIGN KEY (dept_id) REFERENCES departments(dept_id) ON DELETE SET NULL,
    INDEX idx_email (email),
    INDEX idx_role (role_id),
    INDEX idx_active (is_active)
);

-- ============================================================
-- 4. DOCTOR SCHEDULES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS doctor_schedules (
    schedule_id     INT PRIMARY KEY AUTO_INCREMENT,
    doctor_id       INT NOT NULL,
    day_of_week     TINYINT NOT NULL CHECK (day_of_week BETWEEN 0 AND 6), -- 0=Sunday
    start_time      TIME NOT NULL,
    end_time        TIME NOT NULL,
    slot_duration   INT DEFAULT 30, -- minutes
    max_appointments_per_slot INT DEFAULT 1,
    is_available    TINYINT(1) DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (doctor_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_schedule (doctor_id, day_of_week, start_time)
);

-- ============================================================
-- 5. APPOINTMENTS TABLE (Core business logic)
-- ============================================================
CREATE TABLE IF NOT EXISTS appointments (
    appointment_id  INT PRIMARY KEY AUTO_INCREMENT,
    patient_id      INT NOT NULL,
    doctor_id       INT,
    dept_id         INT,
    
    -- Appointment details
    appointment_date DATE NOT NULL,
    start_time      TIME NOT NULL,
    end_time        TIME,
    
    -- Type and reason
    appointment_type ENUM('regular','follow_up','emergency','walk_in') DEFAULT 'regular',
    reason          TEXT NOT NULL,
    symptoms        TEXT,
    notes           TEXT,
    
    -- Workflow status tracking
    status          ENUM('pending','approved','rejected','completed','cancelled','no_show') 
                    DEFAULT 'pending',
    
    -- Role-based action tracking
    requested_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    requested_by    INT,
    
    reviewed_by     INT,                -- Receptionist who approved/rejected
    reviewed_at     TIMESTAMP NULL,
    review_notes    VARCHAR(255),         -- Reason for rejection etc.
    
    confirmed_by_doctor TINYINT(1) DEFAULT 0,
    doctor_accepted_at TIMESTAMP NULL,
    doctor_notes    TEXT,                 -- Doctor consultation notes
    
    completed_at    TIMESTAMP NULL,
    completed_by    INT,
    
    cancelled_by    INT,
    cancelled_at    TIMESTAMP NULL,
    cancellation_reason TEXT,
    
    -- Reschedule chain
    reschedule_count INT DEFAULT 0,
    previous_appointment_id INT NULL,
    
    -- Reminders
    reminder_sent   TINYINT(1) DEFAULT 0,
    reminder_sent_at TIMESTAMP NULL,
    
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (dept_id) REFERENCES departments(dept_id) ON DELETE SET NULL,
    FOREIGN KEY (requested_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (reviewed_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (completed_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (cancelled_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (previous_appointment_id) REFERENCES appointments(appointment_id) ON DELETE SET NULL,
    
    INDEX idx_patient (patient_id),
    INDEX idx_doctor (doctor_id),
    INDEX idx_status (status),
    INDEX idx_date (appointment_date)
);

-- ============================================================
-- 6. MEDICAL RECORDS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS medical_records (
    record_id       INT PRIMARY KEY AUTO_INCREMENT,
    patient_id      INT NOT NULL,
    appointment_id  INT,
    doctor_id       INT NOT NULL,
    
    -- Diagnosis & Treatment
    diagnosis       TEXT,
    symptoms        TEXT,
    prescription    TEXT,
    tests_recommended TEXT,
    test_results    TEXT,
    
    -- Vitals
    blood_pressure  VARCHAR(20),
    heart_rate      INT,
    temperature     DECIMAL(4,1),
    weight          DECIMAL(6,2),
    height          DECIMAL(5,2),
    
    -- Documents
    attachment_path VARCHAR(255),
    
    -- Visibility
    is_confidential TINYINT(1) DEFAULT 0,
    
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE SET NULL,
    FOREIGN KEY (doctor_id) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_patient_records (patient_id, created_at)
);

-- ============================================================
-- 7. NOTIFICATIONS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id         INT NOT NULL,
    type            ENUM('appointment_approved','appointment_rejected','appointment_reminder',
                         'appointment_cancelled','appointment_completed','doctor_assigned',
                         'system','welcome') DEFAULT 'system',
    title           VARCHAR(100) NOT NULL,
    message         TEXT NOT NULL,
    
    -- Link to related entity
    related_id      INT,
    related_type    VARCHAR(50), -- 'appointment', 'user', etc.
    
    -- Status
    is_read         TINYINT(1) DEFAULT 0,
    read_at         TIMESTAMP NULL,
    
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_unread (user_id, is_read)
);

-- ============================================================
-- 8. AUDIT LOGS (Security & Compliance)
-- ============================================================
CREATE TABLE IF NOT EXISTS audit_logs (
    log_id          INT PRIMARY KEY AUTO_INCREMENT,
    user_id         INT,
    action          VARCHAR(100) NOT NULL,
    entity_type     VARCHAR(50),
    entity_id       INT,
    old_values      JSON,
    new_values      JSON,
    ip_address      VARCHAR(45),
    user_agent      VARCHAR(255),
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_action (action),
    INDEX idx_created (created_at)
);

-- ============================================================
-- 9. SYSTEM SETTINGS
-- ============================================================
CREATE TABLE IF NOT EXISTS system_settings (
    setting_id      INT PRIMARY KEY AUTO_INCREMENT,
    setting_key     VARCHAR(100) UNIQUE NOT NULL,
    setting_value   TEXT,
    setting_type    ENUM('string','integer','boolean','json') DEFAULT 'string',
    description     VARCHAR(255),
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES
('hospital_name', 'City General Hospital', 'string', 'Hospital display name'),
('hospital_address', '123 Healthcare Avenue, Medical District', 'string', 'Hospital address'),
('max_appointments_per_day', '50', 'integer', 'Maximum appointments per day'),
('appointment_reminder_hours', '24', 'integer', 'Hours before appointment to send reminder'),
('allow_walk_in', '1', 'boolean', 'Allow walk-in appointments'),
('default_slot_duration', '30', 'integer', 'Default appointment duration in minutes'),
('system_email', 'noreply@citygeneral.com', 'string', 'System notification email'),
('maintenance_mode', '0', 'boolean', 'System maintenance mode');

-- ============================================================
-- SEED DATA - Sample Users (Passwords are bcrypt hashed for 'password123')
-- ============================================================

-- Admin user (password: password123)
INSERT INTO users (role_id, email, password_hash, first_name, last_name, phone, is_verified) VALUES
(1, 'admin@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', '555-0100', 1);

-- Sample doctors (password: password123)
INSERT INTO users (role_id, email, password_hash, first_name, last_name, phone, dept_id, specialization, license_number, qualification, experience_years, consultation_fee, is_verified) VALUES
(2, 'dr.smith@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Smith', '555-0201', 1, 'Internal Medicine', 'MD-100001', 'MD, Harvard Medical School', 15, 150.00, 1),
(2, 'dr.jones@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah', 'Jones', '555-0202', 2, 'Cardiology', 'MD-100002', 'MD, Johns Hopkins University', 12, 250.00, 1),
(2, 'dr.patel@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rajesh', 'Patel', '555-0203', 5, 'Pediatrics', 'MD-100003', 'MD, Stanford University', 10, 180.00, 1);

-- Sample receptionist (password: password123)
INSERT INTO users (role_id, email, password_hash, first_name, last_name, phone, is_verified) VALUES
(3, 'reception@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mary', 'Johnson', '555-0301', 1);

-- Sample patient (password: password123)
INSERT INTO users (role_id, email, password_hash, first_name, last_name, phone, date_of_birth, gender, address, city, is_verified) VALUES
(4, 'patient@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'James', 'Wilson', '555-0401', '1985-03-15', 'male', '456 Oak Street', 'Springfield', 1),
(4, 'linda.parker@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Linda', 'Parker', '555-0402', '1990-07-22', 'female', '789 Pine Avenue', 'Springfield', 1);

-- ============================================================
-- SAMPLE DOCTOR SCHEDULES
-- ============================================================
INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time) VALUES
(2, 1, '09:00:00', '17:00:00'),  -- Dr. Smith - Monday
(2, 3, '09:00:00', '17:00:00'),  -- Dr. Smith - Wednesday
(2, 5, '09:00:00', '14:00:00'),  -- Dr. Smith - Friday
(3, 2, '10:00:00', '18:00:00'),  -- Dr. Jones - Tuesday
(3, 4, '10:00:00', '18:00:00'),  -- Dr. Jones - Thursday
(4, 1, '08:00:00', '16:00:00'),  -- Dr. Patel - Monday
(4, 3, '08:00:00', '16:00:00'),  -- Dr. Patel - Wednesday
(4, 5, '08:00:00', '12:00:00'); -- Dr. Patel - Friday

-- ============================================================
-- VIEWS FOR COMMON QUERIES
-- ============================================================

CREATE OR REPLACE VIEW vw_user_roles AS
SELECT u.user_id, u.email, u.first_name, u.last_name, u.phone, u.is_active,
       r.role_id, r.role_name, r.role_slug
FROM users u
JOIN roles r ON u.role_id = r.role_id;

CREATE OR REPLACE VIEW vw_doctor_departments AS
SELECT u.user_id, u.first_name, u.last_name, u.email, u.phone, u.specialization,
       u.license_number, u.qualification, u.experience_years, u.consultation_fee,
       d.dept_id, d.dept_name, d.location
FROM users u
LEFT JOIN departments d ON u.dept_id = d.dept_id
WHERE u.role_id = 2 AND u.is_active = 1;

CREATE OR REPLACE VIEW vw_appointment_details AS
SELECT 
    a.appointment_id,
    a.appointment_date,
    a.start_time,
    a.end_time,
    a.appointment_type,
    a.reason,
    a.symptoms,
    a.status,
    a.notes as appointment_notes,
    a.doctor_notes,
    a.review_notes,
    a.cancellation_reason,
    a.created_at,
    a.reschedule_count,
    
    p.user_id as patient_id,
    CONCAT(p.first_name, ' ', p.last_name) as patient_name,
    p.email as patient_email,
    p.phone as patient_phone,
    
    d.user_id as doctor_id,
    CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
    d.specialization,
    
    dept.dept_name,
    
    r.first_name as reviewed_by_name
FROM appointments a
JOIN users p ON a.patient_id = p.user_id
LEFT JOIN users d ON a.doctor_id = d.user_id
LEFT JOIN departments dept ON a.dept_id = dept.dept_id
LEFT JOIN users r ON a.reviewed_by = r.user_id;

-- ============================================================
-- STORED PROCEDURES
-- ============================================================

DELIMITER //

-- Get available slots for a doctor on a date
CREATE PROCEDURE sp_get_available_slots(
    IN p_doctor_id INT,
    IN p_date DATE
)
BEGIN
    DECLARE v_day_of_week TINYINT;
    SET v_day_of_week = DAYOFWEEK(p_date) - 1;
    
    SELECT 
        ds.schedule_id,
        ds.start_time,
        ds.end_time,
        ds.slot_duration,
        TIME_FORMAT(ds.start_time, '%h:%i %p') as slot_time,
        (SELECT COUNT(*) FROM appointments a 
         WHERE a.doctor_id = p_doctor_id 
         AND a.appointment_date = p_date 
         AND a.start_time = ds.start_time 
         AND a.status IN ('pending','approved')) as booked_count,
        ds.max_appointments_per_slot
    FROM doctor_schedules ds
    WHERE ds.doctor_id = p_doctor_id 
    AND ds.day_of_week = v_day_of_week
    AND ds.is_available = 1
    HAVING booked_count < max_appointments_per_slot
    ORDER BY ds.start_time;
END //

-- Get appointment statistics
CREATE PROCEDURE sp_get_dashboard_stats(IN p_user_id INT, IN p_role_id INT)
BEGIN
    IF p_role_id = 1 THEN -- Admin
        SELECT 
            (SELECT COUNT(*) FROM appointments WHERE DATE(created_at) = CURDATE()) as today_appointments,
            (SELECT COUNT(*) FROM appointments WHERE status = 'pending') as pending_appointments,
            (SELECT COUNT(*) FROM appointments WHERE status = 'approved' AND appointment_date >= CURDATE()) as upcoming_appointments,
            (SELECT COUNT(*) FROM users WHERE role_id = 4) as total_patients,
            (SELECT COUNT(*) FROM users WHERE role_id = 2) as total_doctors,
            (SELECT COUNT(*) FROM users) as total_users;
    ELSEIF p_role_id = 2 THEN -- Doctor
        SELECT 
            (SELECT COUNT(*) FROM appointments WHERE doctor_id = p_user_id AND DATE(created_at) = CURDATE()) as today_appointments,
            (SELECT COUNT(*) FROM appointments WHERE doctor_id = p_user_id AND status = 'pending') as pending_appointments,
            (SELECT COUNT(*) FROM appointments WHERE doctor_id = p_user_id AND status = 'approved' AND appointment_date >= CURDATE()) as upcoming_appointments,
            (SELECT COUNT(*) FROM appointments WHERE doctor_id = p_user_id AND status = 'completed') as completed_appointments,
            (SELECT COUNT(*) FROM medical_records WHERE doctor_id = p_user_id) as total_records,
            (SELECT COUNT(*) FROM appointments WHERE doctor_id = p_user_id AND status = 'cancelled') as cancelled_appointments;
    ELSEIF p_role_id = 3 THEN -- Receptionist
        SELECT 
            (SELECT COUNT(*) FROM appointments WHERE DATE(created_at) = CURDATE()) as today_appointments,
            (SELECT COUNT(*) FROM appointments WHERE status = 'pending') as pending_approvals,
            (SELECT COUNT(*) FROM appointments WHERE status = 'approved' AND appointment_date = CURDATE()) as today_confirmed,
            (SELECT COUNT(*) FROM appointments WHERE appointment_type = 'walk_in' AND DATE(created_at) = CURDATE()) as walk_ins_today,
            (SELECT COUNT(*) FROM users WHERE role_id = 4 AND DATE(created_at) = CURDATE()) as new_patients_today,
            (SELECT COUNT(*) FROM appointments WHERE status = 'no_show' AND appointment_date = CURDATE()) as no_shows_today;
    ELSEIF p_role_id = 4 THEN -- Patient
        SELECT 
            (SELECT COUNT(*) FROM appointments WHERE patient_id = p_user_id) as total_appointments,
            (SELECT COUNT(*) FROM appointments WHERE patient_id = p_user_id AND status = 'approved' AND appointment_date >= CURDATE()) as upcoming_appointments,
            (SELECT COUNT(*) FROM appointments WHERE patient_id = p_user_id AND status = 'completed') as completed_appointments,
            (SELECT COUNT(*) FROM appointments WHERE patient_id = p_user_id AND status = 'pending') as pending_appointments,
            (SELECT COUNT(*) FROM medical_records WHERE patient_id = p_user_id) as medical_records_count,
            (SELECT COUNT(*) FROM notifications WHERE user_id = p_user_id AND is_read = 0) as unread_notifications;
    END IF;
END //

DELIMITER ;

-- ============================================================
-- TRIGGERS
-- ============================================================

DELIMITER //

-- Auto-create notification on appointment status change
CREATE TRIGGER tr_appointment_status_notification
AFTER UPDATE ON appointments
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        IF NEW.status = 'approved' THEN
            INSERT INTO notifications (user_id, type, title, message, related_id, related_type)
            VALUES (NEW.patient_id, 'appointment_approved', 'Appointment Approved', 
                    CONCAT('Your appointment for ', NEW.appointment_date, ' has been approved.'),
                    NEW.appointment_id, 'appointment');
        ELSEIF NEW.status = 'rejected' THEN
            INSERT INTO notifications (user_id, type, title, message, related_id, related_type)
            VALUES (NEW.patient_id, 'appointment_rejected', 'Appointment Rejected',
                    CONCAT('Your appointment request for ', NEW.appointment_date, ' was rejected. Reason: ', IFNULL(NEW.review_notes, 'No reason provided')),
                    NEW.appointment_id, 'appointment');
        ELSEIF NEW.status = 'cancelled' THEN
            INSERT INTO notifications (user_id, type, title, message, related_id, related_type)
            VALUES (NEW.patient_id, 'appointment_cancelled', 'Appointment Cancelled',
                    CONCAT('Your appointment for ', NEW.appointment_date, ' has been cancelled.'),
                    NEW.appointment_id, 'appointment');
        END IF;
    END IF;
END //

DELIMITER ;

-- ============================================================
-- END OF SCHEMA
-- ============================================================