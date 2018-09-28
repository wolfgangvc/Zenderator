CREATE TABLE `boards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` VARCHAR(36) NOT NULL,
  `name` VARCHAR(128),
  `dateCreated` DATETIME,
  `postCount` INT(11),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid_UNIQUE` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
