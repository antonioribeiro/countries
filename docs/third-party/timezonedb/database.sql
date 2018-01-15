DROP TABLE IF EXISTS `zone`;
CREATE TABLE `zone` (
`zone_id` INT(10) NOT NULL AUTO_INCREMENT,
`country_code` CHAR(2) NOT NULL,
`zone_name` VARCHAR(35) NOT NULL,
PRIMARY KEY (`zone_id`),
INDEX `idx_country_code` (`country_code`),
INDEX `idx_zone_name` (`zone_name`)
) COLLATE='utf8_bin' ENGINE=MyISAM;
LOAD DATA LOCAL INFILE 'zone.csv' INTO TABLE `zone` FIELDS TERMINATED BY ',' ENCLOSED BY '"' LINES TERMINATED BY '\n';

DROP TABLE IF EXISTS `timezone`;
CREATE TABLE `timezone` (
`zone_id` INT(10) NOT NULL,
`abbreviation` VARCHAR(6) NOT NULL,
`time_start` DECIMAL(11,0) NOT NULL,
`gmt_offset` INT NOT NULL,
`dst` CHAR(1) NOT NULL,
INDEX `idx_zone_id` (`zone_id`),
INDEX `idx_time_start` (`time_start`)
) COLLATE='utf8_bin' ENGINE=MyISAM;
LOAD DATA LOCAL INFILE 'timezone.csv' INTO TABLE `timezone` FIELDS TERMINATED BY ',' ENCLOSED BY '"' LINES TERMINATED BY '\n';

DROP TABLE IF EXISTS `country`;
CREATE TABLE `country` (
`country_code` CHAR(2) NULL,
`country_name` VARCHAR(45) NULL,
INDEX `idx_country_code` (`country_code`)
) COLLATE='utf8_bin' ENGINE=MyISAM;
LOAD DATA LOCAL INFILE 'country.csv' INTO TABLE `country` FIELDS TERMINATED BY ',' ENCLOSED BY '"' LINES TERMINATED BY '\n';