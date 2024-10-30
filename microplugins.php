<?php
/**
 * @package Microplugins
 * @version 1.1.3
 */
/*
Plugin Name: Microplugins
Plugin URI: http://wordpress.org/plugins/microplugins/
Description: Permite añadir funcionalidad al sitio evitando la modificación de los archivos del tema activo.
Author: Andy D. Navarro Taño
Version: 1.1.3
Author URI: http://andaniel05.wordpress.com/
Text Domain: microplugins
*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

define('MICROPLUGINS_DIR', __DIR__);
define('MICROPLUGINS_CACHE_DIR', MICROPLUGINS_DIR . '/cache');
define('MICROPLUGINS_URI', plugin_dir_url( __FILE__ ));

require_once __DIR__ . '/class-microplugins.php';

register_activation_hook( __FILE__, array('Microplugins', 'activation_hook') );

Microplugins::get_instance();