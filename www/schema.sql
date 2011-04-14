-- phpMyAdmin SQL Dump
-- version 3.3.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Apr 14, 2011 at 03:17 PM
-- Server version: 5.0.51
-- PHP Version: 5.2.4-2ubuntu5.3

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `paramlog`
--

-- --------------------------------------------------------

--
-- Table structure for table `games`
--

CREATE TABLE IF NOT EXISTS `games` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL,
  `lookup` varchar(32) NOT NULL,
  `player1` int(11) NOT NULL,
  `player2` int(11) NOT NULL,
  `size` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  `timestamp` int(11) NOT NULL,
  `playtime` double NOT NULL,
  `nummoves` mediumint(9) NOT NULL,
  `outcome1` tinyint(4) NOT NULL,
  `outcome2` tinyint(4) NOT NULL,
  `outcomeref` tinyint(4) NOT NULL,
  `saved` tinyint(4) NOT NULL,
  `version1` varchar(64) NOT NULL,
  `version2` varchar(64) NOT NULL,
  `host` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `userid` (`userid`,`id`),
  KEY `timestamp` (`timestamp`),
  KEY `lookup` (`lookup`),
  KEY `result` (`player1`,`player2`,`size`,`time`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `moves`
--

CREATE TABLE IF NOT EXISTS `moves` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL,
  `gameid` int(11) NOT NULL,
  `movenum` smallint(6) NOT NULL,
  `position` varchar(16) NOT NULL,
  `side` tinyint(4) NOT NULL,
  `value` float NOT NULL,
  `outcome` tinyint(4) NOT NULL,
  `timetaken` float NOT NULL,
  `work` bigint(20) NOT NULL,
  `nodes` bigint(20) NOT NULL,
  `comment` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `userid` (`userid`,`gameid`,`movenum`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `players`
--

CREATE TABLE IF NOT EXISTS `players` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL,
  `parent` int(11) NOT NULL,
  `type` tinyint(4) NOT NULL,
  `weight` int(11) NOT NULL default '1',
  `name` varchar(255) NOT NULL,
  `params` varchar(255) character set latin1 collate latin1_general_cs NOT NULL,
  `comment` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `userid` (`userid`,`type`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `results`
--

CREATE TABLE IF NOT EXISTS `results` (
  `userid` int(11) NOT NULL,
  `player1` int(11) NOT NULL,
  `player2` int(11) NOT NULL,
  `size` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  `weight` int(11) NOT NULL,
  `p1wins` int(11) NOT NULL,
  `p2wins` int(11) NOT NULL,
  `ties` int(11) NOT NULL,
  `numgames` int(11) NOT NULL,
  PRIMARY KEY  (`userid`,`player1`,`player2`,`size`,`time`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE IF NOT EXISTS `sessions` (
  `sessionkey` varchar(255) NOT NULL,
  `userid` int(11) NOT NULL,
  `logintime` int(11) NOT NULL,
  `activetime` int(11) NOT NULL,
  `cookietime` int(11) NOT NULL,
  `timeout` int(11) NOT NULL,
  PRIMARY KEY  (`sessionkey`),
  KEY `userid` (`userid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `sizes`
--

CREATE TABLE IF NOT EXISTS `sizes` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL,
  `weight` int(11) NOT NULL,
  `name` varchar(32) NOT NULL,
  `size` varchar(32) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `userid` (`userid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `times`
--

CREATE TABLE IF NOT EXISTS `times` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL,
  `weight` int(11) NOT NULL,
  `name` varchar(32) NOT NULL,
  `move` float NOT NULL,
  `game` float NOT NULL,
  `sims` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `userid` (`userid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `userid` int(11) NOT NULL auto_increment,
  `email` varchar(128) NOT NULL,
  `password` varchar(128) NOT NULL,
  `active` tinyint(4) NOT NULL,
  `activatekey` varchar(128) NOT NULL,
  `apikey` varchar(128) NOT NULL,
  `gamename` varchar(128) NOT NULL,
  PRIMARY KEY  (`userid`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `apikey` (`apikey`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;
