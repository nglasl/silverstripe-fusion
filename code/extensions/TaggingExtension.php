<?php

/**
 *	This extension will automatically be applied to pages, allowing searchable content tagging.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class TaggingExtension extends DataExtension {

	/**
	 *	The tagging will be directly stored in a database field, allowing searching without needing to parse the fusion tags relationship.
	 */

	private static $db = array(
		'Tagging' => 'Text'
	);

	private static $searchable_fields = array(
		'Title',
		'Content',
		'Tagging'
	);

	/**
	 *	The tagging will need to use a unique relationship name, otherwise there are issues around configuration merge priority.
	 */

	private static $many_many = array(
		'FusionTags' => 'FusionTag'
	);

	/**
	 *	Display the appropriate tagging field.
	 */

	public function updateCMSFields(FieldList $fields) {

		// Determine whether consolidated tags are found in the existing relationships.

		$types = array();
		foreach(singleton('FusionService')->getFusionTagTypes() as $type => $field) {
			$types[$type] = $type;
		}
		if(empty(array_intersect($this->owner->many_many(), $types))) {

			// There are no consolidated tags found, therefore instantiate a tagging field.

			$fields->addFieldToTab('Root.Tagging', ListboxField::create(
				'FusionTags',
				'Tags',
				FusionTag::get()->map()->toArray()
			)->setMultiple(true));
		}
	}

	/**
	 *	Update the tagging to reflect the change, allowing searchable content.
	 */

	public function onBeforeWrite() {

		parent::onBeforeWrite();

		// Determine whether consolidated tags are found in the existing relationships.

		$types = array();
		foreach(singleton('FusionService')->getFusionTagTypes() as $type => $field) {
			$types[$type] = $type;
		}
		$types = array_intersect($this->owner->many_many(), $types);
		if(empty($types)) {

			// There are no consolidated tags found, therefore update the tagging based on the fusion tags.

			$tagging = array();
			foreach($this->owner->FusionTags() as $tag) {
				$tagging[] = $tag->Title;
			}
		}
		else {

			// Empty the fusion tags to begin.

			$this->owner->FusionTags()->removeAll();

			// There are consolidated tags found, therefore update the tagging based on these.

			$tagging = array();
			foreach($types as $relationship => $type) {
				foreach($this->owner->$relationship() as $tag) {

					// Update both the fusion tags and tagging.

					$fusion = $tag->FusionTag();
					$this->owner->FusionTags()->add($fusion);
					$tagging[] = $fusion->Title;
				}
			}
		}
		$this->owner->Tagging = implode(' ', $tagging);
	}

}
