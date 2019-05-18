# About Safe External Links

## Contents

 * [Introduction](#introduction)
 * [Requirements](#requirements)
 * [Recommended modules](#recommended-modules)
 * [Installation](#installation)
 * [Configuration](#configuration)
 * [Maintainers](#maintainers)

## Introduction

Safe External Links (sel) is a topical module which addresses user editable
external links on a Drupal site. It processes links and adds `target="_blank"`
and `rel="noreferrer"` or `rel="noopener"` attributes if a link is external.

### Menu links

Menu links are processed right after the module is enabled and the cache of the
site is rebuilt.

### Link field formatter

The content of link fields could be handled by the __Safe external link__
(`sel_link`) formatter.

After that this module is installed, newly created link fields will have the
`sel_link` formatter as default formatter, but the pre-existing field
formatters won't be changed.

If the optional SpamSpan filter module is available, the formatter will
obfuscate email links as well (which are starting with `mailto:` protocol).

### Filter plugin

The filter plugin implicitly requires the `filter_url` filter.

The content of these fields can be processed by the __Safe external link
filter__ (`filter_sel`) filter plugin. If it's enabled, please move it after the
`filter_url` filter (_Convert URLs into links_).

## Requirements

The module does not define any hard dependencies.

## Recommended modules

 * SpamSpan filter (https://www.drupal.org/project/spamspan):
   When enabled, emails in link fields will be obfuscated if the Link Filter is
   enabled for the selected format.

## Installation

 * Install as you would normally install a contributed Drupal module.

## Configuration

The module _requires_ zero configuration. When enabled, it will

 * preprocess rendered menus (if any),
 * provide an external link filter for filter formats which processes external
   links,
 * provide a link formatter for link fields which processes external links and
   obfuscates email (mailto:...) links if SpamSpan filter module is available

Menu link processing and default settings for the provided link formatter could
be configured on Administration » Configuration » Content authoring » Standard
Link Settings.

### Configuration options

 * The default `noreferrer` rel attribute value may be changed to `noopener`.
 * Other optional rel attribute values may be configured as well: `external` and
   `nofollow`.

Menu link processing could be disabled on the Safe External Links config form.

External link processing and email link sanitization could be disabled at the
`sel_link` formatter configuration.

## Maintainers

Current maintainers:

 * Zoltan A. Horvath (huzooka) - https://drupal.org/user/281301
