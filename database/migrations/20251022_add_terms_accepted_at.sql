-- Migration: add terms_accepted_at to customer table
-- Run this against your MySQL database to add the column (nullable)
ALTER TABLE customer
  ADD COLUMN terms_accepted_at TIMESTAMP NULL DEFAULT NULL;

-- Optionally, if you want to record a terms_version or similar, add that as well:
-- ALTER TABLE customer
--   ADD COLUMN terms_version VARCHAR(32) NULL DEFAULT NULL;
