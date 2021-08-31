<?php

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
Plugin Name: Gravity Forms Zammad Add-On
Plugin URI: https://www.gravityforms.com
Description: Integrates Gravity Forms with Zammad.
Version: 1.2
Author: Johan VIVIEN
Author URI: https://hotfirenet.com
License: GPL-3.0
Text Domain: gravityformszammad
Domain Path: /languages

------------------------------------------------------------------------
Copyright 2009-2019 rocketgenius
last updated: October 20, 2010

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 **/

define( 'GF_ZAMMAD_VERSION', '1.2' );

// If Gravity Forms is loaded, bootstrap the Zammad Add-On.
add_action( 'gform_loaded', array( 'GF_Zammad_Bootstrap', 'load' ), 5 );

/**
 * Class GF_Zammad_Bootstrap
 *
 * Handles the loading of the Zammad Add-On and registers with the Add-On Framework.
 */
class GF_Zammad_Bootstrap {

	/**
	 * If the Feed Add-On Framework exists, Zammad Add-On is loaded.
	 *
	 * @access public
	 * @static
	 */
	public static function load() {

		if ( ! method_exists( 'GFForms', 'include_feed_addon_framework' ) ) {
			return;
		}

		require_once( 'class-gf-zammad.php' );

		GFAddOn::register( 'GFZammad' );

	}

}

/**
 * Returns an instance of the GFZammad class
 *
 * @see    GFZammad::get_instance()
 *
 * @return object GFZammad
 */
function gf_zammad() {
	return GFZammad::get_instance();
}