<?php

/**
 *	The fusion specific configuration settings.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

if(!defined('FUSION_PATH')) {
	define('FUSION_PATH', rtrim(basename(dirname(__FILE__))));
}

// Apply any extensions.

foreach(Singleton('FusionTag')->parseTags() as $tag => $field) {
	Object::add_extension($tag, 'FusionExtension');
}

// Update the current fusion admin icon.

Config::inst()->update('FusionAdmin', 'menu_icon', FUSION_PATH . '/images/icon.png');
