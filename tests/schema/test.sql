DROP TABLE IF EXISTS `NestedSet`;

CREATE TABLE `NestedSet` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `lft` INT(10) UNSIGNED NOT NULL,
  `rgt` INT(10) UNSIGNED NOT NULL,
  `level` SMALLINT(5) UNSIGNED NOT NULL,
  `name` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `lft` (`lft`),
  KEY `rgt` (`rgt`),
  KEY `level` (`level`)
);

DROP TABLE IF EXISTS `NestedSetWithManyRoots`;

CREATE TABLE `NestedSetWithManyRoots` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `root` INT(10) UNSIGNED DEFAULT NULL,
  `lft` INT(10) UNSIGNED NOT NULL,
  `rgt` INT(10) UNSIGNED NOT NULL,
  `level` SMALLINT(5) UNSIGNED NOT NULL,
  `name` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `root` (`root`),
  KEY `lft` (`lft`),
  KEY `rgt` (`rgt`),
  KEY `level` (`level`)
);