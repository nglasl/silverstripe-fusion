<?php

// this extension will automatically be applied to pages, allowing tagging out of the box

class SiteTreeTagsExtension extends DataExtension {

	// name it tags so that two tagging modules can't be used on the same page

	private static $many_many = array(
		'Tags' => 'FusionTag'
	);

	public function updateCMSFields(FieldList $fields) {

		// make sure a tags relationship doesn't already exist

		if($this->owner->Tags()->dataClass() === 'FusionTag') {

			// allow tagging for the current page

			$fields->addFieldToTab('Root.Tagging', ListboxField::create(
				'Tags',
				'Tags',
				FusionTag::get()->map()->toArray()
			)->setMultiple(true));
		}
	}

}
