-- Add status column to prescriptions table
ALTER TABLE prescriptions 
ADD COLUMN status ENUM('pending', 'approved', 'unapproved') DEFAULT 'pending',
ADD COLUMN approved_at TIMESTAMP NULL;

-- Update existing records to have 'pending' status
UPDATE prescriptions SET status = 'pending' WHERE status IS NULL; 