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

		// Update the page filtering, allowing multiple tags.

		Requirements::javascript(FUSION_PATH . '/javascript/fusion.js');

		// Instantiate a field containing the existing tags.

		$form->Fields()->insertBefore(ListboxField::create(
			'q[FusionTags]',
			'Tags',
			FusionTag::get()->map('ID', 'Title')->toArray(),
			(($filtering = $this->owner->getRequest()->getVar('q')) && isset($filtering['FusionTags'])) ? $filtering['FusionTags'] : array(),
			null,
			true
		), 'q[Term]');
        
        $filterClass = $form->Fields()->dataFieldByName('q[FilterClass]');
        $options = $filterClass->getSource();
        unset($options['CMSSiteTreeFilter_Search']);
        $filterClass->setSource($options);

		// Allow extension.

		$this->owner->extend('updateCMSMainTaggingExtensionSearchForm', $form);
	}

}

class CMSSiteTreeFilterTagging_Search extends CMSSiteTreeFilter_Search {
    public function getFilteredPages() {
		$pages = parent::getFilteredPages();
        if (isset($this->params['FusionTags']) && count($this->params['FusionTags'])) {
            $pages = $pages->filter(array(
                'FusionTags.ID' => $this->params['FusionTags']
            ));
        }
        return $pages;
	}
}