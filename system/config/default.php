<?php
// Site
$_['site_url']             = '';
$_['site_ssl']             = false;

// Url
$_['url_autostart']        = true;

// Language
$_['language_directory']   = 'en-gb';
$_['language_autoload']    = array('en-gb');

// Date
$_['date_timezone']        = 'UTC';

// Database
$_['db_engine']               = 'mysqli'; // mpdo, mssql, mysql, mysqli or postgre
$_['db_hostname']             = 'localhost';
$_['db_username']             = 'root';
$_['db_password']             = '';
$_['db_database']             = '';
$_['db_port']                 = 3306;
$_['db_autostart']            = false;

// Mail
$_['mail_engine']             = 'smtp'; // mail or smtp
$_['mail_from']               = ''; // Your E-Mail
$_['mail_sender']             = ''; // Your name or company name
$_['mail_reply_to']           = ''; // Reply to E-Mail
$_['mail_smtp_hostname']      = '';
$_['mail_smtp_username']      = '';
$_['mail_smtp_password']      = '';
$_['mail_smtp_port']          = 25;
$_['mail_smtp_timeout']       = 5;
$_['mail_verp']               = false;
$_['mail_parameter']          = '';

// Cache
$_['cache_engine']            = 'file'; //для отключения стереть, для включения file или memcached
$_['cache_expire']            = 432000;
$_['cache_autoactualize_min'] =  5; //минимальное время (сек)  для актуализации, не рекомендуется устанавливать меньше 5 (объекты, закэшированные менее 5 секунд наза актуализироваться не будут)
$_['cache_autoactualize_max'] =  900; //максимальное время для актуализации в секундах (объекты старше указанного времени не актуализируются)

// Session
$_['session_engine']          = 'db';
$_['session_autostart']       = true;
$_['session_name']            = 'OCSESSID';

// Template
$_['template_engine']         = 'twig';
$_['template_directory']      = '';
$_['template_cache']          = true;

// Error
$_['error_display']           = true;
$_['error_log']               = true;
$_['error_filename']          = 'error.log';

// Reponse
$_['response_header']         = array('Content-Type: text/html; charset=utf-8');
$_['response_compression']    = 0;

// Autoload Configs
$_['config_autoload']         = array();

// Autoload Libraries
$_['library_autoload']        = array();

// Autoload Libraries
$_['model_autoload']          = array();

// Actions
$_['action_default']          = 'common/home';
$_['action_router']           = 'startup/router';
$_['action_error']            = 'error/not_found';
$_['action_pre_action']       = array();
$_['action_event']            = array();
