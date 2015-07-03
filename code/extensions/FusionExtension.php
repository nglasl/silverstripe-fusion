<?php

// this extension will automatically be applied to existing tags, or those you have defined through config
// this will basically keep the existings tags inline with the new global fusion tags

class FusionExtension extends DataExtension {

	private static $has_one = array(
		'Fusion' => 'FusionTag'
	);

	public function updateCMSFields(FieldList $fields) {

		$fields->removeByName('FusionID');
	}

	public function onAfterWrite() {

		// this is in on before write because we need to assign the fusion ID

		parent::onAfterWrite();

		// Make sure there's a relevant fusion tag for each other tag created and modified.
		// Retrieve the "fusion field" from config, otherwise fall back to the default.

		$fusionField = 'Title';
		foreach(Config::inst()->get('FusionTag', 'tags') as $tag => $field) {
			if($tag === $this->owner->ClassName) {
				$fusionField = $field;
			}
		}

		// If the tag has been changed, reflect this change for the appropriate fusion tag.

		$changed = $this->owner->getChangedFields();

		// we can't use fusion ID here because the fusion tag may need to be created so a fusion ID can be retrieved

		if(isset($changed['ID']) && !($existing = FusionTag::get()->filter('Title', $this->owner->$fusionField)->first())) {

			// create new fusion tag

			$fusion = FusionTag::create();
			$fusion->Title = $this->owner->$fusionField;
			$fusion->Types = serialize(array(
				$this->owner->ClassName => $this->owner->ClassName
			));
			$fusion->write();
			$this->owner->FusionID = $fusion->ID;
			$this->owner->write();
		}
		else if(isset($changed['ID']) && $existing) {

			//add this type to THAT fusion tag

			$test = unserialize($existing->Types);
			$test[$this->owner->ClassName] = $this->owner->ClassName;
			$existing->Types = serialize($test);
			$existing->write();
			$this->owner->FusionID = $existing->ID;
			$this->owner->write();
		}
		else if(isset($changed[$fusionField]) && ($existing = FusionTag::get()->byID($this->owner->FusionID))) {

			// update the existing fusion tag title

			$existing->Title = $changed[$fusionField]['after'];
			$existing->write();
		}
	}

	// this basically unlinks a fusion tag, but is only defined on the off chance that you happen to get around permissions and delete a tag

	public function onAfterDelete() {

		parent::onAfterDelete();

		// Remove this media type from the fusion tag, but don't delete the fusion tag.

		$fusion = FusionTag::get()->byID($this->owner->FusionID);
		$types = unserialize($fusion->Types);
		unset($types[$this->owner->ClassName]);
		$fusion->Types = serialize($types);
		$fusion->write();
	}

	// Don't allow deletion of tags, because this could cause data integrity issues.

	public function canDelete($member) {

		return false;
	}

	public function validate(ValidationResult $result) {

		$fusionField = 'Title';
		foreach(Config::inst()->get('FusionTag', 'tags') as $tag => $field) {
			if($tag === $this->owner->ClassName) {
				$fusionField = $field;
			}
		}

		// Confirm that the current tag has been given a title and doesn't already exist.

		$this->owner->$fusionField = strtolower($this->owner->$fusionField);
		!$this->owner->$fusionField ? $result->error("\"{$fusionField}\" required!") : (DataObject::get_one(get_class($this->owner), "ID != " . (int)$this->owner->ID . " AND Title = '" . Convert::raw2sql($this->owner->$fusionField) . "'") ? $result->error('Tag already exists!') : $result->valid());

		// Allow extension customisation.

		$this->owner->extend('validateFusionExtensionTag', $result);
		return $result;
	}

}
