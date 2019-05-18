# JSON API aliases

Use Drupal path aliases for JSON API requests.

## Dependencies

- [drupal/path](https://www.drupal.org/docs/8/core/modules/path/overview) (part of core)
- [drupal/jsonapi](https://www.drupal.org/project/jsonapi)

## How to use the JSON API aliases module

1. Enable JSON API aliases module.

2. Create a basic page with url alias(e.g. "/aboutus").

3. Json data can be accessible by GET request "/api/aboutus" with header "content-type: application/vnd.api+json"

4. Refer [JSON API](https://www.drupal.org/node/2806623) documentation for restricting fields in response.