# Dependency Retriever

This module provides an alternative class factory and dependency injection 
method.

The `retriever.factory` service
(\BartFeenstra\DependencyRetriever\Factory\Factory) can instantiate any class, 
provided that its dependencies (constructor arguments) have default values, 
overridden values at instantiation time, or suggested dependencies.

## Dependency suggestions
Dependencies can be suggested through `@suggestedDependency` annotations as in
the following example:

```php
class ClassWithSuggestedDependencies
{

    /**
     * Constructs a new instance.
     *
     * @suggestedDependency drupalContainerParameter:filter_protocols $filter_protocols
     * @suggestedDependency drupalContainerService:entity_type.manager $entity_type_manager
     *
     * @param string[] $filter_protocols
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
     */
    public function __construct(array $filter_protocols, EntityTypeManagerInterface $entity_type_manager)
    {
    }
}
```

This module provides the following dependency retrievers:

- `drupalContainerParameter` to retrieve container parameters. Dependency IDs 
  are parameter names.
- `drupalContainerService` to retrieve container services. Dependency IDs are 
  service IDs.
- `drupalEntityTypeHandler` to retrieve entity type handlers. Dependency IDs are
  entity type IDs, handler types, and handler operations (optional) concatenated
  by periods, such as `node.form.edit` or `user.access`.
