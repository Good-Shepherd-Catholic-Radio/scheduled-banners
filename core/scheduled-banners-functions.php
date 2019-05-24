<?php
/**
 * Provides helper functions.
 *
 * @since	  1.0.0
 *
 * @package	Scheduled_Banners
 * @subpackage Scheduled_Banners/core
 */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Returns the main plugin object
 *
 * @since		1.0.0
 *
 * @return		Scheduled_Banners
 */
function SCHEDULEDPOPUPS() {
	return Scheduled_Banners::instance();
}