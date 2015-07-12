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

The fusion tags are managed like any other data object, where tag types reflect those that have been consolidated. This is designed to push changes out to those respective tag types, maintaining any functionality that directly requires a consolidated tag.

### Searchable Content Tagging

![tagging](images/fusion-tagging.png)

![search](images/fusion-search.png)

Tagging is immediately available to pages out of the box, allowing searchable tags that may also be filtered through the CMS. To enable tagging for a custom data object:

```yaml
Object:
  extensions:
    - 'TaggingExtension'
```

## Maintainer Contact

	Nathan Glasl, nathan@silverstripe.com.au
