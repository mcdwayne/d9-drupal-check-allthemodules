# Entity Type Definitions

Entity type definitions allow us to restrict some fields from being added to the fieldable entity map. This allows us to remove some un-necessary or un-needed fields from our indices.

The generic implementation of this plugin will simply return all fields from your entity type

```
public function getFieldDefinitions(string $entityType, string $bundleType) {
    return $this->entityFieldManager->getFieldDefinitions($entityType,
                                                          $bundleType);
}
```

however if you wish to restrict this to a specific subset you can implement your own logic in this function to provide the wanted field list.
For example for TaxonomyTerms we only return the following fields
```
protected function allowedFields(): array {
    return ['name', 'description', '/field_\w*/'];
}
```
In this case we are putting this data into the `allowedFields()` method because this plugin also uses a trait `use FieldFilterTrait;`.
This Trait allows us to filter our definitions for specific field names, including the use of regex patterns