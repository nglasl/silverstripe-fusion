<?php

/**
 *	This provides functionality for the fusion module.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class FusionService {

	/**
	 *	These custom tag types will be consolidated into fusion tags.
	 */

	private static $custom_tag_types = array();

	/**
	 *	These tag types will not be consolidated into fusion tags.
	 */

	private static $tag_type_exclusions = array();

	/**
	 *	Determine the existing and configuration defined tag types to consolidate.
	 *
	 *	@return array(string, string)
	 */

	public function getFusionTagTypes() {

		// Determine existing tag types.

		$types = array();
		$configuration = Config::inst();
		$exclusions = $configuration->get('FusionService', 'tag_type_exclusions');
		$classes = ClassInfo::subclassesFor('DataObject');
		unset($classes['FusionTag']);
		foreach($classes as $class) {

			// Determine the tag types to consolidate, based on data objects ending with "Tag".

			if((strpos(strrev($class), strrev('Tag')) === 0) && !in_array($class, $exclusions) && !ClassInfo::classImplements($class, 'TestOnly')) {

				// Use the title field as a default.

				$types[$class] = 'Title';
			}
		}

		// Determine configuration defined tag types.

		foreach($configuration->get('FusionService', 'custom_tag_types') as $type => $field) {
			if(in_array($type, $classes) && !in_array($type, $exclusions)) {

				// Use the configuration defined field.

				$types[$type] = $field;
			}
		}
		return $types;
	}

	/**
	 *	Update the searchable content tagging for a specific fusion tag.
	 *
	 *	@parameter <{FUSION_TAG_ID}> integer
	 */

	public function updateTagging($fusionID) {

		// Determine any data objects with the tagging extension.

		$classes = ClassInfo::subclassesFor('DataObject');
		unset($classes['DataObject']);
		$configuration = Config::inst();
		foreach($classes as $class) {

			// Determine the specific data object extensions.

			$extensions = $configuration->get($class, 'extensions', Config::UNINHERITED);
			if(is_array($extensions) && in_array('TaggingExtension', $extensions)) {

				// Determine whether this fusion tag is being used.

				$mode = Versioned::get_reading_mode();
				Versioned::reading_stage('Stage');
				$objects = $class::get()->filter('FusionTags.ID', $fusionID);

				// Update the searchable content tagging for these data objects.

				if($class::has_extension($class, 'Versioned')) {

					// These data objects are versioned.

					foreach($objects as $object) {

						// Update the staging version.

						$object->writeWithoutVersion();
					}
					Versioned::reading_stage('Live');
					$objects = $class::get()->filter('FusionTags.ID', $fusionID);
					foreach($objects as $object) {

						// Update the live version.

						$object->writeWithoutVersion();
					}
				}
				else {

					// These data objects are not versioned.

					foreach($objects as $object) {
						$object->write();
					}
				}
				Versioned::set_reading_mode($mode);
			}
		}
	}

}
