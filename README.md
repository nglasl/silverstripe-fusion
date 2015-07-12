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

The tags are managed like any other data object.

![tag](images/fusion-tag.png)

The tag types will reflect existing tags that have been consolidated, and may be defined to push changes out to those respective tags. This is designed to maintain custom functionality that directly requires a consolidated tag.

### Searchable Content Tagging

![tagging](images/fusion-tagging.png)

This is available to pages out of the box. However you may add the `TaggingExtension` to custom data objects where required.

![search](images/fusion-search.png)

The search functionality is available to pages directly from the CMS.

## Maintainer Contact

	Nathan Glasl, nathan@silverstripe.com.au
