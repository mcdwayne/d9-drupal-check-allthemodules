# Block Render
Block Render is a iFrame Endpoint, REST API, and Javascript SDK for delivering
rendered blocks to other applications. This module can be used to inject blocks
into other sites or native applications.

## Requirements
This module requires the following modules:
* [REST](https://www.drupal.org/documentation/modules/rest)
* [Serialization](https://www.drupal.org/documentation/modules/serialization)

## Recommended modules
* [REST UI](https://www.drupal.org/project/restui)
  Makes it easier to configure the REST endpoints in Drupal 8.

## Installation
* Install as you would normally install a contributed Drupal module. See:
  https://www.drupal.org/documentation/install/modules-themes/modules-8 for
  further information.
* Enable the "Block Render" & "Block Render Multiple" REST endpoints.

## Configuration
There is no explicit configuration. However, block configuration can be passed
to blocks through the query parameters.

## Examples
There are a number of ways that blocks can be configured and delivered. For the
purposes of these examples:
* **BLOCK** is the block machine name (an alphanumeric string). For instance,
  the main content block typically has a machine name of "content".
* **FORMAT** is the format of the request. Typically this is either "json" or
  "xml", but it could be whatever your server supports.

### iFrame
A block can be accessed on an iframe at:
```
http://example.com/block-render/BLOCK
```
Block Render will render the block in the same theme the block was setup for.
Configuration can be passed into the block as a query string. For instance, if
the block has a configuration variable "num_posts", I could pass it in like:
```
http://example.com/block-render/BLOCK?num_posts=5
```
This configuration will be passed to the block before it is rendered. It might
be best to create an empty theme for delivering blocks via iframe (i.e. all
the blocks in the theme should be in the "Disabled" region). This prevents
having other blocks in the response (like header, footer, etc.).

### REST
The REST endpoint provides rendered blocks with all of their assets ready to be
rendered server-side or client-side.

#### List
A list of all renderable blocks can be accessed at:
```
http://example.com/block-render?_format=FORMAT
```
```json
[
  {
    "id": "topplayers",
    "label": "Top Players",
    "theme": "frame"
  }
]
```

#### Single
A single block can be rendered at:
http://example.com/block-render/BLOCK?_format=FORMAT
Configuration can be passed to the block in the same way as the iFrame endpoint.
The block's dependencies (css & javascript) are included in the response as well
as a list of libraries that are used. Libraries can be excluded from the
response by using the `loaded` query parameter. As an example:
```
http://example.com/block-render/BLOCK?_format=FORMAT&loaded[]=jquery
```
and it will be removed from the response.
```json
{
  "dependencies": {
    "react": "0.13.3",
    "jquery": "2.1.4",
    "top-players": ""
  },
  "assets": {
    "header": [
      {
        "tag": "link",
        "attributes": {
          "rel": "stylesheet",
          "href": "http://example.com/modules/custom/scores/assets/styles/compiled/top-players.css?nwo8o2",
          "media": "all"
        },
        "browsers": {
          "IE": true,
          "!IE": true
        }
      }
    ],
    "footer": [
      {
        "tag": "script",
        "value": "",
        "browsers": [],
        "attributes": {
          "src": "http://example.com/core/assets/vendor/jquery/jquery.min.js?v=2.1.4"
        }
      },
      {
        "tag": "script",
        "value": "",
        "browsers": [],
        "attributes": {
          "src": "http://example.com/vendor/react/react.min.js?v=0.13.3"
        }
      },
      {
        "tag": "script",
        "value": "",
        "browsers": [],
        "attributes": {
          "src": "http://example.com/modules/custom/gc_scores/assets/scripts/compiled/top-players.js?nwo8o2"
        }
      }
    ]
  },
  "content": "<div class=\"style-default\" data-block-id=\"scores_top_players\" data-tour=\"0\" data-num-players=\"5\" id=\"block-topplayers\">\n  \n    \n      \n  </div>\n"
}
```

#### Multiple
Multiple blocks can be requested with the `blocks` query param like so:
```
http://example.com/block-render?_format=FORMAT&blocks[]=BLOCK1&blocks[]=BLOCK2
```
The dependencies are combined for all blocks that are loaded, so it is best to
load all blocks on a single request so all of the dependencies can be resolved
before rendering on the page. To exclude a dependency the `loaded` query param
can be used just as in the Single response. Configuration can also be passed to
the blocks, but it is now namespaced like so:
```
http://example.com/block-render?_format=FORMAT&blocks[]=BLOCK1&blocks[]=BLOCK2&BLOCK1[num_posts]=5
```
The block machine name becomes the variable name and the configuration is an
associative array of values.
```json
{
  "dependencies": {
    "react": "0.13.3",
    "jquery": "2.1.4",
    "top-players": ""
  },
  "assets": {
    "header": [
      {
        "tag": "link",
        "attributes": {
          "rel": "stylesheet",
          "href": "http://golfchannel8.loc/modules/custom/gc_scores/assets/styles/compiled/top-players.css?nwon3l",
          "media": "all"
        },
        "browsers": {
          "IE": true,
          "!IE": true
        }
      }
    ],
    "footer": [
      {
        "tag": "script",
        "value": "",
        "browsers": [],
        "attributes": {
          "src": "http://golfchannel8.loc/core/assets/vendor/jquery/jquery.min.js?v=2.1.4"
        }
      },
      {
        "tag": "script",
        "value": "",
        "browsers": [],
        "attributes": {
          "src": "http://golfchannel8.loc/vendor/react/react.min.js?v=0.13.3"
        }
      },
      {
        "tag": "script",
        "value": "",
        "browsers": [],
        "attributes": {
          "src": "http://golfchannel8.loc/modules/custom/gc_scores/assets/scripts/compiled/top-players.js?nwon3l"
        }
      }
    ]
  },
  "content": {
    "topplayers": "<div class=\"style-default\" data-block-id=\"gc_scores_top_players\" data-tour=\"0\" data-num-players=\"5\" id=\"block-topplayers\">\n  \n    \n      \n  </div>\n",
    "help": ""
  }
}
```

### Javascript SDK
The Javascript SDK provides a method for rapidly injecting blocks into a web
page.
The `Drupal.block_render.render()` method is what is used to render data onto
the page. It takes to arguments. The first is the url of the Block Render
Multiple endpoint. The second is an object of configuration. The config object
is in a similar syntax to the url query params, with one exception, `element`
which is the DOM id of where the block should be injected into. If there is no
block configuration, only the DOM id needs to be supplied. The Javascript SDK
requires a JSON format be enabled (this may become configurable in the future).
```html
<script type="text/javascript" src="http://example.com/modules/block_render/js/block-render.js"></script>
<script type="text/javascript">
  Drupal.block_render.render('http://example.com/block-render', {
    blocks: {
      BLOCK1: {
        element: 'domid',
        nun_pages: 5,
      },
      BLOCK2: 'domid2'
    },
    loaded: [
      'jquery'
    ]
  });
</script>
```

## Maintainers
Current maintainers:
* David Barratt ([davidwbarratt](https://www.drupal.org/u/davidwbarratt))

This project has been sponsored by:
* [Golf Channel](https://www.drupal.org/node/2374873)
  Founded by Arnold Palmer. Your 24/7 source for golf news, scores, instruction,
  tournaments & entertainment. Powered by
  [NBC Sports](https://www.drupal.org/node/2579397).
