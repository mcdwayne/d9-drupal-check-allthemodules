# Entity resource layer

Provides plugins that can operate above the default core REST resource 
plugins specifically for entities. By this allows you to easily serve 
and create your entities the way that you want. But this is not all,
read on!

This module is provided solely for developers and has no use for end 
users as is. The documentation below assumes you have a basic 
knowledge and experience with the 
[core REST API](https://www.drupal.org/docs/8/api/restful-web-services-api/restful-web-services-api-overview).

### Features
Through the plugin are provided most features:
* ability to target the functionality for entities per bundle and more
* alteration of inbound requests or outbound responses
* pre and post reacting per REST methods
* automatic and manual field name mapping (alias <-> machine name)
* exclusion / inclusion of fields, protection of sensitive data
* data focusing: return one value of the entity without object format
* automatic embedding of defined referenced entities / reference fields
* custom access checking per REST method
* custom entity validations for POST and PATCH
* provision for custom additional paths for endpoints

But there are features also outside of the plugins like:
* improved validation error responses that contain more information
* multiple validation errors sent at once
* possibility for API versioning
* removed unnecessary nesting of response values, clean responses
* improved and possibly separated REST logging

### Requirements
The only requirement, obviously, the core **rest** module.

### Configuration
There are no extra configurations for REST. The entity endpoints are 
as usual enabled and configured from the 
**Configuration > Web Services > Rest**. Once any endpoint for one 
entity is open then all resource layer plugins for that entity will 
apply.

### Getting started
Most of the information you need are found in the 
[EntityResourceLayer](src/Annotation/EntityResourceLayer.php) 
annotation. From here you should start and read carefully each value 
what represents and what it can contain.

Once you've done your research you can start implementing your 
resource layer plugins under **src/Plugins/Resource**. 

#### Usage with views
Note that these plugins also apply per serialization basis, which allows 
them to be used with views as-well. Make sure when rendering the entities
from the view you select to display as entity and not fields.

#### Entity reference embedding
You can embed entities that are referenced through fields of the current
entity. This embedded entity can also have it's resource layer plugin in
turn and be formatted the way you want. This allows for infinite nesting
and customization options.

#### Resource layer selection
You can have multiple layers for same entity/bundle. These in turn can
have different priorities and in that order are applied. Note that resource 
plugin application can also be filtered upon path. This allows for custom
representations of the same entity in different contexts.

#### Custom entity validations
Before using custom validations you should check the defined exceptions
that this module provides. The basic idea is that validations errors
are created with exceptions that are transformed to responses. These
exceptions are based on constraints. You can also provide your
constraint information for custom exception of this type.

The module provides 
[an exception that is multiple](src/Exception/EntityResourceMultipleException.php). 
This allows for **all validation errors to be returned** to the consumer 
at once.

### Example plugin
```php
<?php
namespace Drupal\my_module\Plugin\Resource;
use ...

/**
 * Resource adapter for user entities.
 *
 * @EntityResourceLayer(
 *   id = "user",
 *   entityType = "user",
 *   additionalPath = "/users/{user}",
 *   routes = {
 *    "[rest\.entity\.user\..*]",
 *    "users.api_search",
 *   },
 *   sensitiveFields = {"password"},
 *   fieldsOnly = {
 *    "uid",
 *    "preferred_langcode",
 *    "roles",
 *    "field_name",
 *    "field_surname",
 *    "field_gender",
 *   },
 *   camelFields = FALSE,
 *   fieldMap = {
 *    "uid" = "id",
 *    "mail" = "email",
 *   },
 * )
 */
class UserResourceLayer extends EntityResourceLayerBase {

  /**
   * Allowed roles to be set on user.
   */
  const ALLOWED_ROLES = [...];

  /**
   * {@inheritdoc}
   */
  public function adaptOutgoing(array $data, FieldableEntityInterface $entity) {
    unset($data['id']);
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function adaptIncoming(array $data) {
    $data['email'] = trim($data['email']);
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function beforePost(FieldableEntityInterface $entity) {
    $entity->set('pass', user_password());
  }

  /**
   * {@inheritdoc}
   */
  public function beforePatch(FieldableEntityInterface $originalEntity, FieldableEntityInterface $updatedEntity = NULL) {
    /** @var \Drupal\user\UserInterface $updatedEntity */
    if (!array_intersect(static::ALLOWED_ROLES, $updatedEntity->getRoles())) {
      $updatedEntity->addRole('some_role');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function reactPost(Response $response, FieldableEntityInterface $entity) {
    _user_mail_notify('register_admin_created', $entity);
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(FieldableEntityInterface $entity) {
    $violations = new EntityResourceMultipleException();
    foreach ($entity->get('roles')->getValue() as $value) {
      if (in_array($value['target_id'], static::ALLOWED_ROLES)) {
        continue;
      }

      $violations->addException((new EntityResourceFieldException($this->t('You cannot set this role for the user.'), 'roles', 'FIELD_INV_VALUES'))
        ->addCustomConstraints(['choices' => static::ALLOWED_ROLES]));
    }
    
    if ($violations->hasException()) {
      throw $violations;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(FieldableEntityInterface $user, $operation) {
    if ($operation != 'POST' && $user->get('status')->getString() == '0') {
      return new AccessResultForbidden();
    }

    return new AccessResultNeutral();
  }

}

```
