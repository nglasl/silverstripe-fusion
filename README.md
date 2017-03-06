# [fusion](https://packagist.org/packages/nglasl/silverstripe-fusion)

_The current release is **2.0.6**_

> A module for SilverStripe which will automatically consolidate existing tag types into new fusion tags, and allows searchable content tagging out of the box.

## Requirement

* SilverStripe 3.1 → **3.5**

## Getting Started

* Place the module under your root project directory.
* `/dev/build`
* Select `Tagging` through the CMS.

## Overview

### Tags

These will be automatically consolidated, based on data objects ending with `Tag`. To further customise this process, you may define the following:

```yaml
FusionService:
  custom_tag_types:
    TagName: 'Attribute'
```

```yaml
FusionService:
  tag_type_exclusions:
    - 'TagName'
```

### Tag Management

![management](https://raw.githubusercontent.com/nglasl/silverstripe-fusion/master/images/fusion-management.png)

![tag](https://raw.githubusercontent.com/nglasl/silverstripe-fusion/master/images/fusion-tag.png)

The fusion tags are managed like any other data object, where tag types reflect those that have been consolidated. This pushes changes out to those respective tag types, maintaining any functionality directly requiring a tag that has been consolidated.

### Searchable Content Tagging

![tagging](https://raw.githubusercontent.com/nglasl/silverstripe-fusion/master/images/fusion-tagging.png)

![filtering](https://raw.githubusercontent.com/nglasl/silverstripe-fusion/master/images/fusion-filtering.png)

The fusion tags are immediately available to pages out of the box, allowing searchable content tagging (using `Tagging`) for both content authors and users.

To enable tagging for a data object:

```yaml
DataObjectName:
  extensions:
    - 'TaggingExtension'
```

## Maintainer Contact

	Nathan Glasl, nathan@silverstripe.com.au
