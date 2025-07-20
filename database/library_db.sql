-- Library Management System Database Schema
-- Fixed version for Final Year Project
-- Database: library_db

-- Create database
CREATE DATABASE IF NOT EXISTS `library_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `library_db`;

-- Disable foreign key checks during creation
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- Table structure for users
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','librarian','student','staff') NOT NULL DEFAULT 'student',
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for categories
-- --------------------------------------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for books
-- --------------------------------------------------------
DROP TABLE IF EXISTS `books`;
CREATE TABLE `books` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `available_quantity` int(11) NOT NULL DEFAULT 1,
  `publication_year` year(4) DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `book_cover` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `books_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for borrowings
-- --------------------------------------------------------
DROP TABLE IF EXISTS `borrowings`;
CREATE TABLE `borrowings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `borrow_date` date NOT NULL,
  `due_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `status` enum('borrowed','returned','overdue') NOT NULL DEFAULT 'borrowed',
  `fine_amount` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `book_id` (`book_id`),
  CONSTRAINT `borrowings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `borrowings_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for fines
-- --------------------------------------------------------
DROP TABLE IF EXISTS `fines`;
CREATE TABLE `fines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `borrowing_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `status` enum('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `borrowing_id` (`borrowing_id`),
  CONSTRAINT `fines_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fines_ibfk_2` FOREIGN KEY (`borrowing_id`) REFERENCES `borrowings` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for settings
-- --------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_name` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_name` (`setting_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- Insert sample data
-- --------------------------------------------------------

-- Insert default users (password: admin123)
INSERT INTO `users` (`username`, `email`, `password`, `role`, `full_name`, `phone`, `address`) VALUES
('admin', 'admin@library.com', 'admin123', 'admin', 'System Administrator', '03-1234-5678', 'Library Administration Office'),
('librarian', 'librarian@library.com', 'admin123', 'librarian', 'Head Librarian', '03-1234-5679', 'Library Staff Room'),
('student001', 'student001@university.edu', 'admin123', 'student', 'John Doe', '01-2345-6789', '123 Student Housing, University Campus'),
('staff001', 'staff001@university.edu', 'admin123', 'staff', 'Jane Smith', '01-2345-6788', '456 Faculty Building, University Campus');

-- Insert categories
INSERT INTO `categories` (`name`, `description`) VALUES
('Computer Science', 'Books related to programming, algorithms, and computer technology'),
('Mathematics', 'Mathematics textbooks and reference materials'),
('Physics', 'Physics textbooks and scientific literature'),
('Engineering', 'Engineering textbooks and technical manuals'),
('Literature', 'Classic and modern literature, novels, and poetry'),
('History', 'Historical books and reference materials'),
('Business', 'Business management and economics books'),
('Science Fiction', 'Science fiction novels and stories'),
('Biography', 'Biographies and autobiographies'),
('Reference', 'Dictionaries, encyclopedias, and reference materials');

-- Insert sample books
INSERT INTO `books` (`title`, `author`, `isbn`, `category_id`, `quantity`, `available_quantity`, `publication_year`, `publisher`, `description`) VALUES
('Introduction to Algorithms', 'Thomas H. Cormen', '9780262033848', 1, 3, 3, 2009, 'MIT Press', 'Comprehensive introduction to algorithms and data structures'),
('Clean Code', 'Robert C. Martin', '9780132350884', 1, 2, 2, 2008, 'Prentice Hall', 'A handbook of agile software craftsmanship'),
('Design Patterns', 'Gang of Four', '9780201633610', 1, 2, 1, 1994, 'Addison-Wesley', 'Elements of reusable object-oriented software'),
('Calculus Early Transcendentals', 'James Stewart', '9781285741550', 2, 4, 4, 2015, 'Cengage Learning', 'Comprehensive calculus textbook'),
('Linear Algebra and Its Applications', 'David C. Lay', '9780321982384', 2, 3, 3, 2015, 'Pearson', 'Linear algebra for engineering and science'),
('University Physics', 'Hugh D. Young', '9780133969290', 3, 5, 5, 2015, 'Pearson', 'Comprehensive university physics textbook'),
('Fundamentals of Electric Circuits', 'Charles K. Alexander', '9780073380575', 4, 3, 2, 2016, 'McGraw-Hill', 'Electric circuits for engineering students'),
('Pride and Prejudice', 'Jane Austen', '9780141439518', 5, 2, 2, 1813, 'Penguin Classics', 'Classic English literature'),
('1984', 'George Orwell', '9780451524935', 5, 3, 1, 1949, 'Signet Classics', 'Dystopian social science fiction novel'),
('A Brief History of Time', 'Stephen Hawking', '9780553380163', 3, 2, 2, 1988, 'Bantam Books', 'Popular science book on cosmology');

-- Insert system settings
INSERT INTO `settings` (`setting_name`, `setting_value`) VALUES
('maintenance_mode', 'false'),
('registration_enabled', 'true'),
('email_notifications', 'true'),
('auto_calculate_fines', 'true'),
('backup_enabled', 'true'),
('debug_mode', 'true'),
('max_books_per_user', '5'),
('default_borrow_days', '14'),
('fine_per_day', '2.00'),
('grace_period_days', '1');