<?php
/*
Plugin Name: Canva Connector
Version: 0.1.0
Description: Connects a Piwigo gallery to the Canva Piwigo Media app without sharing Piwigo API keys with a central backend.
Plugin URI: https://github.com/PCT-BR/Canvaconnector-for-piwigo
Author: PCT-BR
*/

defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

define('CANVA_CONNECTOR_PATH', PHPWG_PLUGINS_PATH . basename(dirname(__FILE__)) . '/');

add_event_handler('get_admin_plugin_menu_links', 'canva_connector_admin_menu');

function canva_connector_admin_menu($menu)
{
  $menu[] = array(
    'NAME' => 'Canva Connector',
    'URL'  => get_root_url() . 'plugins/canva_connector/connect.php',
  );

  return $menu;
}
