<?php

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/*
Plugin Name: Gravity Forms Campaign Monitor Add-On
Plugin URI: https://gravityforms.com
Description: Integrates Gravity Forms with Campaign Monitor, allowing form submissions to be automatically sent to your Campaign Monitor account.
Version: 3.9
Author: Gravity Forms
Author URI: https://gravityforms.com
License: GPL-2.0+
Text Domain: gravityformscampaignmonitor
Domain Path: /languages

------------------------------------------------------------------------
Copyright 2009-2020 Rocketgenius, Inc.

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
*/

define( 'GF_CAMPAIGN_MONITOR_VERSION', '3.9' );

// If Gravity Forms is loaded, bootstrap the Campaign Monitor Add-On.
add_action( 'gform_loaded', array( 'GF_CampaignMonitor_Bootstrap', 'load' ), 5 );

/**
 * Class GF_CampaignMonitor_Bootstrap
 *
 * Handles the loading of the Campaign Monitor Add-On and registers with the Add-On framework.
 */
class GF_CampaignMonitor_Bootstrap {

	/**
	 * If the Feed Add-On Framework exists, Campaign Monitor Add-On is loaded.
	 *
	 * @access public
	 * @static
	 */
	public static function load() {

		if ( ! method_exists( 'GFForms', 'include_feed_addon_framework' ) ) {
			return;
		}

		require_once( 'class-gf-campaignmonitor.php' );

		GFAddOn::register( 'GFCampaignMonitor' );

	}

}

/**
 * Returns an instance of the GFCampaignMonitor class
 *
 * @see    GFCampaignMonitor::get_instance()
 *
 * @return object GFCampaignMonitor
 */
function gf_campaignmonitor() {
	return GFCampaignMonitor::get_instance();
}
