CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` VARCHAR(36) NOT NULL,
  `email` varchar(320) NOT NULL,
  `password` TEXT,
  `validated` ENUM('Yes', 'No') DEFAULT 'No',
  `validationKey` TEXT,
  `accountType` ENUM('user','admin') DEFAULT 'user',
  `dateRegistered` DATETIME NOT NULL,
  `dateConfirmed` DATETIME,
  `dateLastLogin` DATETIME,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid_UNIQUE` (`uuid`),
  UNIQUE KEY `email_UNIQUE` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
