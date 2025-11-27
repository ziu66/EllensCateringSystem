-- Fix agreement table to ensure AgreementID has AUTO_INCREMENT
-- Run this if you already have the agreement table created

ALTER TABLE `agreement`
  DROP PRIMARY KEY;

ALTER TABLE `agreement`
  MODIFY `AgreementID` int(11) NOT NULL AUTO_INCREMENT,
  ADD PRIMARY KEY (`AgreementID`);
