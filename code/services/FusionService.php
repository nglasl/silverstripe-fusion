<?php

/**
 *	Determines the existing and configuration defined tag types to consolidate.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class FusionService {

	/**
	 *	These custom tag types will be consolidated into fusion tags.
	 */

	private static $custom_tag_types = array();

	/**
	 *	Retrieve the fusion tag types to consolidate, searching for existing and configuration defined tag types.
	 *
	 *	@return array(string, string)
	 */

	public function getFusionTagTypes() {

		$types = array();

		// Determine existing tag types.

		$classes = ClassInfo::subclassesFor('DataObject');
		unset($classes['FusionTag']);
		foreach($classes as $class) {

			// Determine which tag types to consolidate, based on data objects ending with "Tag".

			if((strpos(strrev($class), strrev('Tag')) === 0)) {

				// Use the title field as a default.

				$types[$class] = 'Title';
			}
		}

		// Determine any other tag types to consolidate, based on configuration that may have been defined.

		foreach(Config::inst()->get('FusionService', 'custom_tag_types') as $type => $field) {
			if(in_array($type, $classes)) {

				// Use the defined configuration field.

				// if has field add it, otherwise use title
				$types[$type] = $field;
			}
		}
		return $types;
	}

}
