<?php

// this extension will automatically be applied to pages, allowing tagging out of the box, however can also be applied to data objects

class TaggingExtension extends DataExtension {

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

		// if there are "fused" tags found in the many_many relationship, write these into FusionTags, so we have a single search field

		if(!empty($intersect)) {

			// clear the fusion tags out so we can keep it completely in sync

			$this->owner->FusionTags()->removeAll();

			// retrieve each relationship

			foreach($intersect as $relationship => $tags) {

				// retrieve each tag

				foreach($this->owner->$relationship() as $tag) {

					// retrieve the associated fusion tag and push this into FusionTags

					$this->owner->FusionTags()->add($tag->Fusion());
				}
			}
		}
	}

}
