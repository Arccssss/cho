-- Migrate data from old patient_name to new name fields
-- This script only populates new fields if they are empty, preserving existing data

-- Update appointments with new name fields (only if they are empty)
UPDATE appointments 
SET 
    last_name = SUBSTRING_INDEX(COALESCE(patient_name, ''), ' ', -1),
    first_name = SUBSTRING_INDEX(SUBSTRING_INDEX(COALESCE(patient_name, ''), ' ', 1), ' ', -1)
WHERE 
    (first_name IS NULL OR first_name = '') 
    AND (last_name IS NULL OR last_name = '') 
    AND patient_name IS NOT NULL 
    AND patient_name != '';

-- Show migration results
SELECT 
    COUNT(*) as total_migrated,
    COUNT(*) as total_with_first_name,
    COUNT(*) as total_with_last_name,
    COUNT(*) as total_empty_first,
    COUNT(*) as total_empty_last,
    COUNT(*) as total_empty_both
FROM appointments 
WHERE 
    (first_name IS NULL OR first_name = '') 
    AND (last_name IS NULL OR last_name = '');

-- Show sample of migrated records
SELECT 
    id,
    patient_name as original_name,
    first_name,
    last_name,
    middle_name,
    suffix,
    'Migrated' as migration_status
FROM appointments 
WHERE 
    (first_name IS NOT NULL AND first_name != '') 
    OR (last_name IS NOT NULL AND last_name != '')
LIMIT 10;
