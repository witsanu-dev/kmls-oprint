-- Pharmacy Note OPD Card System - Kamalasai Hospital
-- Database Schema Script

CREATE TABLE IF NOT EXISTS `oprint_pharmacy_note` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `vn` VARCHAR(20) NOT NULL,
  `hn` VARCHAR(20) NOT NULL,
  `stop_drug_icode` VARCHAR(20) DEFAULT NULL,
  `stop_drug_name` VARCHAR(255) DEFAULT NULL,
  `stop_start_date` DATE DEFAULT NULL,
  `stop_end_date` DATE DEFAULT NULL,
  `consult_doctor_code` VARCHAR(20) DEFAULT NULL,
  `consult_doctor_name` VARCHAR(255) DEFAULT NULL,
  `consult_time` TIME DEFAULT NULL,
  `pharmacist_name` VARCHAR(255) DEFAULT NULL,
  `note_remark` TEXT DEFAULT NULL,
  `status` VARCHAR(20) DEFAULT 'active',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_vn` (`vn`),
  INDEX `idx_hn` (`hn`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
