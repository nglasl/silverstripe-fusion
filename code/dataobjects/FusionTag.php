<?php

/**
 *	Tags that consolidate existing and configuration defined tag types.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class FusionTag extends DataObject {

	private static $db = array(
		'Title' => 'Varchar(255)',
		'TagTypes' => 'Text'
	);

	private static $default_sort = 'Title';

	private static $dependencies = array(
		'service' => '%$FusionService'
	);

	/**
	 *	The process to automatically consolidate existing and configuration defined tag types, executed on project build.
	 */

	public function requireDefaultRecords() {

		parent::requireDefaultRecords();

		// This isn't required during testing.

		if(Controller::has_curr() && (Controller::curr() instanceof TestRunner)) {
			return;
		}

		// Retrieve existing and configuration defined tag types that have not been consolidated.

		$wrapped = array();
		foreach($this->service->getFusionTagTypes() as $type => $field) {
			if(($tags = $type::get()->filter('FusionTagID', 0)) && $tags->exists()) {
				foreach($tags as $tag) {

					// Determine whether there's an existing fusion tag.

					if(!($existing = FusionTag::get()->filter('Title', $tag->$field)->first())) {

						// There is no fusion tag, therefore instantiate one using the current tag.

						$fusion = FusionTag::create();
						$fusion->Title = $tag->$field;
						$fusion->TagTypes = serialize(array(
							$tag->ClassName => $tag->ClassName
						));
						$fusion->write();
						$fusionID = $fusion->ID;
						DB::alteration_message("\"{$tag->$field}\" Fusion Tag", 'created');
					}
					else {

						// There is a fusion tag, therefore append the current tag type.

						$types = unserialize($existing->TagTypes);
						$types[$tag->ClassName] = $tag->ClassName;
						$existing->TagTypes = serialize($types);
						$existing->write();
						$fusionID = $existing->ID;
					}

					// Update the current tag to point to this.

					$tag->FusionTagID = $fusionID;
					$tag->write();
				}
			}

			// The existing and configuration defined tag types need to be wrapped for serialised partial matching.

			$wrapped[] = "\"{$type}\"";
		}

		// Determine whether tag type exclusions have caused any fusion tags to become redundant.

		$tags = FusionTag::get()->where('TagTypes IS NOT NULL');
		if(count($wrapped)) {

			// Determine the fusion tags that only contain tag type exclusions.

			$tags = $tags->exclude('TagTypes:PartialMatch', $wrapped);
		}
		if($tags->exists()) {

			// Determine any data objects with the tagging extension.

			$objects = array();
			$configuration = Config::inst();
			$classes = ClassInfo::subclassesFor('DataObject');
			unset($classes['DataObject']);
			foreach($classes as $class) {

				// Determine the specific data object extensions.

				$extensions = $configuration->get($class, 'extensions', Config::UNINHERITED);
				if(is_array($extensions) && in_array('TaggingExtension', $extensions)) {
					$objects[] = $class;
				}
			}

			// Determine whether these fusion tags are now redundant.

			$mode = Versioned::get_reading_mode();
			Versioned::reading_stage('Stage');
			$types = array();
			$where = array();
			foreach($tags as $tag) {

				// Determine whether this fusion tag is being used.

				$ID = $tag->ID;
				foreach($objects as $object) {
					if($object::get()->filter('FusionTags.ID', $ID)->exists()) {

						// This fusion tag is being used, so keep it.

						continue 2;
					}
				}

				// These fusion tags are now redundant.

				$types = array_merge($types, unserialize($tag->TagTypes));
				$where[] = array(
					'FusionTagID' => $ID
				);
				$tag->delete();
				DB::alteration_message("\"{$tag->Title}\" Fusion Tag", 'deleted');
			}
			Versioned::set_reading_mode($mode);

			// The tag type exclusions need updating to reflect this.

			foreach($types as $exclusion) {
				$query = new SQLUpdate(
					$exclusion,
					array(
						'FusionTagID' => 0
					),
					$where
				);
				$query->useDisjunction();
				$query->execute();
			}
		}
	}

	/**
	 *	Restrict access when deleting fusion tags.
	 */

	public function canDelete($member = null) {

		return false;
	}

	/**
	 *	Display the appropriate fusion tag fields.
	 */

	public function getCMSFields() {

		$fields = parent::getCMSFields();

		// Determine whether the tag types should be displayed.

		$types = array();
		foreach($this->service->getFusionTagTypes() as $type => $field) {
			$types[$type] = $type;
		}
		if(count($types)) {

			// The serialised representation will require a custom field to display correctly.

			$fields->replaceField('TagTypes', $list = ListboxField::create(
				'Types',
				'Tag Types',
				$types
			)->setMultiple(true));

			// Disable existing tag types to prevent deletion.

			$items = is_string($this->TagTypes) ? array_keys(unserialize($this->TagTypes)) : array();
			$list->setValue($items);
			$list->setDisabledItems($items);
		}
		else {

			// There are no existing or configuration defined tag types.

			$fields->removeByName('TagTypes');
		}

		// Allow extension.

		$this->extend('updateFusionTagCMSFields', $fields);
		return $fields;
	}

	/**
	 *	Confirm that the fusion tag has been given a title and doesn't already exist.
	 */

	public function validate() {

		$result = parent::validate();
		$this->Title = strtolower($this->Title);
		if($result->valid() && !$this->Title) {
			$result->error('"Title" required!');
		}
		else if($result->valid() && FusionTag::get_one('FusionTag', array(
			'ID != ?' => $this->ID,
			'Title = ?' => $this->Title
		))) {
			$result->error('Tag already exists!');
		}

		// Allow extension.

		$this->extend('validateFusionTag', $result);
		return $result;
	}

	/**
	 *	Update the tag types with a serialised representation.
	 */

	public function onBeforeWrite() {

		parent::onBeforeWrite();

		// Determine whether new tag types exist.

		$types = $this->Types ? explode(',', $this->Types) : array();

		// Merge the new and existing tag types.

		if(is_string($this->TagTypes)) {
			$types = array_merge($types, array_keys(unserialize($this->TagTypes)));
		}
		if(!empty($types)) {
			sort($types);

			// Update the tag types with a serialised representation.

			$formatted = array();
			$existing = $this->service->getFusionTagTypes();
			foreach($types as $type) {

				// The tag type exclusions need checking.

				if(isset($existing[$type])) {
					$formatted[$type] = $type;
				}
			}
			$this->TagTypes = !empty($formatted) ? serialize($formatted) : null;

			// Update the custom field to reflect the change correctly.

			$this->Types = implode(',', $formatted);
		}
	}

	/**
	 *	Update the existing and configuration defined tag types to reflect the change.
	 */

	public function onAfterWrite() {

		parent::onAfterWrite();

		// Determine the tag types to update.

		$types = unserialize($this->TagTypes);
		$changed = $this->getChangedFields();
		foreach($this->service->getFusionTagTypes() as $type => $field) {
			if(isset($types[$type])) {

				// Determine whether new tag types exist.

				$newTypes = array();
				if(isset($changed['TagTypes'])) {
					$before = unserialize($changed['TagTypes']['before']);
					$after = unserialize($changed['TagTypes']['after']);
					$newTypes = is_array($before) ? array_diff($after, $before) : $after;
				}

				// Determine whether there's an existing tag.

				if((isset($changed['ID']) || isset($newTypes[$type])) && !($type::get()->filter($field, $this->Title)->first())) {

					// There is no tag, therefore instantiate one using this fusion tag.

					$tag = $type::create();
					$tag->$field = $this->Title;
					$tag->FusionTagID = $this->ID;
					$tag->write();
				}

				// Determine whether this fusion tag has been updated.

				else if(!isset($changed['ID']) && isset($changed['Title']) && ($existing = $type::get()->filter($field, $changed['Title']['before']))) {

					// There is an update, therefore update the existing tag/s to reflect the change.

					foreach($existing as $tag) {
						$tag->$field = $changed['Title']['after'];
						$tag->write();
					}
				}
			}
		}

		// Update the searchable content tagging for this fusion tag.

		if(!isset($changed['ID']) && isset($changed['Title'])) {
			$this->service->updateTagging($this->ID);
		}
	}

}
