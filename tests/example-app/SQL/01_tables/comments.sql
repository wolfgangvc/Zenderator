CREATE TABLE `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` VARCHAR(36) NOT NULL,
  `userId` INT(11) NOT NULL,
  `submissionId` INT(11) NOT NULL,
  `parentCommentId` INT(11),
  `post` TEXT,
  `dateAuthored` DATETIME,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid_UNIQUE` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
