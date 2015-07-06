<?php

// the new unified tag
// these will automatically be instantiated for you on build for each existing tag, and those you define through config
//
//	FusionTag:
//		tags:
//			MyTag: 'TagField'

class FusionTag extends DataObject {

	private static $db = array(
		'Title' => 'Varchar(255)',
		'Types' => 'Text'
	);

	private static $tags = array();

	public function requireDefaultRecords() {

		parent::requireDefaultRecords();
		foreach($this->parseTags() as $tag => $field) {
			$this->createTags($tag, $field);
		}
	}

	public function getCMSFields() {

		$fields = parent::getCMSFields();
		$output = array();
		foreach($this->parseTags() as $tag => $field) {
			$output[$tag] = $tag;
		}
		$items = is_string($this->Types) ? array_keys(unserialize($this->Types)) : array();
		$fields->replaceField('Types', $list = ListboxField::create('Typez', 'Type', $output, $items, null, true));
		$list->setDisabledItems($items);
		return $fields;
	}

	public function onBeforeWrite() {

		parent::onBeforeWrite();

		// Don't change the default object creation if we're not creating it through the CMS.

		if($this->Typez) {
			$arr = explode(',', $this->Typez);
			if(is_string($this->Types)) {
				$arr = array_merge($arr, array_keys(unserialize($this->Types)));
			}
			sort($arr);
			$toSave = array();
			foreach($arr as $save) {
				$toSave[$save] = $save;
			}
			$this->Types = serialize($toSave);

			// write this back into the original value because the edit form will attempt to load from this

			$this->Typez = array_keys($toSave);
		}
	}

	public function onAfterWrite() {

		// this is in on after write because we need the ID to be set

		parent::onAfterWrite();

		// Create the relevant media types now.

		$types = unserialize($this->Types);
		$changed = $this->getChangedFields();
		foreach($this->parseTags() as $tag => $field) {

			// we can't use fusion ID here because the fusion tag may need to be created so a fusion ID can be retrieved

			if(isset($types[$tag])) {

				// determine the types that were added

				$intersect = null;
				if(isset($changed['Types'])) {
					$before = unserialize($changed['Types']['before']);
					$after = unserialize($changed['Types']['after']);
					$intersect = is_array($before) ? array_diff($after, $before) : $after;
				}
				if((isset($changed['ID']) || isset($intersect[$tag])) && !($tag::get()->filter($field, $this->Title)->first())) {

					// on new fusion tag or fusion type update, validate should catch any duplicates

					$new = $tag::create();
					$new->$field = $this->Title;
					$new->FusionTagID = $this->ID;
					$new->write();
				}
				else if(!isset($changed['ID']) && isset($changed['Title']) && ($existing = $tag::get()->filter($field, $changed['Title']['before'])->first())) {

					// there should only be one tag, otherwise tags are not unique

					$existing->$field = $changed['Title']['after'];
					$existing->write();
				}
			}
		}
	}

	public function canDelete($member = null) {

		return false;
	}

	public function parseTags() {

		$tagTypes = array();
		$classes = ClassInfo::subclassesFor('DataObject');
		foreach($classes as $object) {

			// Determine tags to fuse based on data objects ending with "Tag".

			if((strpos(strrev($object), strrev('Tag')) === 0) && ($object !== get_class())) {
				$tagTypes[$object] = 'Title';
			}
		}

		// Retrieve any other data objects to fuse, overriding so you can use a custom field.

		foreach(self::config()->get('tags') as $tag => $field) {
			if(in_array($tag, $classes) && ($tag !==  get_class())) {
				$tagTypes[$tag] = $field;
			}
		}
		return $tagTypes;
	}

	private function createTags($object, $field) {

		// Loop through existing tags and create a fusion tag for each, or add the type to an existing fusion tag.

		if(($tags = $object::get()->filter('FusionTagID', 0)) && $tags->exists()) {
			foreach($tags as $tag) {
				if($existing = FusionTag::get()->filter('Title', $tag->$field)->first()) {
					$test = unserialize($existing->Types);

					// The same class will only cause one appearance in the array.

					$test[$tag->ClassName] = $tag->ClassName;
					$existing->Types = serialize($test);
					$existing->write();
					$tag->FusionTagID = $existing->ID;
					$tag->write();
					DB::alteration_message('Fusion Tag CHANGED: ' . $tag->$field, 'changed');
				}
				else {
					$fusion = FusionTag::create();
					$fusion->Title = $tag->$field;
					$fusion->Types = serialize(array(
						$tag->ClassName => $tag->ClassName
					));
					$fusion->write();
					$tag->FusionTagID = $fusion->ID;
					$tag->write();
					DB::alteration_message('Fusion Tag: ' . $tag->$field, 'created');
				}
			}
		}
	}

	public function validate() {

		$result = parent::validate();

		// Confirm that the current tag has been given a title and doesn't already exist.

		$this->Title = strtolower($this->Title);
		!$this->Title ? $result->error('"Title" required!') : (FusionTag::get_one('FusionTag', "ID != " . (int)$this->ID . " AND Title = '" . Convert::raw2sql($this->Title) . "'") ? $result->error('Tag already exists!') : $result->valid());

		// Allow extension customisation.

		$this->extend('validateFusionTag', $result);
		return $result;
	}

}
