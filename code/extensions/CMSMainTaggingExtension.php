<?php

/**
 *	This extension will automatically be applied, allowing CMS searchable content tagging.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class CMSMainTaggingExtension extends Extension {

	/**
	 *	Update page filtering, allowing CMS searchable content tagging.
	 */

	public function updateSearchForm($form) {

		// Instantiate a field containing existing tags.

		$form->Fields()->insertBefore(ListboxField::create(
			'q[Tagging]',
			'Tagging',
			FusionTag::get()->map('Title', 'Title')->toArray(),
			(($filtering = $this->owner->getRequest()->getVar('q')) && isset($filtering['Tagging']) && is_string($filtering['Tagging'])) ? explode(' ', $filtering['Tagging']) : array(),
			null,
			true
		), 'q[Term]');

		// Update page filtering, allowing tags to be parsed correctly.

		Requirements::javascript(FUSION_PATH . '/javascript/fusion.js');
	}

}
