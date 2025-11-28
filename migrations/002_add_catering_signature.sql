-- Add CateringSignature column to agreement table
ALTER TABLE `agreement` 
ADD COLUMN `CateringSignature` longtext DEFAULT NULL AFTER `CustomerSignature`;
