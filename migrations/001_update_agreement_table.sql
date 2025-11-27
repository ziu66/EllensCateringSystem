-- Migration: Update agreement table with new columns for signature and PDF storage
-- Date: November 28, 2025

-- Add new columns to agreement table
ALTER TABLE `agreement` 
  ADD COLUMN `ClientID` int(11) DEFAULT NULL AFTER `AdminID`,
  ADD COLUMN `CustomerSignature` longtext DEFAULT NULL AFTER `ContractFile`,
  ADD COLUMN `Status` enum('unsigned','signed') DEFAULT 'unsigned' AFTER `SignedDate`,
  ADD COLUMN `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  ADD COLUMN `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp();

-- Change ContractFile from varchar(255) to longtext to store full PDF
ALTER TABLE `agreement` 
  MODIFY `ContractFile` longtext DEFAULT NULL;

-- Add foreign key for ClientID if not exists
ALTER TABLE `agreement`
  ADD CONSTRAINT `agreement_ibfk_3` FOREIGN KEY (`ClientID`) REFERENCES `client` (`ClientID`)
  ON DELETE CASCADE ON UPDATE CASCADE;

-- Create index for faster queries
ALTER TABLE `agreement`
  ADD INDEX `idx_client_id` (`ClientID`),
  ADD INDEX `idx_status` (`Status`);
