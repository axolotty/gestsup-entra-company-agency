SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET default_storage_engine=INNODB;

DROP TABLE IF EXISTS `tentra_company_agency`;
DROP TABLE IF EXISTS `tentra_company_agency_sync`;
DELETE FROM `tplugins` WHERE `name`='entra_company_agency';