# Entity Router

Lookup entities by their paths/redirects and convert to a given format.

## Use

```bash
curl -X GET '/entity/router?path=<PATH>&format=<FORMAT>'
```

- The value of the `<PATH>` can be an internal path of an entity, the redirect or its alias.

  Examples:

  - `/node/1` - the internal path;
  - `/my-article` - the alias;
  - `/my-article-old` - the old alias that redirects to the current.

- A value of the `<FORMAT>` should be one of request formats that are provided by `EntityResponseHandler` plugins (`jsonapi` handled out of the box).

## Extend

- Define the format handler as a plugin.

  ```php
  use Drupal\entity_router\EntityResponseHandlerInterface;

  /**
   * @EntityResponseHandler(
   *   id = "html",
   * )
   */
  class HtmlEntityResponseHandler implements EntityResponseHandlerInterface {
  }
  ```

- Subscribe to the response event - `\Drupal\entity_router\Event\EntityResponseEvent`.
- Modify the list of response handlers by `hook_entity_response_handler_plugins_alter()`.

## Alternative

- https://www.drupal.org/project/decoupled_router
