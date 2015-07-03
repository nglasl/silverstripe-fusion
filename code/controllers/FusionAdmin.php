<?php

// manage fusion tags

class FusionAdmin extends ModelAdmin {

	private static $managed_models = 'FusionTag';

	private static $menu_title = 'Tagging';

	private static $menu_description = 'Manage unified content tags.';

	private static $url_segment = 'fusion';

}
