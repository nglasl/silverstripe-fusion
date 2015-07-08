<?php

/**
 *	This extension will automatically be applied to pages, and allows searchable content tagging.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class TaggingExtension extends DataExtension {

	/**
	 *	The tagging will be stored in a database field to allow searching without needing to parse the fusion tags relationship.
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
	 * The tagging will need to use a unique relationship name, otherwise issues around configuration merge priority will appear.
	 */

	private static $many_many = array(
		'FusionTags' => 'FusionTag'
	);

	/**
	 *	Display the appropriate fusion tag relationship field, but only when another form of tagging does not exist.
	 */

	public function updateCMSFields(FieldList $fields) {

		$types = array();
		foreach(singleton('FusionService')->getFusionTagTypes() as $type => $field) {
			$types[$type] = $type;
		}

		// Determine whether no consolidated tags are found in the existing relationships.

		if(empty(array_intersect($this->owner->many_many(), $types))) {

			// Allow content tagging.

			$fields->addFieldToTab('Root.Tagging', ListboxField::create(
				'FusionTags',
				'Tags',
				FusionTag::get()->map()->toArray()
			)->setMultiple(true));
		}
	}

	/**
	 *	Merge the new and existing tags into the fusion tags field, and populate the tagging database field for search purposes.
	 */

	public function onBeforeWrite() {

		parent::onBeforeWrite();
		$types = array();
		foreach(singleton('FusionService')->getFusionTagTypes() as $type => $field) {
			$types[$type] = $type;
		}

		// Determine whether no consolidated tags are found in the existing relationships.

		$types = array_intersect($this->owner->many_many(), $types);
		if(empty($types)) {

			// Determine the tagging to be stored.

			$tagging = array();
			foreach($this->owner->FusionTags() as $tag) {
				$tagging[] = $tag->Title;
			}
		}

		// There are consolidated tags found in the existing relationships.

		else {

			// Make sure the fusion tags are emptied to begin.

			$this->owner->FusionTags()->removeAll();

			// Retrieve each consolidated tag from the relationships.

			$tagging = array();
			foreach($types as $relationship => $type) {
				foreach($this->owner->$relationship() as $tag) {

					// Determine the tagging to be stored.

					$fusion = $tag->FusionTag();
					$this->owner->FusionTags()->add($fusion);
					$tagging[] = $fusion->Title;
				}
			}
		}
		$this->owner->Tagging = implode(' ', $tagging);
	}

}
