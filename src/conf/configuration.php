<?php

error_reporting(E_ALL | E_NOTICE);

// TODO: make something else
$ini = parse_ini_file(SUPRA_CONF_PATH . 'supra.ini', true);

require_once SUPRA_CONF_PATH . 'log.php';
require_once SUPRA_CONF_PATH . 'database.php';
require_once SUPRA_CONF_PATH . 'objectrepository.php';
require_once SUPRA_CONF_PATH . 'locale.php';
require_once SUPRA_CONF_PATH . 'filestorage.php';
require_once SUPRA_CONF_PATH . 'user.php';
