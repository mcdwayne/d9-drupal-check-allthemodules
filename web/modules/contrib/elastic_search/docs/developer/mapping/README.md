# Mappings

The main additions that a developer may wish to extend the elastic_search module are around the mapping section. This largely involves implementing plugins, or events.

## Entities

Custom Entities may need to implement an ElasticEnabledEntity plugin. This is mainly implemented as a way to map a parent 'type' to the child, so that forms can be shown in the correct places in the drupal interface without excessive routing code.

These plugins are only necessary if you need to display a mapping page on a type which is not directly mapped. This is more easily explained with an example

You wish to map the fields of an entity type 'node_type' bundle type 'Article' in elastic search. This means that you will be indexing entities of type 'node'
Therefore you wish for the configuration page to appear on the 'node_type' configuration display and not the nodes themselves, as mapping concern the entity type and not each piece of content.

See [Custom Entity Support](CustomEntitySupport.md) for more

## Custom Fields

Custom Field types must be mapped to one of the Elastic Search field types before they can be added to a fieldable entity map.
This is done by implementing an EventSubscriber of type FieldMapper and adding the field name to the supported field array.

See [Custom Field Support](CustomFieldSupport.md) for more

## 'Meta' Field Mappers

You may need to implement a custom field mapper so that you can add custom elastic mapping or normalization to a fields data.
This is usually useful if your field type is non-atomic or needs additional pre-processing before it is sent to elastic.
We call these 'Meta Field Mappers' because they do not directly relate to an elastic field type.

You can see an example of this in [SimpleReference](../../src/Plugin/FieldMapper/SimpleReference.php)

See [Meta FieldMappers](MetaFieldMappers.md) for more

# Entity Type Definitions

The final plugin type is an entity type definition. These allow us to exclude certain fields from being shown in the FieldableEntityMap form and mapped.

See [Entity Type Definitions](EntityTypeDefinitions.md) for more
