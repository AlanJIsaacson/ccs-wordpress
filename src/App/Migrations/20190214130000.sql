CREATE TABLE `ccs_lots` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `framework_id` varchar(255) DEFAULT NULL,
  `wordpress_id` varchar(255) DEFAULT NULL,
  `salesforce_id` varchar(255) DEFAULT NULL,
  `lot_number` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `hide_suppliers` varchar(255) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;