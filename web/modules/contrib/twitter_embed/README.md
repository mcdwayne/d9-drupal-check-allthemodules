# Twitter Embed

Simple embed of Twitter widgets, without OAuth.

## Features

- Expose Twitter widgets to Drupal with Block and FieldFormatter.
- Covered widgets: Timeline, Button.

## Configuration

After enabling the module, you have the following options:

### Embed as a Block

- Add a _Twitter timeline_ or a _Twitter button_ block.
- Configuration is per block.

### Embed as a Field

- Add a _Twitter embed_ field to any content entity.
- On the 'Manage display' tab, choose between _Twitter timeline_
or _Twitter button_ format.

The display options (theme, link color, ...) from the blocks
are currently not available for field formatters.

## Documentation

The widget options are described on 
[Twitter Publish](https://publish.twitter.com/) 
and 
[Twitter Developer Documentation](https://dev.twitter.com/web/overview).

## Dependencies

None.

## Related modules

For the WYSIWYG, use [URL Embed](https://www.drupal.org/project/url_embed).

To get a Twitter timeline
- as a Block,
use [Twitter Block](https://www.drupal.org/project/twitter_block),
available for Drupal 7 and 8.
- as a Field,
use [Twitter Embed Field](https://www.drupal.org/project/twitter_embed_field),
currently as a dev release for Drupal 8.
This module aims to 
- unify a single configuration interface over Blocks and Fields
- provide several widgets implementation.

For more advanced use cases, review the 
[Twitter](https://www.drupal.org/project/twitter) module.
It is available for Drupal 7 and there is a Drupal 8 release on its way.

Have also a look at the 
[Social API](https://www.drupal.org/project/social_api)
module for Drupal 8.

## Roadmap

- Implement the Button widget
- Unit tests
