CREATE TABLE `submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` VARCHAR(36) NOT NULL,
  `userId` INT(11) NOT NULL,
  `title` TEXT,
  `url` TEXT,
  `post` TEXT,
  `dateAuthored` DATETIME,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid_UNIQUE` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
