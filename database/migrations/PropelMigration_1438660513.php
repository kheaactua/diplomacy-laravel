<?php

/**
 * Data object containing the SQL and PHP code to migrate the database
 * up to version 1438660513.
 * Generated on 2015-08-03 22:55:13 by vagrant
 */
class PropelMigration_1438660513
{
    public $comment = '';

    public function preUp($manager)
    {
        // add the pre-migration code here
    }

    public function postUp($manager)
    {
        // add the post-migration code here
    }

    public function preDown($manager)
    {
        // add the pre-migration code here
    }

    public function postDown($manager)
    {
        // add the post-migration code here
    }

    /**
     * Get the SQL statements for the Up migration
     *
     * @return array list of the SQL strings to execute for the Up migration
     *               the keys being the datasources
     */
    public function getUpSQL()
    {
        return array (
  'mysql' => '
# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE `user`
(
    `user_id` INTEGER NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL,
    `handle` VARCHAR(100) NOT NULL,
    `password` VARCHAR(100) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `first_name` VARCHAR(40),
    `last_name` VARCHAR(60),
    `perms` TEXT COMMENT \'Role tags.  Not to be confused with membership status.  Roles will primarily be used for website administrators..  e.g. admin|storemng|editor|volunteer\',
    `gender` TINYINT DEFAULT 0,
    `birthday` DATE,
    `created_on` DATETIME,
    `updated_at` DATETIME,
    PRIMARY KEY (`user_id`)
) ENGINE=InnoDB;

CREATE TABLE `game`
(
    `game_id` INTEGER NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `start_year` INTEGER NOT NULL,
    `start_season` TINYINT DEFAULT 0 COMMENT \'This is dumb, it should always be spring\',
    PRIMARY KEY (`game_id`)
) ENGINE=InnoDB;

CREATE TABLE `empire`
(
    `game_id` INTEGER NOT NULL,
    `empire_id` INTEGER NOT NULL AUTO_INCREMENT,
    `abbr` VARCHAR(10) NOT NULL COMMENT \'Abbreviated name.  Would often be used as an ID on paper, or in the spreadsheet\',
    `name_official` VARCHAR(100) NOT NULL,
    `name_long` VARCHAR(100) NOT NULL,
    `name_short` VARCHAR(10) NOT NULL,
    PRIMARY KEY (`empire_id`),
    INDEX `empire_fi_d5280a` (`game_id`),
    CONSTRAINT `empire_fk_d5280a`
        FOREIGN KEY (`game_id`)
        REFERENCES `game` (`game_id`)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `game_match`
(
    `game_id` INTEGER,
    `match_id` INTEGER NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL COMMENT \'Name of the game instance\',
    `created_on` DATETIME,
    `updated_at` DATETIME,
    `current_turn_id` INTEGER COMMENT \'Pointer to the current turn\',
    `next_turn_id` INTEGER COMMENT \'Pointer to the next turn\',
    PRIMARY KEY (`match_id`),
    INDEX `game_match_fi_d5280a` (`game_id`),
    INDEX `game_match_fi_9abc6a` (`current_turn_id`),
    INDEX `game_match_fi_ff930e` (`next_turn_id`),
    CONSTRAINT `game_match_fk_d5280a`
        FOREIGN KEY (`game_id`)
        REFERENCES `game` (`game_id`)
        ON DELETE CASCADE,
    CONSTRAINT `game_match_fk_9abc6a`
        FOREIGN KEY (`current_turn_id`)
        REFERENCES `turn` (`turn_id`)
        ON DELETE CASCADE,
    CONSTRAINT `game_match_fk_ff930e`
        FOREIGN KEY (`next_turn_id`)
        REFERENCES `turn` (`turn_id`)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `turn`
(
    `match_id` INTEGER,
    `turn_id` INTEGER NOT NULL AUTO_INCREMENT,
    `step` INTEGER DEFAULT 0 NOT NULL,
    `status` TINYINT DEFAULT 0,
    `created_on` DATETIME,
    `updated_at` DATETIME,
    `transcript` TEXT COMMENT \'Full transcript of the orders that are executed in this turn\',
    PRIMARY KEY (`turn_id`),
    INDEX `turn_fi_0fe8b6` (`match_id`),
    CONSTRAINT `turn_fk_0fe8b6`
        FOREIGN KEY (`match_id`)
        REFERENCES `game_match` (`match_id`)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `empire_order`
(
    `order_id` INTEGER NOT NULL AUTO_INCREMENT,
    `turn_id` INTEGER NOT NULL,
    `empire_id` INTEGER NOT NULL,
    `unit_type` TINYINT COMMENT \'Occupying unit.  This column is being kept around for novelty/compliance with the actual diplomacy game.  It\\\'s not used in anyway.  If anything it\\\'s just causing extra DB queries.\',
    `command` VARCHAR(100) NOT NULL COMMENT \'Full order/command text.\',
    `status` TINYINT DEFAULT 0,
    `transcript` TEXT COMMENT \'Transcript of this order\',
    `source_id` INTEGER COMMENT \'Source territory of order\',
    `descendant_class` VARCHAR(100),
    PRIMARY KEY (`order_id`),
    INDEX `empire_order_fi_bd52fc` (`turn_id`),
    INDEX `empire_order_fi_8e6a5e` (`empire_id`),
    INDEX `empire_order_fi_aff488` (`source_id`),
    CONSTRAINT `empire_order_fk_bd52fc`
        FOREIGN KEY (`turn_id`)
        REFERENCES `turn` (`turn_id`)
        ON DELETE CASCADE,
    CONSTRAINT `empire_order_fk_8e6a5e`
        FOREIGN KEY (`empire_id`)
        REFERENCES `empire` (`empire_id`)
        ON DELETE CASCADE,
    CONSTRAINT `empire_order_fk_aff488`
        FOREIGN KEY (`source_id`)
        REFERENCES `match_state` (`state_id`)
        ON DELETE CASCADE
) ENGINE=InnoDB COMMENT=\'Saves the orders of each turn.  Required because there is a break in the process between initial orders and required retreats.\';

CREATE TABLE `order_move`
(
    `dest_id` INTEGER COMMENT \'Destination of order\',
    `order_id` INTEGER NOT NULL,
    `turn_id` INTEGER NOT NULL,
    `empire_id` INTEGER NOT NULL,
    `unit_type` TINYINT COMMENT \'Occupying unit.  This column is being kept around for novelty/compliance with the actual diplomacy game.  It\\\'s not used in anyway.  If anything it\\\'s just causing extra DB queries.\',
    `command` VARCHAR(100) NOT NULL COMMENT \'Full order/command text.\',
    `status` TINYINT DEFAULT 0,
    `transcript` TEXT COMMENT \'Transcript of this order\',
    `source_id` INTEGER COMMENT \'Source territory of order\',
    PRIMARY KEY (`order_id`),
    INDEX `order_move_fi_5d9a0e` (`dest_id`),
    INDEX `order_move_i_d48d7d` (`turn_id`),
    INDEX `order_move_i_cf62ab` (`empire_id`),
    INDEX `order_move_i_808e01` (`source_id`),
    CONSTRAINT `order_move_fk_5d9a0e`
        FOREIGN KEY (`dest_id`)
        REFERENCES `match_state` (`state_id`)
        ON DELETE CASCADE,
    CONSTRAINT `order_move_fk_c4a98e`
        FOREIGN KEY (`order_id`)
        REFERENCES `empire_order` (`order_id`)
        ON DELETE CASCADE,
    CONSTRAINT `order_move_fk_bd52fc`
        FOREIGN KEY (`turn_id`)
        REFERENCES `turn` (`turn_id`)
        ON DELETE CASCADE,
    CONSTRAINT `order_move_fk_8e6a5e`
        FOREIGN KEY (`empire_id`)
        REFERENCES `empire` (`empire_id`)
        ON DELETE CASCADE,
    CONSTRAINT `order_move_fk_aff488`
        FOREIGN KEY (`source_id`)
        REFERENCES `match_state` (`state_id`)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `order_retreat`
(
    `dest_id` INTEGER COMMENT \'Destination of order\',
    `order_id` INTEGER NOT NULL,
    `turn_id` INTEGER NOT NULL,
    `empire_id` INTEGER NOT NULL,
    `unit_type` TINYINT COMMENT \'Occupying unit.  This column is being kept around for novelty/compliance with the actual diplomacy game.  It\\\'s not used in anyway.  If anything it\\\'s just causing extra DB queries.\',
    `command` VARCHAR(100) NOT NULL COMMENT \'Full order/command text.\',
    `status` TINYINT DEFAULT 0,
    `transcript` TEXT COMMENT \'Transcript of this order\',
    `source_id` INTEGER COMMENT \'Source territory of order\',
    PRIMARY KEY (`order_id`),
    INDEX `order_retreat_fi_5d9a0e` (`dest_id`),
    INDEX `order_retreat_i_d48d7d` (`turn_id`),
    INDEX `order_retreat_i_cf62ab` (`empire_id`),
    INDEX `order_retreat_i_808e01` (`source_id`),
    CONSTRAINT `order_retreat_fk_5d9a0e`
        FOREIGN KEY (`dest_id`)
        REFERENCES `match_state` (`state_id`)
        ON DELETE CASCADE,
    CONSTRAINT `order_retreat_fk_c4a98e`
        FOREIGN KEY (`order_id`)
        REFERENCES `empire_order` (`order_id`)
        ON DELETE CASCADE,
    CONSTRAINT `order_retreat_fk_bd52fc`
        FOREIGN KEY (`turn_id`)
        REFERENCES `turn` (`turn_id`)
        ON DELETE CASCADE,
    CONSTRAINT `order_retreat_fk_8e6a5e`
        FOREIGN KEY (`empire_id`)
        REFERENCES `empire` (`empire_id`)
        ON DELETE CASCADE,
    CONSTRAINT `order_retreat_fk_aff488`
        FOREIGN KEY (`source_id`)
        REFERENCES `match_state` (`state_id`)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `order_support`
(
    `ally_state_id` INTEGER COMMENT \'State that you are supporting\',
    `dest_id` INTEGER COMMENT \'Source destination of order\',
    `order_id` INTEGER NOT NULL,
    `turn_id` INTEGER NOT NULL,
    `empire_id` INTEGER NOT NULL,
    `unit_type` TINYINT COMMENT \'Occupying unit.  This column is being kept around for novelty/compliance with the actual diplomacy game.  It\\\'s not used in anyway.  If anything it\\\'s just causing extra DB queries.\',
    `command` VARCHAR(100) NOT NULL COMMENT \'Full order/command text.\',
    `status` TINYINT DEFAULT 0,
    `transcript` TEXT COMMENT \'Transcript of this order\',
    `source_id` INTEGER COMMENT \'Source territory of order\',
    PRIMARY KEY (`order_id`),
    INDEX `order_support_fi_276f64` (`ally_state_id`),
    INDEX `order_support_fi_5d9a0e` (`dest_id`),
    INDEX `order_support_i_d48d7d` (`turn_id`),
    INDEX `order_support_i_cf62ab` (`empire_id`),
    INDEX `order_support_i_808e01` (`source_id`),
    CONSTRAINT `order_support_fk_276f64`
        FOREIGN KEY (`ally_state_id`)
        REFERENCES `match_state` (`state_id`)
        ON DELETE CASCADE,
    CONSTRAINT `order_support_fk_5d9a0e`
        FOREIGN KEY (`dest_id`)
        REFERENCES `match_state` (`state_id`)
        ON DELETE CASCADE,
    CONSTRAINT `order_support_fk_c4a98e`
        FOREIGN KEY (`order_id`)
        REFERENCES `empire_order` (`order_id`)
        ON DELETE CASCADE,
    CONSTRAINT `order_support_fk_bd52fc`
        FOREIGN KEY (`turn_id`)
        REFERENCES `turn` (`turn_id`)
        ON DELETE CASCADE,
    CONSTRAINT `order_support_fk_8e6a5e`
        FOREIGN KEY (`empire_id`)
        REFERENCES `empire` (`empire_id`)
        ON DELETE CASCADE,
    CONSTRAINT `order_support_fk_aff488`
        FOREIGN KEY (`source_id`)
        REFERENCES `match_state` (`state_id`)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `order_convoy`
(
    `troupe_id` INTEGER COMMENT \'Territory to convoy through\',
    `dest_id` INTEGER COMMENT \'Source destination of order\',
    `order_id` INTEGER NOT NULL,
    `turn_id` INTEGER NOT NULL,
    `empire_id` INTEGER NOT NULL,
    `unit_type` TINYINT COMMENT \'Occupying unit.  This column is being kept around for novelty/compliance with the actual diplomacy game.  It\\\'s not used in anyway.  If anything it\\\'s just causing extra DB queries.\',
    `command` VARCHAR(100) NOT NULL COMMENT \'Full order/command text.\',
    `status` TINYINT DEFAULT 0,
    `transcript` TEXT COMMENT \'Transcript of this order\',
    `source_id` INTEGER COMMENT \'Source territory of order\',
    PRIMARY KEY (`order_id`),
    INDEX `order_convoy_fi_86762d` (`troupe_id`),
    INDEX `order_convoy_fi_5d9a0e` (`dest_id`),
    INDEX `order_convoy_i_d48d7d` (`turn_id`),
    INDEX `order_convoy_i_cf62ab` (`empire_id`),
    INDEX `order_convoy_i_808e01` (`source_id`),
    CONSTRAINT `order_convoy_fk_86762d`
        FOREIGN KEY (`troupe_id`)
        REFERENCES `match_state` (`state_id`)
        ON DELETE CASCADE,
    CONSTRAINT `order_convoy_fk_5d9a0e`
        FOREIGN KEY (`dest_id`)
        REFERENCES `match_state` (`state_id`)
        ON DELETE CASCADE,
    CONSTRAINT `order_convoy_fk_c4a98e`
        FOREIGN KEY (`order_id`)
        REFERENCES `empire_order` (`order_id`)
        ON DELETE CASCADE,
    CONSTRAINT `order_convoy_fk_bd52fc`
        FOREIGN KEY (`turn_id`)
        REFERENCES `turn` (`turn_id`)
        ON DELETE CASCADE,
    CONSTRAINT `order_convoy_fk_8e6a5e`
        FOREIGN KEY (`empire_id`)
        REFERENCES `empire` (`empire_id`)
        ON DELETE CASCADE,
    CONSTRAINT `order_convoy_fk_aff488`
        FOREIGN KEY (`source_id`)
        REFERENCES `match_state` (`state_id`)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `order_hold`
(
    `order_id` INTEGER NOT NULL,
    `turn_id` INTEGER NOT NULL,
    `empire_id` INTEGER NOT NULL,
    `unit_type` TINYINT COMMENT \'Occupying unit.  This column is being kept around for novelty/compliance with the actual diplomacy game.  It\\\'s not used in anyway.  If anything it\\\'s just causing extra DB queries.\',
    `command` VARCHAR(100) NOT NULL COMMENT \'Full order/command text.\',
    `status` TINYINT DEFAULT 0,
    `transcript` TEXT COMMENT \'Transcript of this order\',
    `source_id` INTEGER COMMENT \'Source territory of order\',
    PRIMARY KEY (`order_id`),
    INDEX `order_hold_i_d48d7d` (`turn_id`),
    INDEX `order_hold_i_cf62ab` (`empire_id`),
    INDEX `order_hold_i_808e01` (`source_id`),
    CONSTRAINT `order_hold_fk_c4a98e`
        FOREIGN KEY (`order_id`)
        REFERENCES `empire_order` (`order_id`)
        ON DELETE CASCADE,
    CONSTRAINT `order_hold_fk_bd52fc`
        FOREIGN KEY (`turn_id`)
        REFERENCES `turn` (`turn_id`)
        ON DELETE CASCADE,
    CONSTRAINT `order_hold_fk_8e6a5e`
        FOREIGN KEY (`empire_id`)
        REFERENCES `empire` (`empire_id`)
        ON DELETE CASCADE,
    CONSTRAINT `order_hold_fk_aff488`
        FOREIGN KEY (`source_id`)
        REFERENCES `match_state` (`state_id`)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `order_disband`
(
    `order_id` INTEGER NOT NULL,
    `turn_id` INTEGER NOT NULL,
    `empire_id` INTEGER NOT NULL,
    `unit_type` TINYINT COMMENT \'Occupying unit.  This column is being kept around for novelty/compliance with the actual diplomacy game.  It\\\'s not used in anyway.  If anything it\\\'s just causing extra DB queries.\',
    `command` VARCHAR(100) NOT NULL COMMENT \'Full order/command text.\',
    `status` TINYINT DEFAULT 0,
    `transcript` TEXT COMMENT \'Transcript of this order\',
    `source_id` INTEGER COMMENT \'Source territory of order\',
    PRIMARY KEY (`order_id`),
    INDEX `order_disband_i_d48d7d` (`turn_id`),
    INDEX `order_disband_i_cf62ab` (`empire_id`),
    INDEX `order_disband_i_808e01` (`source_id`),
    CONSTRAINT `order_disband_fk_c4a98e`
        FOREIGN KEY (`order_id`)
        REFERENCES `empire_order` (`order_id`)
        ON DELETE CASCADE,
    CONSTRAINT `order_disband_fk_bd52fc`
        FOREIGN KEY (`turn_id`)
        REFERENCES `turn` (`turn_id`)
        ON DELETE CASCADE,
    CONSTRAINT `order_disband_fk_8e6a5e`
        FOREIGN KEY (`empire_id`)
        REFERENCES `empire` (`empire_id`)
        ON DELETE CASCADE,
    CONSTRAINT `order_disband_fk_aff488`
        FOREIGN KEY (`source_id`)
        REFERENCES `match_state` (`state_id`)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `order_supply`
(
    `order_id` INTEGER NOT NULL,
    `turn_id` INTEGER NOT NULL,
    `empire_id` INTEGER NOT NULL,
    `unit_type` TINYINT COMMENT \'Occupying unit.  This column is being kept around for novelty/compliance with the actual diplomacy game.  It\\\'s not used in anyway.  If anything it\\\'s just causing extra DB queries.\',
    `command` VARCHAR(100) NOT NULL COMMENT \'Full order/command text.\',
    `status` TINYINT DEFAULT 0,
    `transcript` TEXT COMMENT \'Transcript of this order\',
    `source_id` INTEGER COMMENT \'Source territory of order\',
    PRIMARY KEY (`order_id`),
    INDEX `order_supply_i_d48d7d` (`turn_id`),
    INDEX `order_supply_i_cf62ab` (`empire_id`),
    INDEX `order_supply_i_808e01` (`source_id`),
    CONSTRAINT `order_supply_fk_c4a98e`
        FOREIGN KEY (`order_id`)
        REFERENCES `empire_order` (`order_id`)
        ON DELETE CASCADE,
    CONSTRAINT `order_supply_fk_bd52fc`
        FOREIGN KEY (`turn_id`)
        REFERENCES `turn` (`turn_id`)
        ON DELETE CASCADE,
    CONSTRAINT `order_supply_fk_8e6a5e`
        FOREIGN KEY (`empire_id`)
        REFERENCES `empire` (`empire_id`)
        ON DELETE CASCADE,
    CONSTRAINT `order_supply_fk_aff488`
        FOREIGN KEY (`source_id`)
        REFERENCES `match_state` (`state_id`)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `territory_template`
(
    `game_id` INTEGER,
    `territory_id` INTEGER NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `type` TINYINT DEFAULT 0,
    `is_supply` CHAR DEFAULT \'0\' COMMENT \'True (1) if this territory is a supply base\',
    `initial_occupier_id` INTEGER COMMENT \'Initial occupying empire\',
    `initial_unit` TINYINT COMMENT \'Occupying unit.  Only none if occpupier_id is empty.\',
    PRIMARY KEY (`territory_id`),
    INDEX `territory_template_fi_d5280a` (`game_id`),
    INDEX `territory_template_fi_697c53` (`initial_occupier_id`),
    CONSTRAINT `territory_template_fk_d5280a`
        FOREIGN KEY (`game_id`)
        REFERENCES `game` (`game_id`)
        ON DELETE CASCADE,
    CONSTRAINT `territory_template_fk_697c53`
        FOREIGN KEY (`initial_occupier_id`)
        REFERENCES `empire` (`empire_id`)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `match_state`
(
    `state_id` INTEGER NOT NULL AUTO_INCREMENT COMMENT \'Needed for ease referencing\',
    `turn_id` INTEGER NOT NULL,
    `territory_id` INTEGER NOT NULL,
    `occupier_id` INTEGER COMMENT \'Occupying empire\',
    `unit_type` TINYINT DEFAULT 3 COMMENT \'Occupying unit.  Only none if occpupier_id is empty.\',
    PRIMARY KEY (`state_id`),
    INDEX `match_state_fi_bd52fc` (`turn_id`),
    INDEX `match_state_fi_a96e5c` (`territory_id`),
    INDEX `match_state_fi_17886a` (`occupier_id`),
    CONSTRAINT `match_state_fk_bd52fc`
        FOREIGN KEY (`turn_id`)
        REFERENCES `turn` (`turn_id`)
        ON DELETE CASCADE,
    CONSTRAINT `match_state_fk_a96e5c`
        FOREIGN KEY (`territory_id`)
        REFERENCES `territory_template` (`territory_id`)
        ON DELETE CASCADE,
    CONSTRAINT `match_state_fk_17886a`
        FOREIGN KEY (`occupier_id`)
        REFERENCES `empire` (`empire_id`)
        ON DELETE CASCADE
) ENGINE=InnoDB COMMENT=\'Contains the match state for every turn\';

CREATE TABLE `territory_map`
(
    `territory_a_id` INTEGER NOT NULL,
    `territory_b_id` INTEGER NOT NULL,
    PRIMARY KEY (`territory_a_id`,`territory_b_id`),
    INDEX `territory_map_fi_129006` (`territory_b_id`),
    CONSTRAINT `territory_map_fk_4c4f94`
        FOREIGN KEY (`territory_a_id`)
        REFERENCES `territory_template` (`territory_id`)
        ON DELETE CASCADE,
    CONSTRAINT `territory_map_fk_129006`
        FOREIGN KEY (`territory_b_id`)
        REFERENCES `territory_template` (`territory_id`)
        ON DELETE CASCADE
) ENGINE=InnoDB COMMENT=\'Contains the neighbour information for each territory.  One row for every connection.\';

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
',
);
    }

    /**
     * Get the SQL statements for the Down migration
     *
     * @return array list of the SQL strings to execute for the Down migration
     *               the keys being the datasources
     */
    public function getDownSQL()
    {
        return array (
  'mysql' => '
# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `user`;

DROP TABLE IF EXISTS `game`;

DROP TABLE IF EXISTS `empire`;

DROP TABLE IF EXISTS `game_match`;

DROP TABLE IF EXISTS `turn`;

DROP TABLE IF EXISTS `empire_order`;

DROP TABLE IF EXISTS `order_move`;

DROP TABLE IF EXISTS `order_retreat`;

DROP TABLE IF EXISTS `order_support`;

DROP TABLE IF EXISTS `order_convoy`;

DROP TABLE IF EXISTS `order_hold`;

DROP TABLE IF EXISTS `order_disband`;

DROP TABLE IF EXISTS `order_supply`;

DROP TABLE IF EXISTS `territory_template`;

DROP TABLE IF EXISTS `match_state`;

DROP TABLE IF EXISTS `territory_map`;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
',
);
    }

}