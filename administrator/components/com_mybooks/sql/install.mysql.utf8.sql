-- -----------------------------------------------------
-- Table `#__mybooks_book`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `#__mybooks_book`;
CREATE TABLE `#__mybooks_book` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(225) NOT NULL ,
  `alias` VARCHAR(255) NOT NULL ,
  `intro_text` MEDIUMTEXT NULL ,
  `full_text` MEDIUMTEXT NULL ,
  `published` TINYINT NOT NULL DEFAULT 0 ,
  `catid` INT UNSIGNED NOT NULL ,
  `checked_out` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `checked_out_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' ,
  `asset_id` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `access` TINYINT NOT NULL DEFAULT 0 ,
  `params` TEXT NOT NULL ,
  `metakey` TEXT NOT NULL ,
  `metadesc` TEXT NOT NULL ,
  `metadata` TEXT NOT NULL ,
  `xreference` VARCHAR(50) NOT NULL ,
  `hits` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `publish_up` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' ,
  `publish_down` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' ,
  `created` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' ,
  `created_by` INT UNSIGNED NOT NULL ,
  `modified` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' ,
  `modified_by` INT UNSIGNED NOT NULL ,
  `language` CHAR(7) NOT NULL,
  PRIMARY KEY (`id`) ,
  INDEX `idx_access` (`access` ASC) ,
  INDEX `idx_created_by` (`created_by` ASC) ,
  INDEX `idx_published` (`published` ASC) ,
  INDEX `idx_check_out` (`checked_out` ASC) )
ENGINE = MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;


-- -----------------------------------------------------
-- Table `#__mybooks_book_cat_map`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `#__mybooks_book_cat_map
CREATE TABLE `#__mybooks_book_cat_map` (
  `book_id` INT UNSIGNED NOT NULL ,
  `cat_id` INT UNSIGNED NOT NULL ,
  `ordering` INT NULL NOT NULL ,
  INDEX `idx_book_id` (`book_id` ASC) ,
  INDEX `idx_cat_id` (`cat_id` ASC) )
ENGINE = MyISAM DEFAULT CHARSET=utf8;
