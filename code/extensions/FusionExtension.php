<?php

/**
 *	This extension will automatically be applied to existing and configuration defined tag types, and will help consolidate these into fusion tags.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class FusionExtension extends DataExtension {

	private static $has_one = array(
		'FusionTag' => 'FusionTag'
	);

	/**
	 *	Restrict access when deleting tags.
	 */

	public function canDelete($member) {

		return false;
	}

	/**
	 *	Hide the fusion tag.
	 */

	public function updateCMSFields(FieldList $fields) {

		$fields->removeByName('FusionTagID');
	}

	/**
	 *	Confirm that the tag has been given a title and doesn't already exist.
	 */

	public function validate(ValidationResult $result) {

		// Determine the field to use, based on the configuration defined tag types.

		$validate = 'Title';
		$class = $this->owner->ClassName;
		foreach(Config::inst()->get('FusionService', 'custom_tag_types') as $type => $field) {
			if($type === $class) {
				$validate = $field;
			}
		}

		// Confirm that the tag has been given a title and doesn't already exist.

		$this->owner->$validate = strtolower($this->owner->$validate);
		if($result->valid() && !$this->owner->$validate) {
			$result->error("\"{$validate}\" required!");
		}
		else if($result->valid() && $class::get_one($class, "ID != " . (int)$this->owner->ID . " AND {$validate} = '" . Convert::raw2sql($this->owner->$validate) . "'")) {
			$result->error('Tag already exists!');
		}
		return $result;
	}

	/**
	 *	Update the fusion tag to reflect the change.
	 */

	public function onAfterWrite() {

		parent::onAfterWrite();

		// Determine the field to use, based on the configuration defined tag types.

		$write = 'Title';
		$class = $this->owner->ClassName;
		foreach(Config::inst()->get('FusionService', 'custom_tag_types') as $type => $field) {
			if($type === $class) {
				$write = $field;
			}
		}

		// Determine whether there's an existing fusion tag.

		$changed = $this->owner->getChangedFields();
		$existing = FusionTag::get()->filter('Title', $this->owner->$write)->first();
		if(isset($changed['ID']) && !$existing) {

			// There is no fusion tag, therefore instantiate one using this tag.

			$fusion = FusionTag::create();
			$fusion->Title = $this->owner->$write;
			$fusion->TagTypes = serialize(array(
				$class => $class
			));
			$fusion->write();

			// Update this tag to point to the fusion tag.

			$this->owner->FusionTagID = $fusion->ID;
			$this->owner->write();
		}
		else if(isset($changed['ID']) && $existing) {

			// There is a fusion tag, therefore append this tag type.

			$types = unserialize($existing->TagTypes);
			$types[$class] = $class;
			$existing->TagTypes = serialize($types);
			$existing->write();

			// Update this tag to point to the fusion tag.

			$this->owner->FusionTagID = $existing->ID;
			$this->owner->write();
		}
		else if(isset($changed[$write]) && !isset($changed['FusionTagID']) && $existing && ($existing->ID != $this->owner->FusionTagID)) {

			// Update the fusion tag to remove this tag type.

			$fusion = FusionTag::get()->byID($this->owner->FusionTagID);
			$types = unserialize($fusion->TagTypes);
			unset($types[$this->owner->ClassName]);
			$fusion->TagTypes = !empty($types) ? serialize($types) : null;
			$fusion->write();

			// There is an existing fusion tag, therefore append this tag type.

			$types = unserialize($existing->TagTypes);
			$types[$class] = $class;
			$existing->TagTypes = serialize($types);
			$existing->write();

			// Update this tag to point to the new fusion tag.

			$this->owner->FusionTagID = $existing->ID;
			$this->owner->write();
		}

		// Determine whether this tag has been updated.

		else if(isset($changed[$write]) && !isset($changed['FusionTagID']) && ($existing = FusionTag::get()->byID($this->owner->FusionTagID))) {

			// There is an update, therefore update the existing fusion tag to reflect the change.

			$existing->Title = $changed[$write]['after'];
			$existing->write();
		}
	}

	/**
	 *	Update the fusion tag to remove this tag type.
	 */

	public function onAfterDelete() {

		parent::onAfterDelete();
		$fusion = FusionTag::get()->byID($this->owner->FusionTagID);
		$types = unserialize($fusion->TagTypes);
		unset($types[$this->owner->ClassName]);
		$fusion->TagTypes = !empty($types) ? serialize($types) : null;
		$fusion->write();
	}

}
