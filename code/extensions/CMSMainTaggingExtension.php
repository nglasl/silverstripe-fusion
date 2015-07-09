<?php

/**
 *	This extension will automatically be applied, allowing CMS searchable content tagging.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class CMSMainTaggingExtension extends Extension {

	/**
	 *	Update the page filter display, allowing CMS searchable content tagging.
	 */

	public function updateSearchForm($form) {

		// Instantiate a field containing existing tags.

		$form->Fields()->insertBefore(ListboxField::create(
			'q[Tagging]',
			'Tagging',
			FusionTag::get()->map()->toArray()
		)->setMultiple(true), 'q[Term]');

		// Update the page filters, allowing tags to be parsed correctly.

		$form->setFormAction(null);
		$form->Actions()->replaceField('action_doSearch', FormAction::create(
			'updateSearchFilters',
			_t('CMSMain_left_ss.APPLY_FILTER', 'Apply Filter')
		)->addExtraClass('ss-ui-action-constructive'));
	}

	/**
	 *	Update the page filters, allowing tags to be parsed correctly.
	 *
	 *	@parameter <{SEARCH_FORM_DATA}> array
	 */

	public function updateSearchFilters($data) {

		$link = $this->owner->Link();
		$separator = '?';

		// Determine whether page filters exist.

		if(isset($data['q']) && is_array($data['q'])) {
			foreach($data['q'] as $filter => $value) {

				// Determine whether tagging filters exist.

				if($filter === 'Tagging' && is_array($value)) {

					// Parse the tagging into a searchable format.

					$tagging = array();
					foreach($value as $tag) {

						// Retrieve the tag title.

						$tagging[] = FusionTag::get()->byID($tag)->Title;
					}
					$value = implode(' ', $tagging);
				}

				// Append the page filters to the URL.

				$link = HTTP::setGetVar("q[{$filter}]", $value, $link, $separator);
				$separator = '&';
			}
		}
		return $this->owner->redirect(urlencode($link));
	}

}
