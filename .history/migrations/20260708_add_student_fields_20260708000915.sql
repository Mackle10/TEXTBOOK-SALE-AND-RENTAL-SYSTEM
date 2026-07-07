-- Migration: Add student category and education fields
-- Back up your database before running: mysqldump -u root textbooks > textbooks_backup.sql

-- 1) Add columns if they don't exist
ALTER TABLE students
  ADD COLUMN IF NOT EXISTS student_category ENUM('University','High School') NOT NULL DEFAULT 'University',
  ADD COLUMN IF NOT EXISTS education_level VARCHAR(100) NOT NULL DEFAULT 'Undeclared',
  ADD COLUMN IF NOT EXISTS year_class VARCHAR(20) NOT NULL DEFAULT 'Year 1',
  ADD COLUMN IF NOT EXISTS year_of_study TINYINT NULL DEFAULT NULL;

-- 2) Populate sensible defaults for existing rows
-- If a row looks like a university student (has a numeric year), keep it; otherwise default to University
UPDATE students
SET student_category = 'University'
WHERE student_category IS NULL OR student_category = '';

UPDATE students
SET education_level = COALESCE(NULLIF(education_level, ''), COALESCE(NULLIF(course, ''), 'Undeclared'))
WHERE education_level IS NULL OR education_level = '';

UPDATE students
SET year_class = COALESCE(NULLIF(year_class, ''), CONCAT('Year ', GREATEST(1, LEAST(5, IFNULL(year_of_study, 1)))))
WHERE year_class IS NULL OR year_class = '';

-- 3) For high-school conversions you may want to adjust specific rows manually.
-- Example: mark user_id 123 as High School student
-- UPDATE students SET student_category = 'High School', education_level = 'Advanced Level / A-Level', year_class = 'Senior 4', year_of_study = NULL WHERE user_id = 123;

-- 4) (Optional) Add index on student_category for faster queries
ALTER TABLE students ADD INDEX IF NOT EXISTS idx_students_category (student_category);

-- End of migration
