<?php


    
    namespace Drupal\vk_crosspost\Plugin\Field\FieldType;
 
    use Drupal\Core\Field\FieldItemBase;
    use Drupal\Core\Field\FieldStorageDefinitionInterface;
    use Drupal\Core\TypedData\DataDefinition;
    use Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem;
    use Drupal\Core\TypedData\OptionsProviderInterface;
     
    /**
     * @FieldType(
     *   id = "my_logic_field",
     *   label = @Translation("pole for cross in VK"),
     *   description = @Translation("This field stores a reference to a user and a password for this user on the entity."),
     *   default_widget = "vk_crosspostWidget",
     *   default_formatter = "vk_crosspostFormatter",  
     * )
     */
   
    class LogicField extends BooleanItem implements OptionsProviderInterface {

 public static function schema (FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'int',
          'size' => 'tiny',
        ],
      ],
    ];
  }
      
 public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
     $properties['value'] = DataDefinition::create('boolean')
    ->setLabel(t('Boolean value'))
    ->setRequired(TRUE);
    // var_dump($properties);

  return $properties; 
      }

public function preSave() {
parent::preSave();
$z = $this->getFieldDefinition()->getName();
//$z = substr($z ,6);
$_SESSION['z'] = $z;
//kint($z);
}

}

