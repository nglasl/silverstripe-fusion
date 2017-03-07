<?php

/**
 *	The fusion specific unit testing.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class FusionUnitTests extends SapphireTest {

	/**
	 *	The fusion testing tag requires a database table.
	 */

	protected $extraDataObjects = array(
		'FusionTestingTag'
	);

	public function setUpOnce() {

		parent::setUpOnce();

		// The fusion testing tag needs to be a custom tag type.

		Config::inst()->update('FusionService', 'custom_tag_types', array(
			'FusionTestingTag' => 'Title'
		));
	}

	/**
	 *	The test to ensure the fusion tags are functioning correctly.
	 */

	public function testTags() {

		// Instantiate a tag to use.

		$tag = FusionTestingTag::create();
		$tag->Title = 'new';
		$tag->write();

		// Determine whether a fusion tag exists.

		$ID = $tag->FusionTagID;
		$fusion = FusionTag::get()->byID($ID);
		$this->assertTrue(is_object($fusion));
		$this->assertEquals($fusion->Title, $tag->Title);

		// The tag types need to be wrapped for serialised partial matching.

		$this->assertContains('"FusionTestingTag"', $fusion->TagTypes);

		// Update the tag.

		$tag->Title = 'changed';
		$tag->write();

		// Determine whether the fusion tag reflects this.

		$fusion = FusionTag::get()->byID($ID);
		$this->assertEquals($fusion->Title, $tag->Title);

		// Update the fusion tag.

		$fusion->Title = 'again';
		$fusion->write();

		// Determine whether the tag reflects this.

		$tag = FusionTestingTag::get()->byID($tag->ID);
		$this->assertEquals($tag->Title, $fusion->Title);

		// Delete the tag.

		$tag->delete();

		// Determine whether the fusion tag reflects this.

		$fusion = FusionTag::get()->byID($ID);
		$this->assertTrue(is_object($fusion));
		$this->assertEquals($fusion->TagTypes, null);

		// The database needs to be emptied to prevent further testing conflict.

		self::empty_temp_db();
	}

	/**
	 *	The test to ensure the page tagging is functioning correctly.
	 */

	public function testTagging() {

		// Instantiate a page to use.

		$page = SiteTree::create();

		// Determine whether consolidated tags are found in the existing relationships.

		$types = array();
		$existing = singleton('FusionService')->getFusionTagTypes();
		foreach($existing as $type => $field) {
			$types[$type] = $type;
		}
		$types = array_intersect($page->many_many(), $types);
		if(empty($types)) {

			// Instantiate a tag to use, adding it against the page.

			$tag = FusionTag::create();
			$field = 'Title';
			$tag->$field = 'new';
			$tag->write();
			$page->FusionTags()->add($tag->ID);
		}
		else {

			// There are consolidated tags found.

			foreach($types as $relationship => $type) {

				// Instantiate a tag to use, adding it against the page.

				$tag = $type::create();
				$field = $existing[$type];
				$tag->$field = 'new';
				$tag->write();
				$page->$relationship()->add($tag->ID);

				// The consolidated tags are automatically combined, so this only needs to exist against one.

				break;
			}
		}
		$page->writeToStage('Stage');
		$page->writeToStage('Live');

		// Determine whether the page tagging reflects this.

		$this->assertContains($tag->$field, $page->Tagging);

		// Update the tag.

		$tag->$field = 'changed';
		$tag->write();

		// Determine whether the page tagging reflects this.

		$page = SiteTree::get()->byID($page->ID);
		$this->assertContains($tag->$field, $page->Tagging);

		// The database needs to be emptied to prevent further testing conflict.

		self::empty_temp_db();
	}

}

/**
 *	The tag used to ensure a fusion tag is functioning correctly.
 */

class FusionTestingTag extends DataObject implements TestOnly {

	private static $db = array(
		'Title' => 'Varchar(255)'
	);

	private static $extensions = array(
		'FusionExtension'
	);

}
