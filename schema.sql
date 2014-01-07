SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT=0;
START TRANSACTION;

-- --------------------------------------------------------

--
-- Table structure for table `refiler_dirs`
--

CREATE TABLE IF NOT EXISTS `refiler_dirs` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `path` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `path` (`path`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `refiler_files`
--

CREATE TABLE IF NOT EXISTS `refiler_files` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `dir_id` mediumint(8) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `caption` text NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `size` int(10) unsigned NOT NULL,
  `width` smallint(5) unsigned NOT NULL,
  `height` smallint(5) unsigned NOT NULL,
  `thumb_type` varchar(255) NOT NULL,
  `dir` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `dir_id` (`dir_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `refiler_file_tag_map`
--

CREATE TABLE IF NOT EXISTS `refiler_file_tag_map` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `file_id` mediumint(8) unsigned NOT NULL,
  `tag_id` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `file_tag` (`file_id`,`tag_id`),
  KEY `file_id` (`file_id`),
  KEY `tag_id` (`tag_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `refiler_tags`
--

CREATE TABLE IF NOT EXISTS `refiler_tags` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `caption` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `url` (`url`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `refiler_tag_map`
--

CREATE TABLE IF NOT EXISTS `refiler_tag_map` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` mediumint(8) unsigned NOT NULL,
  `child_id` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `parent_child` (`parent_id`,`child_id`),
  KEY `parent_id` (`parent_id`),
  KEY `child_id` (`child_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `refiler_files`
--
ALTER TABLE `refiler_files`
  ADD CONSTRAINT `refiler_files_ibfk_1` FOREIGN KEY (`dir_id`) REFERENCES `refiler_dirs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `refiler_file_tag_map`
--
ALTER TABLE `refiler_file_tag_map`
  ADD CONSTRAINT `refiler_file_tag_map_ibfk_1` FOREIGN KEY (`file_id`) REFERENCES `refiler_files` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `refiler_file_tag_map_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `refiler_tags` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `refiler_tag_map`
--
ALTER TABLE `refiler_tag_map`
  ADD CONSTRAINT `refiler_tag_map_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `refiler_tags` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `refiler_tag_map_ibfk_2` FOREIGN KEY (`child_id`) REFERENCES `refiler_tags` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;
