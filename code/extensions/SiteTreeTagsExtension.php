<?php

// this extension will automatically be applied to pages, allowing tagging out of the box

class SiteTreeTagsExtension extends DataExtension {

	// use a separate tag name to avoid issues around merging many_many relationships based on priority

	private static $many_many = array(
		'FusionTags' => 'FusionTag'
	);

	public function updateCMSFields(FieldList $fields) {

		$output = array();
		foreach(singleton('FusionTag')->parseTags() as $tag => $field) {
			$output[$tag] = $tag;
		}
		$intersect = array_intersect($output, $this->owner->many_many());

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

}
