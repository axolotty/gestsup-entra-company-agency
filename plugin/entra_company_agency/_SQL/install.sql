SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET default_storage_engine=INNODB;

CREATE TABLE `tentra_company_agency` (
  `id` INT(5) NOT NULL AUTO_INCREMENT,
  `entra_company_name` VARCHAR(150) NOT NULL,
  `agency_id` INT(5) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `entra_company_name` (`entra_company_name`),
  KEY `agency_id` (`agency_id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8;

-- tracks which agency_id the plugin itself linked to a given user, so a later
-- company change can swap it out without ever touching a manually-assigned agency
CREATE TABLE `tentra_company_agency_sync` (
  `user_id` INT(10) NOT NULL,
  `agency_id` INT(5) NOT NULL,
  PRIMARY KEY (`user_id`),
  KEY `agency_id` (`agency_id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8;

INSERT INTO `tplugins` (`name`, `label`, `description`, `icon`, `version`) VALUES ('entra_company_agency','Sociétés Entra ID','Associe automatiquement une agence GestSup à chaque société Entra ID (companyName) lors de la connexion SSO Office 365','building','1.2');