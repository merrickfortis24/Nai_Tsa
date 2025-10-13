-- Migration: extend order_payment_receipt for GCash verification workflow
START TRANSACTION;

-- Ensure table exists (noop if already present)
CREATE TABLE IF NOT EXISTS order_payment_receipt (
  Payment_Receipt_ID INT NOT NULL AUTO_INCREMENT,
  Order_ID INT NOT NULL,
  payment_received_at DATETIME NULL,
  payment_received_by INT NULL,
  Proof_Photo VARCHAR(255) NULL,
  PRIMARY KEY (Payment_Receipt_ID),
  KEY idx_opr_order (Order_ID),
  CONSTRAINT fk_opr_order FOREIGN KEY (Order_ID) REFERENCES orders(Order_ID) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add missing columns if not present
ALTER TABLE order_payment_receipt
  ADD COLUMN IF NOT EXISTS Reference_Number VARCHAR(100) NULL AFTER Proof_Photo,
  ADD COLUMN IF NOT EXISTS Submitted_Amount DECIMAL(10,2) NULL DEFAULT 0.00 AFTER Reference_Number,
  ADD COLUMN IF NOT EXISTS Status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending' AFTER Submitted_Amount,
  ADD COLUMN IF NOT EXISTS Verified_By INT NULL AFTER Status,
  ADD COLUMN IF NOT EXISTS Verified_At DATETIME NULL AFTER Verified_By,
  ADD COLUMN IF NOT EXISTS Reject_Reason TEXT NULL AFTER Verified_At,
  ADD KEY IF NOT EXISTS idx_opr_status (Status),
  ADD KEY IF NOT EXISTS idx_opr_ref (Reference_Number);

COMMIT;
