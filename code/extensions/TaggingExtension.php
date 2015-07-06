<?php

// this extension will automatically be applied to pages, allowing tagging out of the box, however can also be applied to data objects

class TaggingExtension extends DataExtension {

	// store tags in a database field to allow searching without needing to parse the many_many relationship

	private static $db = array(
		'Tagging' => 'Text'
	);

	private static $searchable_fields = array(
		'Tagging'
	);

	// use a separate tag name to avoid issues around merging many_many relationships based on priority

	private static $many_many = array(
		'FusionTags' => 'FusionTag'
	);

	public function updateCMSFields(FieldList $fields) {

		$output = array();
		foreach(singleton('FusionTag')->parseTags() as $tag => $field) {
			$output[$tag] = $tag;
		}
		$intersect = array_intersect($this->owner->many_many(), $output);

		// if there are no "fused" tags found in the many_many relationship, add the fusion tagging

		if(empty($intersect)) {

			// allow tagging for the current page

			$fields->addFieldToTab('Root.Tagging', ListboxField::create(
				'FusionTags',
				'Tags',
				FusionTag::get()->map()->toArray()
			)->setMultiple(true));
		}
	}

	public function onBeforeWrite() {

		parent::onBeforeWrite();

		// search for fusion tags that we can use for searching, retrieving the relationship names

		$output = array();
		foreach(singleton('FusionTag')->parseTags() as $tag => $field) {
			$output[$tag] = $tag;
		}
		$intersect = array_intersect($this->owner->many_many(), $output);

		// if there are no "fused" tags in place

		if(empty($intersect)) {

			// store the fusion tagging in a database field to allow searching without needing to parse the many_many relationship

			$tagging = array();
			foreach($this->owner->FusionTags() as $tag) {
				$tagging = $tag->Title;
			}
			$this->owner->Tagging = implode(' ', $tagging);
		}

		// if there are "fused" tags found in the many_many relationship, write these into Tagging and FusionTags, so we have a single search field

		else {

			// clear the fusion tags out so we can keep it completely in sync

			$this->owner->FusionTags()->removeAll();

			// retrieve each relationship

			$tagging = array();
			foreach($intersect as $relationship => $tags) {

				// retrieve each tag

				foreach($this->owner->$relationship() as $tag) {

					// retrieve the associated fusion tag and push this into Tags and FusionTags

					$fusion = $tag->FusionTag();
					$this->owner->FusionTags()->add($fusion);
					$tagging[] = $fusion->Title;
				}
			}

			// store the fusion tagging in a database field to allow searching without needing to parse the many_many relationship

			$this->owner->Tagging = implode(' ', $tagging);
		}
	}

}
