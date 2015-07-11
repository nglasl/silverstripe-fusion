<?php

/**
 *	This extension will automatically be applied, allowing CMS searchable content tagging.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class CMSMainTaggingExtension extends Extension {

	/**
	 *	Update the page filtering, allowing CMS searchable content tagging.
	 */

	public function updateSearchForm($form) {

		// Instantiate a field containing the existing tags.

		$form->Fields()->insertBefore(ListboxField::create(
			'q[Tagging]',
			'Tags',
			FusionTag::get()->map('Title', 'Title')->toArray(),
			(($filtering = $this->owner->getRequest()->getVar('q')) && isset($filtering['Tagging'])) ? $filtering['Tagging'] : array(),
			null,
			true
		), 'q[Term]');

		// Update the page filtering, allowing multiple tags.

		Requirements::javascript(FUSION_PATH . '/javascript/fusion.js');
	}

}
