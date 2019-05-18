## Entity delete operations

Provides the capabilities of performing "soft" delete operations and restoration in Drupal 8.

### License

This Drupal module is licensed under the [GNU General Public License](./LICENSE.md) version 2.

### Setup

This is an API module that must be implemented by entity types that wish to support it.

* Add a "deleted" entity key
* Add `entity_delete_op = true` to the entity annotation
* Entity class should implement `EntityDeletableInterface` and `use EntityDeletableTrait`
* If entity types declare their own delete permissions, users must be granted that permission as well as the entity_delete_op permission for the entity type.