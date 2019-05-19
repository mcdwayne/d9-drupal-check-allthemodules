# YAML Encoder

This provides a YAML encoder for use with [the Drupal 8/Symfony serialization
framework][1]. [YAML doesn't have an official Media/MIME type][2]; the module
uses `application/yaml` [which apparently matches MediaWiki][3].

Stolen with many thanks from [#1897612] where there's been discussion over
whether this should live in core or contrib.

[1]: https://www.drupal.org/docs/8/api/serialization-api/serialization-api-overview
[2]: https://www.iana.org/assignments/media-types/media-types.xhtml
[3]: https://www.drupal.org/project/drupal/issues/1897612#comment-6983338
[#1897612]: https://drupal.org/node/1897612