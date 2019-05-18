# Entity Access

Simplify per-bundle entity access checking for routes. Example: `_entity_access: <ENTITY_TYPE>[:<BUNDLE>].<OPERATION>`.

From the developer perspective, this module - just improvement of existing `access_check.entity` service. Default implementation - `\Drupal\Core\Entity\EntityAccessCheck` - overridden and empowered with possibility to verify entity bundle, if it specified. That's all.

## Example

For instance, you want to add a local task to the page of specific entity bundle. To do this you'll just need to define a new route (in `example.routing.yml`) and a task itself (in `example.links.task.yml`).

**example.routing.yml**:

```yml
example.route:
  path: '/admin/structure/types/manage/{node_type}/example-page'
  defaults:
    _title: 'Example page'
    _form: '\Drupal\example\Entity\NodeType\Form\ExamplePage'
  requirements:
    _entity_access: 'node_type:article.update'
```

**example.links.task.yml**:

```yml
example.route:
  title: 'Example task'
  base_route: entity.node_type.edit_form
  route_name: example.route
```

**IMPORTANT**: The `buildForm()` method of `\Drupal\example\Entity\NodeType\Form\ExamplePage` class MUST contain additional argument, NAMED EXACTLY as entity type (argument type can be ommited).

```php
/**
 * {@inheritdoc}
 */
public function buildForm(array $form, FormStateInterface $form_state, \Drupal\node\Entity\NodeType $node_type = NULL): array {
  return $form;
}
```

Page and local task as well will not be accessible if this requirement will be ignored.

### Additional examples

Check out the [entity_access_test](http://cgit.drupalcode.org/entity_access/tree/tests/modules/entity_access_test) module to get more examples.
