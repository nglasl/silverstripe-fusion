<?php

/**
 *	CMS interface for creating, managing and consolidating content tagging.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class FusionAdmin extends ModelAdmin {

	private static $managed_models = 'FusionTag';

	private static $menu_title = 'Tagging';

	private static $menu_description = 'Create, manage and consolidate <strong>tags</strong> for your content.';

	private static $url_segment = 'tagging';

}
