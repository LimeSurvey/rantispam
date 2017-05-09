DROP TABLE IF EXISTS #__rantispam_tokens_prob;
DROP TABLE IF EXISTS #__rantispam_token_count;
DROP TABLE IF EXISTS #__rantispam_spams_detected;
DROP TABLE IF EXISTS #__rantispam_messages_hash;
DROP TABLE IF EXISTS #__rantispam_banip;

CREATE TABLE IF NOT EXISTS `#__rantispam_tokens_prob` (
  `token` varchar(150) NOT NULL,
  `prob` float DEFAULT '0.00',
  `prev_prob` float DEFAULT '0.00',
  `in_ham` int DEFAULT '0',
  `in_spam` int DEFAULT '0',
  `provider` varchar(256),
  `param1` varchar(256) NOT NULL,
  `param2` varchar(256) NOT NULL,
  `update_time` datetime NULL,	
  PRIMARY KEY  (`token`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=_bin;

CREATE TABLE IF NOT EXISTS `#__rantispam_token_count` (
  `token_count_id` int(11) NOT NULL auto_increment,
  `good_count` int(11) DEFAULT '0',
  `bad_count` int(11) DEFAULT '0',
  PRIMARY KEY  (`token_count_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__rantispam_spams_detected` (
  `spam_id` int(11) NOT NULL auto_increment,
  `user_id` int(11),
  `user_ip` varchar(100),
  `user_name` varchar(100),
  `spam_text` text,
  `subject` text,
  `spam_score` float DEFAULT '0.95',
  `provider` varchar(100),
  `message_id` varchar(20) NULL,
  `param1` varchar(10) NOT NULL,
  `param2` varchar(10) NOT NULL,
  `param3` varchar(10) NOT NULL,
  `param4` varchar(10) NOT NULL,
  `detect_time` datetime NULL,	
  PRIMARY KEY  (`spam_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__rantispam_messages_hash` (
  `hash` varchar(100) NOT NULL,
  PRIMARY KEY  (`hash`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__rantispam_banip` (
  `id` int(11) NOT NULL auto_increment,	
  `bannedip` varchar(45) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE(bannedip)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8mb4_unicode_ci;

