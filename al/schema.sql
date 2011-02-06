CREATE TABLE `category` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id` INT(10) UNSIGNED,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`)
);