<?php

/**
 *	Content tags that consolidate existing and configuration defined tag types.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class FusionTag extends DataObject {

	private static $db = array(
		'Title' => 'Varchar(255)',
		'TagTypes' => 'Text'
	);

	private static $dependencies = array(
		'service' => '%$FusionService'
	);

	/**
	 *	The process to automatically consolidate existing and configuration defined tag types, executed on project build.
	 */

	public function requireDefaultRecords() {

		parent::requireDefaultRecords();

		// Retrieve existing and configuration defined tags that don't have an associated fusion tag.

		foreach($this->service->getFusionTagTypes() as $type => $field) {
			if(($tags = $type::get()->filter('FusionTagID', 0)) && $tags->exists()) {
				foreach($tags as $tag) {

					// Determine whether there's an existing fusion tag.

					if(!($existing = FusionTag::get()->filter('Title', $tag->$field)->first())) {

						// There was no fusion tag found, therefore create one from the existing tag.

						$fusion = FusionTag::create();
						$fusion->Title = $tag->$field;

						// Determine the fusion tag type.

						$fusion->TagTypes = serialize(array(
							$tag->ClassName => $tag->ClassName
						));
						$fusion->write();

						// Make sure the existing tag now has the associated fusion tag.

						$tag->FusionTagID = $fusion->ID;
					}
					else {

						// There was a fusion tag found, therefore append to the fusion tag types.

						$types = unserialize($existing->TagTypes);
						$types[$tag->ClassName] = $tag->ClassName;
						$existing->TagTypes = serialize($types);
						$existing->write();

						// Make sure the existing tag now has the associated fusion tag.

						$tag->FusionTagID = $existing->ID;
					}
					$tag->write();
					DB::alteration_message("Fusion Tag {$tag->$field}", 'created');
				}
			}
		}
	}

	/**
	 *	Restrict access for CMS users deleting fusion tags.
	 *
	 *	@parameter <{CURRENT_MEMBER}> member
	 *	@return boolean
	 */

	public function canDelete($member = null) {

		return false;
	}

	/**
	 *	Display the appropriate CMS fusion tag fields.
	 */

	public function getCMSFields() {

		$fields = parent::getCMSFields();

		// Allow customisation of types.

		$types = array();
		foreach($this->service->getFusionTagTypes() as $type => $field) {
			$types[$type] = $type;
		}
		$fields->replaceField('TagTypes', $list = ListboxField::create(
			'Types',
			'Tag Types',
			$types
		)->setMultiple(true));

		// Disable any existing types to prevent deletion.

		$items = is_string($this->TagTypes) ? array_keys(unserialize($this->TagTypes)) : array();
		$list->setValue($items);
		$list->setDisabledItems($items);
		return $fields;
	}

	/**
	 *	Confirm that the current fusion tag is valid.
	 */

	public function validate() {

		$result = parent::validate();

		// Confirm that the current fusion tag has been given a title and doesn't already exist.

		$this->Title = strtolower($this->Title);
		!$this->Title ? $result->error('"Title" required!') : (FusionTag::get_one('FusionTag', "ID != " . (int)$this->ID . " AND Title = '" . Convert::raw2sql($this->Title) . "'") ? $result->error('Tag already exists!') : $result->valid());

		// Allow extension customisation.

		$this->extend('validateFusionTag', $result);
		return $result;
	}

	/**
	 *	Merge the new and existing tag types as a serialised representation.
	 */

	public function onBeforeWrite() {

		parent::onBeforeWrite();

		// Make sure new tag types exist.

		if($this->Types) {

			// Parse the new tag types and merge them with the existing tag types.

			$types = explode(',', $this->Types);
			if(is_string($this->TagTypes)) {
				$types = array_merge($types, array_keys(unserialize($this->TagTypes)));
			}
			sort($types);

			// Save the tag types as a serialised representation.

			$formatted = array();
			foreach($types as $type) {
				$formatted[$type] = $type;
			}
			$this->TagTypes = serialize($formatted);

			// Make sure these go back into the tag types field, otherwise the edit form will not reflect the save.

			$this->Types = array_keys($formatted);
		}
	}

	/**
	 *	Make sure the consolidated tag types also have their tags updated to reflect these changes.
	 */

	public function onAfterWrite() {

		parent::onAfterWrite();
		$types = unserialize($this->TagTypes);
		$changed = $this->getChangedFields();
		foreach($this->service->getFusionTagTypes() as $type => $field) {
			if(isset($types[$type])) {

				// Determine the new tag types.

				$newTypes = array();
				if(isset($changed['TagTypes'])) {
					$before = unserialize($changed['TagTypes']['before']);
					$after = unserialize($changed['TagTypes']['after']);
					$newTypes = is_array($before) ? array_diff($after, $before) : $after;
				}

				// Determine whether a tag of the relevant type already exists for a new fusion tag.

				if((isset($changed['ID']) || isset($newTypes[$type])) && !($type::get()->filter($field, $this->Title)->first())) {

					// Create a new tag of the relevant type to match the fusion tag.

					$tag = $type::create();
					$tag->$field = $this->Title;
					$tag->FusionTagID = $this->ID;
					$tag->write();
				}

				// Determine whether the fusion tag has been updated.

				else if(!isset($changed['ID']) && isset($changed['Title']) && ($existing = $type::get()->filter($field, $changed['Title']['before'])->first())) {

					// Update the existing tag of the relevant type to match the fusion tag.

					$existing->$field = $changed['Title']['after'];
					$existing->write();
				}
			}
		}
	}

}
