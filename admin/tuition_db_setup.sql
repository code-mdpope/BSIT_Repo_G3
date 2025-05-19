-- Tuition Settings Table
CREATE TABLE IF NOT EXISTS tuition_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    academic_year INT NOT NULL,
    semester VARCHAR(20) NOT NULL,
    tuition_per_credit DECIMAL(10, 2) NOT NULL,
    registration_fee DECIMAL(10, 2) NOT NULL,
    technology_fee DECIMAL(10, 2) NOT NULL,
    activity_fee DECIMAL(10, 2) NOT NULL,
    health_fee DECIMAL(10, 2) NOT NULL,
    discount_full_time DECIMAL(5, 2) NOT NULL,
    discount_early_payment DECIMAL(5, 2) NOT NULL,
    late_payment_penalty DECIMAL(10, 2) NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY unique_term (academic_year, semester)
);

-- Student Tuition Records
CREATE TABLE IF NOT EXISTS student_tuition (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    academic_year INT NOT NULL,
    semester VARCHAR(20) NOT NULL,
    tuition_amount DECIMAL(10, 2) NOT NULL,
    fees_amount DECIMAL(10, 2) NOT NULL,
    discounts_amount DECIMAL(10, 2) NOT NULL,
    penalties_amount DECIMAL(10, 2) NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    amount_paid DECIMAL(10, 2) NOT NULL DEFAULT 0,
    balance DECIMAL(10, 2) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    due_date DATE NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (student_id) REFERENCES users(user_id)
);

-- Sample Data for Tuition Settings
INSERT INTO tuition_settings 
    (academic_year, semester, tuition_per_credit, registration_fee, technology_fee, 
     activity_fee, health_fee, discount_full_time, discount_early_payment, late_payment_penalty, updated_at)
VALUES
    (2025, 'Spring', 350.00, 150.00, 200.00, 100.00, 75.00, 5.00, 3.00, 10.00, NOW()),
    (2024, 'Fall', 335.00, 145.00, 190.00, 95.00, 70.00, 5.00, 3.00, 10.00, NOW()),
    (2024, 'Summer', 335.00, 145.00, 190.00, 95.00, 70.00, 5.00, 3.00, 10.00, NOW()),
    (2024, 'Spring', 335.00, 145.00, 190.00, 95.00, 70.00, 5.00, 3.00, 10.00, NOW());

-- Update payments table to add reference to tuition record
ALTER TABLE payments 
ADD COLUMN tuition_id INT NULL,
ADD FOREIGN KEY (tuition_id) REFERENCES student_tuition(id); 