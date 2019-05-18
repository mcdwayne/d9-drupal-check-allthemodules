# 200WAD API
API client for 200 Words a day. https://200wordsaday.com/makers 

## Configuration

### Get your personal access token

from your profile page: https://200wordsaday.com/settings.


### Drupal configuration

Add your API token and the API base url in /admin/config/m200wad-api

## Usage

This module provides a m200wad_api.client service that can be used in Drupal hooks or in Classes using Dependency Injection.

The `connect` method accepts the following parameters:

```
   * @param string $method
   *   get, post, patch, delete, etc. See Guzzle documentation.
   * @param string $endpoint
   *   The 200WAD API endpoint (ex. api/texts)
   * @param array $query
   *   Query string parameters the endpoint allows
   * @param array $body
   *   Content and other payload
```
   
### Drupal hook_ example:

```
function yourmodule_node_insert(NodeInterface $node) {

  if($node->getType() == 'blog') {
    $categories = [];
    foreach($node->get('field_category')->referencedEntities() as $item) {
      $categories[] = '#' . $item->getName();
    }

    $query_params = [];
    $body = [
      'title' => $node->getTitle(),
      'content' => $node->body->getValue()[0]['value'],
      'access_rights' => 'public',
      'status' => 'published',
      'categories' => $categories,
      'canonical_url' => $node->toUrl()->setAbsolute()->toString()
    ];

    // Guzzle response has an exception.
    try {
      $api = Drupal::service('m200wad_api.client');
      $result = $api->connect('post', 'api/texts', $query_params, $body);
    }
    catch (\Exception $e) {
      Drupal::logger('m200wad_api')->error('Exception. The 200 Word a day post could not be created.');
      return FALSE;
    }

}
```
