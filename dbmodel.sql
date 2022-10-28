
-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- Rauha implementation : © Timothée Pecatte <tim.pecatte@gmail.com>, Emmanuel ??
--
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

-- dbmodel.sql


CREATE TABLE IF NOT EXISTS `biomes` (
  `biome_id` int(5) NOT NULL AUTO_INCREMENT,
  `biome_state` int(10) DEFAULT 0 COMMENT '0=out, 1=deck, 2=coord', 
  `biome_location` varchar(32) NOT NULL COMMENT 'deck1, deck2, player1...',
  `x` int(1) NULL COMMENT '0, 1 or 2',
  `y` int(1) NULL  COMMENT '0, 1 or 2',
  `player_id` int(10) NULL,
  `extra_datas` JSON NULL COMMENT 'not used for now',
  `data_id` int(5) NOT NULL COMMENT 'used to retrieve static data',
  PRIMARY KEY (`biome_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `gods` (
  `god_id` int(1)  NOT NULL,
  `player_id` int(10) NULL,
  `used` int(1) DEFAULT 0 COMMENT '0=not used, 1=used', 
  `extra_datas` JSON NULL COMMENT 'not used for now',
  PRIMARY KEY (`god_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--ADD a crystal count to each player
ALTER TABLE `player` ADD `player_crystal` INT(3) DEFAULT 0 ;

-- CORE TABLES --
CREATE TABLE IF NOT EXISTS `global_variables` (
  `name` varchar(255) NOT NULL,
  `value` JSON,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `user_preferences` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `player_id` int(10) NOT NULL,
  `pref_id` int(10) NOT NULL,
  `pref_value` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `move_id` int(10) NOT NULL,
  `table` varchar(32) NOT NULL,
  `primary` varchar(32) NOT NULL,
  `type` varchar(32) NOT NULL,
  `affected` JSON,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
ALTER TABLE `gamelog` ADD `cancel` TINYINT(1) NOT NULL DEFAULT 0;
