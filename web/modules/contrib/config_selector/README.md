Configuration Selector
======================

The Configuration Selector module allows modules and install profiles to
provide multiple versions of optional configuration. For example, if you want to
provide a view that lists content that integrates with search_api or
content_lock or both or neither. You can provide 4 different views in
config/optional and as different dependencies are installed views are enabled
and disabled according to a priority set with the configuration.

This module currently has no interface for editing or creating features and is
meant for use by developers who can manually edit their configuration.

How it works
------------

Configuration Selector configuration can be added to configuration entities'
third party settings for example:
```yaml
third_party_settings:
  config_selector:
    feature: your_feature_name
    priority: 10
```
Note you also should add config_selector to the configuratione entity's
dependencies like so:
```yaml
dependencies:
  module:
    - config_selector
```

When there is multiple active configuration with matching
`third_party_settings.config_selector.feature` values the one with the highest
priority will be enabled. All the others will be disabled.

The advantage of only supporting configuration that can be disabled means that
any user customisations are not lost as no operation is destructive.

Limitations
-----------
The module only works with configuration entities that can be disabled. It
provides configuration schema for Views and Blocks out-of-the-box. Many
configuration entities cannot be disabled.
