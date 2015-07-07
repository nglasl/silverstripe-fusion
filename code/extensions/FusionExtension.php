<?php

/**
 *	This extension will automatically be applied to existing and configuration defined tag types, and will update the associated fusion tag to reflect changes.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class FusionExtension extends DataExtension {

	private static $has_one = array(
		'FusionTag' => 'FusionTag'
	);

	/**
	 *	Restrict access for CMS users deleting tags.
	 *
	 *	@parameter <{CURRENT_MEMBER}> member
	 *	@return boolean
	 */

	public function canDelete($member) {

		return false;
	}

	/**
	 *	Make sure the associated fusion tag is not visible.
	 */

	public function updateCMSFields(FieldList $fields) {

		$fields->removeByName('FusionTagID');
	}

	/**
	 *	Confirm that the current tag is valid.
	 */

	public function validate(ValidationResult $result) {

		// Determine the field to validate, based on configuration that may have been defined.

		$validate = 'Title';
		foreach(Config::inst()->get('FusionService', 'custom_tag_types') as $type => $field) {
			if($type === $this->owner->ClassName) {
				$validate = $field;
			}
		}

		// Confirm that the current tag has been given a title and doesn't already exist.

		$this->owner->$validate = strtolower($this->owner->$validate);
		!$this->owner->$validate ? $result->error("\"{$validate}\" required!") : (DataObject::get_one($this->owner->ClassName, "ID != " . (int)$this->owner->ID . " AND Title = '" . Convert::raw2sql($this->owner->$validate) . "'") ? $result->error('Tag already exists!') : $result->valid());

		// Allow extension customisation.

		$this->owner->extend('validateFusionExtension', $result);
		return $result;
	}

	/**
	 *	Make sure the associated fusion tag exists and has its tag types updated to reflect any changes.
	 */

	public function onAfterWrite() {

		parent::onAfterWrite();

		// Determine the field to validate, based on configuration that may have been defined.

		$write = 'Title';
		foreach(Config::inst()->get('FusionService', 'custom_tag_types') as $type => $field) {
			if($type === $this->owner->ClassName) {
				$write = $field;
			}
		}

		// Determine whether there's an existing fusion tag.

		$changed = $this->owner->getChangedFields();
		if(isset($changed['ID']) && !($existing = FusionTag::get()->filter('Title', $this->owner->$write)->first())) {

			// There was no fusion tag found, therefore create one from the existing tag.

			$fusion = FusionTag::create();
			$fusion->Title = $this->owner->$write;

			// Determine the fusion tag type.

			$fusion->TagTypes = serialize(array(
				$this->owner->ClassName => $this->owner->ClassName
			));
			$fusion->write();

			// Make sure this tag now has the associated fusion tag.

			$this->owner->FusionTagID = $fusion->ID;
			$this->owner->write();
		}

		// There was a fusion tag found, therefore append to the fusion tag types.

		else if(isset($changed['ID']) && $existing) {
			$types = unserialize($existing->TagTypes);
			$types[$this->owner->ClassName] = $this->owner->ClassName;
			$existing->TagTypes = serialize($types);
			$existing->write();

			// Make sure this tag now has the associated fusion tag.

			$this->owner->FusionTagID = $existing->ID;
			$this->owner->write();
		}

		// If this tag has been updated, reflect this change for the associated fusion tag.

		else if(isset($changed[$write]) && ($existing = FusionTag::get()->byID($this->owner->FusionTagID))) {

			// Update the existing fusion tag to match this tag.

			$existing->Title = $changed[$write]['after'];
			$existing->write();
		}
	}

	/**
	 *	Unlink this tag type from the associated fusion tag.
	 */

	public function onAfterDelete() {

		parent::onAfterDelete();
		$fusion = FusionTag::get()->byID($this->owner->FusionTagID);
		$types = unserialize($fusion->TagTypes);
		unset($types[$this->owner->ClassName]);
		$fusion->TagTypes = serialize($types);
		$fusion->write();
	}

}
