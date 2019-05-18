# Meta FieldMappers

Within the elastic_search module most of the heavy lifting  is done by the FieldMapper plugins.
It is the responsibility of these plugins to

    * Provide any options for the Fieldable Entity Map form
    * Tell the Cartographer what mapping to send to elastic
    * Tell the ElasticPayloadRenderer how to flatten your field data at document index time.

All of the current field types in elastic have field mappers in the elastic_search plugin.

Elastic only understands the field types that belong to its specification, but obviously within drupal we have the opportunity to be far more flexible.
Because of this as well as implementing all the standard elastic field types we may need to introduce some additional processing. We do this through so called 'Meta FieldMappers'

An example of a meta field mapper is a [simple reference mapping type](../../../src/Plugin/FieldMapper/SimpleReference.php) , this type takes an entity_reference or entity_reference_revision and passes it to elastic as a simple id instead of inlining the referenced entities fields
'Under the hood' this is actually mapped as follows
```
  /**
   * An array of DSL that we use as a 'canned response' for when we have to return a simple reference
   *
   * @var array
   */
  public static $simpleReferenceDsl = [
    'type'                  => 'keyword',
    'boost'                 => 0,
    'doc_values'            => TRUE,
    'eager_global_ordinals' => FALSE,
    'include_in_all'        => TRUE,
    'index'                 => TRUE,
    'index_options'         => 'docs',
    'norms'                 => FALSE,
    'similarity'            => 'classic',
    'store'                 => FALSE,
  ];
```

And when the entity reference is passed to elastic it is processed as follows, take note of the handling of fields with multiple values, which need to be returned as an array

```
   /**
    * {@inheritdoc}
    *
    * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
    * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
    * @throws \Drupal\Core\DependencyInjection\ContainerNotInitializedException
    * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
    */
   public function normalizeFieldData(string $id,
                                      array $data,
                                      array $fieldMappingData) {

     if (!array_key_exists('nested', $fieldMappingData) || (int) $fieldMappingData['nested'] !== 1) {
       return !empty($data) ? $this->testTargetData($data[0]) : NULL;
     }

     //If nested then we need to pass back an array of values
     $out = [];
     foreach ($data as $datum) {
       if (!empty($datum)) {
         try {
           $out[] = $this->testTargetData($datum);
         } catch (\Throwable $t) {
           continue;
         }
       }
     }
     return !empty($out) ? $out : NULL;
   }
```

As FieldMappers are also responsible for exposing their config to the FieldableEntityMap forms if you needed to add any additional options or configurations you could do it here by overriding the `getFormFields` method.
The `FieldMapper\FormHelper` namespace contains some common fields in use on most elastic search types to help with this.