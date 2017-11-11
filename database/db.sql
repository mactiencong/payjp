-- phpMyAdmin SQL Dump
-- version 4.0.10.18
-- https://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Apr 21, 2017 at 11:42 PM
-- Server version: 5.1.73
-- PHP Version: 5.6.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `soe_admin`
--

-- --------------------------------------------------------

--
-- Table structure for table `payjp_client_cards`
--

CREATE TABLE IF NOT EXISTS `payjp_client_cards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `payjp_card_id` varchar(100) NOT NULL,
  `payjp_card_detail` text NOT NULL COMMENT 'card detail retrieve from pay.jp',
  `is_active` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1-active,2-unavailable',
  `created` int(11) DEFAULT NULL,
  `modified` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;

--
-- Dumping data for table `payjp_client_cards`
--

-- --------------------------------------------------------

--
-- Table structure for table `payjp_client_subscription_plan`
--

CREATE TABLE IF NOT EXISTS `payjp_client_subscription_plan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subscription_plan_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `payjp_plan_id` varchar(100) NOT NULL COMMENT 'plan_id from pay.jp',
  `payjp_plan_detail` text COMMENT 'plan detail from pay.jp',
  `device_number` tinyint(3) NOT NULL COMMENT 'total device numbers the user register to use',
  `is_active` tinyint(1) NOT NULL COMMENT '1-active,0-deactive',
  `amount` int(11) DEFAULT NULL COMMENT 'amount base on plan and total device numbers',
  `last_charge` int(11) DEFAULT NULL,
  `cancel_at` int(11) DEFAULT NULL,
  `paused_at` int(11) DEFAULT NULL,
  `resumed_at` int(11) DEFAULT NULL,
  `payjp_customer_id` varchar(100) DEFAULT NULL,
  `payjp_subscription_id` varchar(100) DEFAULT NULL,
  `payjp_subscription_data` text,
  `expire_time` int(11) DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  `modified` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=12 ;

--
-- Dumping data for table `payjp_client_subscription_plan`
--

-- --------------------------------------------------------

--
-- Table structure for table `payjp_subscription_plan`
--

CREATE TABLE IF NOT EXISTS `payjp_subscription_plan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) CHARACTER SET utf8 NOT NULL,
  `description` varchar(500) CHARACTER SET utf8 NOT NULL,
  `interval` varchar(20) NOT NULL COMMENT '"month" or "year"',
  `status` int(11) NOT NULL DEFAULT '1' COMMENT '1-active,0-deactive,2-deleted',
  `created` int(11) DEFAULT NULL,
  `modified` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `payjp_subscription_plan`
--

INSERT INTO `payjp_subscription_plan` (`id`, `name`, `description`, `interval`, `status`, `created`, `modified`) VALUES
(1, '月額払い', '', 'month', 1, NULL, NULL),
(2, '年間一括払い（12ヶ月分→10ヶ月分　20%OFF)', '', 'year', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payjp_transaction`
--

CREATE TABLE IF NOT EXISTS `payjp_transaction` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1-the first payment,0-payment by renewing',
  `client_id` int(11) NOT NULL,
  `client_subscription_plan_id` int(11) NOT NULL,
  `amount` int(11) NOT NULL,
  `payjp_charge_id` varchar(100) DEFAULT NULL COMMENT 'charge id from payjp',
  `payjp_token` varchar(100) DEFAULT NULL,
  `payjp_response_data` text,
  `payjp_callback_data` text,
  `time` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT NULL COMMENT '1-success,0-fail',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;

--
-- Dumping data for table `payjp_transaction`
--

ALTER TABLE  `payjp_client_subscription_plan` ADD  `first_charge` INT NULL DEFAULT NULL COMMENT  'time of the first charge' AFTER  `last_charge` ;

ALTER TABLE  `user_registration_payment` ADD  `email` VARCHAR( 100 ) NULL DEFAULT NULL COMMENT 'the email use register on frontend' AFTER  `client_id` ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
