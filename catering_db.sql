-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 25, 2025 at 11:06 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `catering_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `LogID` int(11) NOT NULL,
  `UserID` int(11) DEFAULT NULL,
  `UserType` enum('admin','client') DEFAULT NULL,
  `Action` varchar(100) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `IPAddress` varchar(45) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`LogID`, `UserID`, `UserType`, `Action`, `Description`, `IPAddress`, `CreatedAt`) VALUES
(1, 10, 'client', 'registration', 'New client registered with email verification', '::1', '2025-10-25 07:41:26'),
(2, 11, 'client', 'registration', 'New client registered with email verification', '::1', '2025-10-25 07:44:16');

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `AdminID` int(11) NOT NULL,
  `Name` varchar(100) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Password` varchar(255) DEFAULT NULL,
  `user_role` enum('admin','client') NOT NULL DEFAULT 'admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`AdminID`, `Name`, `Email`, `Password`, `user_role`) VALUES
(1, 'AdminSofia', 'adminsofia@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `agreement`
--

CREATE TABLE `agreement` (
  `AgreementID` int(11) NOT NULL AUTO_INCREMENT,
  `BookingID` int(11) DEFAULT NULL,
  `AdminID` int(11) DEFAULT NULL,
  `ClientID` int(11) DEFAULT NULL,
  `ContractFile` longtext DEFAULT NULL,
  `CustomerSignature` longtext DEFAULT NULL,
  `SignedDate` date DEFAULT NULL,
  `Status` enum('unsigned','signed') DEFAULT 'unsigned',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`AgreementID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `booking`
--

CREATE TABLE `booking` (
  `BookingID` int(11) NOT NULL,
  `ClientID` int(11) DEFAULT NULL,
  `EventType` varchar(50) DEFAULT NULL,
  `DateBooked` date DEFAULT NULL,
  `EventDate` date DEFAULT NULL,
  `EventLocation` text DEFAULT NULL,
  `NumberOfGuests` int(11) DEFAULT NULL,
  `SpecialRequests` text DEFAULT NULL,
  `Status` enum('Pending','Confirmed','Completed','Cancelled') DEFAULT NULL,
  `TotalAmount` decimal(10,2) DEFAULT NULL,
  `PaymentStatus` enum('Pending Payment','Processing','Paid','Failed') DEFAULT 'Pending Payment',
  `PaymentMethod` enum('Cash','GCash','Bank Transfer') DEFAULT NULL,
  `PaymentDate` datetime DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `booking_menu`
--

CREATE TABLE `booking_menu` (
  `BookingID` int(11) NOT NULL,
  `MenuID` int(11) NOT NULL,
  `Quantity` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `booking_package`
--

CREATE TABLE `booking_package` (
  `BookingID` int(11) NOT NULL,
  `PackageID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `client`
--

CREATE TABLE `client` (
  `ClientID` int(11) NOT NULL,
  `Name` varchar(100) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Password` varchar(255) NOT NULL,
  `IsEmailVerified` tinyint(1) NOT NULL DEFAULT 0,
  `EmailVerifiedAt` datetime DEFAULT NULL,
  `ContactNumber` varchar(20) DEFAULT NULL,
  `Address` text DEFAULT NULL,
  `ProfileImage` varchar(255) DEFAULT NULL,
  `user_role` enum('admin','client') NOT NULL DEFAULT 'client',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `client`
--

INSERT INTO `client` (`ClientID`, `Name`, `Email`, `Password`, `IsEmailVerified`, `EmailVerifiedAt`, `ContactNumber`, `Address`, `ProfileImage`, `user_role`, `CreatedAt`, `UpdatedAt`) VALUES
(1, 'Sofia Tapongco', 'sofia@gmail.com', 'sofiatapongco', 0, NULL, '09167898776', 'Nasugbu, Batangas', NULL, 'client', '2025-10-25 06:25:38', '2025-10-25 06:25:38'),
(2, 'Erich Castillo', 'erichn@gmail.com', 'erichcastillo', 0, NULL, '09167074350', 'Lian, Batangas', NULL, 'client', '2025-10-25 06:25:38', '2025-10-25 06:25:38'),
(3, 'John Lei', 'Lei@gmail.com', 'leitorres', 0, NULL, '09167898765', 'Nasugbu, Lian Batangas', NULL, 'client', '2025-10-25 06:25:38', '2025-10-25 06:25:38'),
(4, 'Yeoj Valdez', 'Yeoj@gmail.com', 'yeojvaldez', 0, NULL, '091654536577', 'Lian, Batangas', NULL, 'client', '2025-10-25 06:25:38', '2025-10-25 06:25:38'),
(5, 'Ara Felicisimo', 'arafelicisimo@gmail.com', 'arafelicisimo', 0, NULL, '09694335266', 'Malaruhatan, Lian Batangas', NULL, 'client', '2025-10-25 06:25:38', '2025-10-25 06:25:38'),
(6, 'Elmira Despo', 'elmiradespo@gmail.com', 'elmiradespo', 0, NULL, '096789876544', 'Malaruhatan, Lian Batangas', NULL, 'client', '2025-10-25 06:25:38', '2025-10-25 06:25:38'),
(7, 'Aaron James', 'aaron@gmail.com', 'aaronjames', 0, NULL, '09786565434', 'Lian,Batangas', NULL, 'client', '2025-10-25 06:25:38', '2025-10-25 06:25:38'),
(8, 'Rheyven Bausas', 'rheyven@gmail.com', 'rheyvenbausas', 0, NULL, '09165456787', 'Prenza, Lian Batangas', NULL, 'client', '2025-10-25 06:25:38', '2025-10-25 06:25:38'),
(9, 'Lea Castro', 'leacastro@gmail.com', 'leacastro', 0, NULL, '09165456787', 'Nasugbu, Lian Batangas', NULL, 'client', '2025-10-25 06:25:38', '2025-10-25 06:25:38'),
(10, 'jem ganda', 'jemsalvacion28@gmail.com', '$2y$10$4FegFUcQK2K9KJxk6/RBmu5Lq/voV8wXY/JPdaLJ1wzz/cx7sZqBW', 0, NULL, '09851590335', 'Pantalan', NULL, 'client', '2025-10-25 07:41:26', '2025-10-25 07:41:26'),
(11, 'lei', 'leitorres030@gmail.com', '$2y$10$vKradLUBcBYxLqy4/ID30.x05KHWCNs2ekpeGkMuH2EnricHOP/.2', 0, NULL, '09851590335', 'wawa', NULL, 'client', '2025-10-25 07:44:16', '2025-10-25 07:44:16');

-- --------------------------------------------------------

--
-- Table structure for table `email_queue`
--

CREATE TABLE `email_queue` (
  `EmailID` int(11) NOT NULL,
  `RecipientEmail` varchar(100) NOT NULL,
  `Subject` varchar(255) NOT NULL,
  `Body` text NOT NULL,
  `Status` enum('pending','sent','failed') DEFAULT 'pending',
  `Attempts` int(11) DEFAULT 0,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `SentAt` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_verification`
--

CREATE TABLE `email_verification` (
  `VerificationID` int(11) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Code` varchar(6) NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `ExpiresAt` datetime NOT NULL,
  `IsUsed` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_verification`
--

INSERT INTO `email_verification` (`VerificationID`, `Email`, `Code`, `CreatedAt`, `ExpiresAt`, `IsUsed`) VALUES
(2, 'jemsalvacion28@gmail.com', '651097', '2025-10-25 07:40:42', '2025-10-25 09:55:42', 1),
(3, 'leitorres030@gmail.com', '233343', '2025-10-25 07:43:16', '2025-10-25 09:58:16', 1),
(6, 'leanie@gmail.com', '372584', '2025-10-25 07:55:50', '2025-10-25 10:10:50', 0);

-- --------------------------------------------------------

--
-- Table structure for table `menu`
--

CREATE TABLE `menu` (
  `MenuID` int(11) NOT NULL,
  `DishName` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `MenuPrice` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu`
--

INSERT INTO `menu` (`MenuID`, `DishName`, `Description`, `MenuPrice`) VALUES
(1, 'Roast Chicken', 'Herb-roasted chicken with vegetables', 250.00),
(2, 'Beef Caldereta', 'Traditional Filipino beef stew', 300.00),
(3, 'Pancit Canton', 'Stir-fried noodles with vegetables and meat', 180.00),
(4, 'Lumpiang Shanghai', 'Filipino spring rolls (20pcs)', 200.00),
(5, 'Mixed Vegetables', 'Fresh seasonal vegetables', 150.00),
(6, 'Lechon Kawali', 'Crispy pork belly', 320.00),
(7, 'Grilled Fish', 'Fresh fish marinated and grilled', 280.00),
(8, 'Fruit Platter', 'Assorted fresh seasonal fruits', 220.00);

-- --------------------------------------------------------

--
-- Table structure for table `notification`
--

CREATE TABLE `notification` (
  `NotificationID` int(11) NOT NULL,
  `ClientID` int(11) NOT NULL,
  `BookingID` int(11) DEFAULT NULL,
  `AdminID` int(11) DEFAULT NULL,
  `Message` text DEFAULT NULL,
  `SentDate` datetime DEFAULT current_timestamp(),
  `Type` enum('Confirmation','Reminder','Change','QuotationApproved') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `package`
--

CREATE TABLE `package` (
  `PackageID` int(11) NOT NULL,
  `PackageName` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `PackPrice` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `package`
--

INSERT INTO `package` (`PackageID`, `PackageName`, `Description`, `PackPrice`) VALUES
(1, 'Basic Celebration Package', 'Perfect for small gatherings (30-50 pax): 3 main dishes, 1 dessert, drinks', 12000.00),
(2, 'Premium Wedding Package', 'Complete wedding catering (100-150 pax): 5 main dishes, 2 desserts, drinks, setup', 45000.00),
(3, 'Corporate Event Package', 'Professional business events (50-80 pax): 4 main dishes, snacks, drinks', 25000.00),
(4, 'Debut Package', 'Elegant debut celebration (80-100 pax): 4 main dishes, cake, drinks, decorations', 35000.00),
(5, 'Bento Meal Package', 'Individual boxed meals (per person): 1 main, 1 side, rice, dessert', 150.00),
(6, 'Budget-Friendly Package', 'Affordable for any occasion (30-50 pax): 2 main dishes, drinks', 8000.00);

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `PaymentID` int(11) NOT NULL,
  `BookingID` int(11) DEFAULT NULL,
  `AdminID` int(11) DEFAULT NULL,
  `AmountPaid` decimal(10,2) DEFAULT NULL,
  `PaymentDate` date DEFAULT NULL,
  `PaymentMethod` enum('Cash','GCash') DEFAULT NULL,
  `Status` enum('Pending','Paid','Refunded') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quotation`
--

CREATE TABLE `quotation` (
  `QuotationID` int(11) NOT NULL,
  `BookingID` int(11) DEFAULT NULL,
  `AdminID` int(11) DEFAULT NULL,
  `SpecialRequest` text DEFAULT NULL,
  `EstimatedPrice` decimal(10,2) DEFAULT NULL,
  `SpecialRequestPrice` decimal(10,2) DEFAULT 0,
  `SpecialRequestItems` json DEFAULT NULL,
  `Status` enum('Pending','Approved','Rejected') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `salesreport`
--

CREATE TABLE `salesreport` (
  `ReportID` int(11) NOT NULL,
  `AdminID` int(11) DEFAULT NULL,
  `DateGenerated` date DEFAULT NULL,
  `TotalSales` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `SessionID` varchar(255) NOT NULL,
  `UserID` int(11) NOT NULL,
  `UserType` enum('admin','client') NOT NULL,
  `IPAddress` varchar(45) DEFAULT NULL,
  `UserAgent` varchar(255) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `ExpiresAt` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`LogID`),
  ADD KEY `idx_user` (`UserID`,`UserType`),
  ADD KEY `idx_created` (`CreatedAt`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`AdminID`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Indexes for table `agreement`
--
ALTER TABLE `agreement`
  ADD PRIMARY KEY (`AgreementID`),
  ADD KEY `BookingID` (`BookingID`),
  ADD KEY `AdminID` (`AdminID`);

--
-- Indexes for table `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`BookingID`),
  ADD KEY `ClientID` (`ClientID`);

--
-- Indexes for table `booking_menu`
--
ALTER TABLE `booking_menu`
  ADD PRIMARY KEY (`BookingID`,`MenuID`),
  ADD KEY `MenuID` (`MenuID`);

--
-- Indexes for table `booking_package`
--
ALTER TABLE `booking_package`
  ADD PRIMARY KEY (`BookingID`,`PackageID`),
  ADD KEY `PackageID` (`PackageID`);

--
-- Indexes for table `client`
--
ALTER TABLE `client`
  ADD PRIMARY KEY (`ClientID`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Indexes for table `email_queue`
--
ALTER TABLE `email_queue`
  ADD PRIMARY KEY (`EmailID`),
  ADD KEY `idx_status` (`Status`);

--
-- Indexes for table `email_verification`
--
ALTER TABLE `email_verification`
  ADD PRIMARY KEY (`VerificationID`),
  ADD KEY `idx_email` (`Email`),
  ADD KEY `idx_code` (`Code`),
  ADD KEY `idx_expires` (`ExpiresAt`);

--
-- Indexes for table `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`MenuID`);

--
-- Indexes for table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`NotificationID`,`ClientID`),
  ADD KEY `ClientID` (`ClientID`),
  ADD KEY `BookingID` (`BookingID`),
  ADD KEY `AdminID` (`AdminID`);

--
-- Indexes for table `package`
--
ALTER TABLE `package`
  ADD PRIMARY KEY (`PackageID`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`PaymentID`),
  ADD KEY `BookingID` (`BookingID`),
  ADD KEY `AdminID` (`AdminID`);

--
-- Indexes for table `quotation`
--
ALTER TABLE `quotation`
  ADD PRIMARY KEY (`QuotationID`),
  ADD KEY `BookingID` (`BookingID`),
  ADD KEY `AdminID` (`AdminID`);

--
-- Indexes for table `salesreport`
--
ALTER TABLE `salesreport`
  ADD PRIMARY KEY (`ReportID`),
  ADD KEY `AdminID` (`AdminID`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`SessionID`),
  ADD KEY `idx_user` (`UserID`,`UserType`),
  ADD KEY `idx_expires` (`ExpiresAt`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `LogID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `AdminID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `agreement`
--
ALTER TABLE `agreement`
  MODIFY `AgreementID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `BookingID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `client`
--
ALTER TABLE `client`
  MODIFY `ClientID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `email_queue`
--
ALTER TABLE `email_queue`
  MODIFY `EmailID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_verification`
--
ALTER TABLE `email_verification`
  MODIFY `VerificationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `menu`
--
ALTER TABLE `menu`
  MODIFY `MenuID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `notification`
--
ALTER TABLE `notification`
  MODIFY `NotificationID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `package`
--
ALTER TABLE `package`
  MODIFY `PackageID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `PaymentID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quotation`
--
ALTER TABLE `quotation`
  MODIFY `QuotationID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `salesreport`
--
ALTER TABLE `salesreport`
  MODIFY `ReportID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `agreement`
--
ALTER TABLE `agreement`
  ADD CONSTRAINT `agreement_ibfk_1` FOREIGN KEY (`BookingID`) REFERENCES `booking` (`BookingID`),
  ADD CONSTRAINT `agreement_ibfk_2` FOREIGN KEY (`AdminID`) REFERENCES `admin` (`AdminID`);

--
-- Constraints for table `booking`
--
ALTER TABLE `booking`
  ADD CONSTRAINT `booking_ibfk_1` FOREIGN KEY (`ClientID`) REFERENCES `client` (`ClientID`);

--
-- Constraints for table `booking_menu`
--
ALTER TABLE `booking_menu`
  ADD CONSTRAINT `booking_menu_ibfk_1` FOREIGN KEY (`BookingID`) REFERENCES `booking` (`BookingID`),
  ADD CONSTRAINT `booking_menu_ibfk_2` FOREIGN KEY (`MenuID`) REFERENCES `menu` (`MenuID`);

--
-- Constraints for table `booking_package`
--
ALTER TABLE `booking_package`
  ADD CONSTRAINT `booking_package_ibfk_1` FOREIGN KEY (`BookingID`) REFERENCES `booking` (`BookingID`),
  ADD CONSTRAINT `booking_package_ibfk_2` FOREIGN KEY (`PackageID`) REFERENCES `package` (`PackageID`);

--
-- Constraints for table `notification`
--
ALTER TABLE `notification`
  ADD CONSTRAINT `notification_ibfk_1` FOREIGN KEY (`ClientID`) REFERENCES `client` (`ClientID`),
  ADD CONSTRAINT `notification_ibfk_2` FOREIGN KEY (`BookingID`) REFERENCES `booking` (`BookingID`),
  ADD CONSTRAINT `notification_ibfk_3` FOREIGN KEY (`AdminID`) REFERENCES `admin` (`AdminID`);

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`BookingID`) REFERENCES `booking` (`BookingID`),
  ADD CONSTRAINT `payment_ibfk_2` FOREIGN KEY (`AdminID`) REFERENCES `admin` (`AdminID`);

--
-- Constraints for table `quotation`
--
ALTER TABLE `quotation`
  ADD CONSTRAINT `quotation_ibfk_1` FOREIGN KEY (`BookingID`) REFERENCES `booking` (`BookingID`),
  ADD CONSTRAINT `quotation_ibfk_2` FOREIGN KEY (`AdminID`) REFERENCES `admin` (`AdminID`);

--
-- Constraints for table `salesreport`
--
ALTER TABLE `salesreport`
  ADD CONSTRAINT `salesreport_ibfk_1` FOREIGN KEY (`AdminID`) REFERENCES `admin` (`AdminID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
