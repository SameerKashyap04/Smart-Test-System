-- Add voice detection settings to exams table
USE smart_test_system;

-- Add voice detection columns to exams table
ALTER TABLE exams 
ADD COLUMN voice_detection_enabled TINYINT(1) DEFAULT 1,
ADD COLUMN voice_sensitivity DECIMAL(3,2) DEFAULT 0.30,
ADD COLUMN voice_violation_threshold INT DEFAULT 2,
ADD COLUMN voice_max_violations INT DEFAULT 5,
ADD COLUMN microphone_required TINYINT(1) DEFAULT 1;

-- Update existing exams to have voice detection enabled by default
UPDATE exams SET 
    voice_detection_enabled = 1,
    voice_sensitivity = 0.30,
    voice_violation_threshold = 2,
    voice_max_violations = 5,
    microphone_required = 1;

-- Show success message
SELECT 'Voice detection settings added to exams table successfully!' as message;
