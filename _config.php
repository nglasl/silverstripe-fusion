<?php

/**
 *	The fusion specific configuration settings.
 *	@author Nathan Glasl <nathan@symbiote.com.au>
 */

if(!defined('FUSION_PATH')) {
	define('FUSION_PATH', rtrim(basename(dirname(__FILE__))));
}

// Apply the fusion extension to existing and configuration defined tag types.

foreach(singleton('FusionService')->getFusionTagTypes() as $type => $field) {
	$type::add_extension($type, 'FusionExtension');
}

// Update the current fusion admin icon.

Config::inst()->update('FusionAdmin', 'menu_icon', FUSION_PATH . '/images/icon.png');
