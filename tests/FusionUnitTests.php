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

		// The fusion testing tag needs to be included.

		Config::inst()->update('FusionService', 'custom_tag_types', array(
			'FusionTestingTag' => 'Title'
		));
	}

	/**
	 *	The test to ensure the fusion tags are functioning correctly.
	 */

	public function testFusionTags() {

		// Instantiate a tag to use.

		$tag = FusionTestingTag::create();
		$tag->Title = 'new';
		$tag->write();

		// Determine whether a fusion tag exists.

		$ID = $tag->FusionTagID;
		$fusion = FusionTag::get()->byID($ID);
		$this->assertTrue(is_object($fusion));
		$this->assertEquals($fusion->Title, $tag->Title);
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
	}

}

/**
 *	The tag used to ensure a fusion tag is functioning correctly.
 */

class FusionTestingTag extends DataObject implements TestOnly {

	private static $db = array(
		'Title' => 'Varchar(255)'
	);

	/**
	 *	The tag requires the fusion extension.
	 */

	private static $extensions = array(
		'FusionExtension'
	);

}
