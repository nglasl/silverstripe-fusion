# [fusion](https://packagist.org/packages/nglasl/silverstripe-fusion)

_The current release is **1.0.0**_

	A module for SilverStripe which will automatically consolidate existing tag types
	into new fusion tags, and allows searchable content tagging out of the box.

## Requirement

* SilverStripe 3.1.X

## Getting Started

* Place the module under your root project directory.
* `/dev/build`
* Select `Tagging` through the CMS.

## Overview

### Tags

These will be automatically consolidated, based on data objects ending with `Tag`. To further customise this process, you may define the following configuration:

```yaml
FusionService:
  custom_tag_types:
    Tag: 'Field'
```

### Management

![management](images/fusion-management.png)

![tag](images/fusion-tag.png)

The fusion tags are managed like any other data object, where tag types reflect those that have been consolidated, and are defined to push changes out to those respective tags. This is designed to maintain custom functionality that may directly require a consolidated tag.

### Searchable Content Tagging

![tagging](images/fusion-tagging.png)

![search](images/fusion-search.png)

This is immediately available to pages out of the box, where tagging may be filtered through the CMS. The `TaggingExtension` may be applied to custom data objects where required.

## Maintainer Contact

	Nathan Glasl, nathan@silverstripe.com.au
