<?php
namespace Drupal\pagedesigner\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the Pagedesigner content field type.
 *
 * @FieldType(
 *   id = "pagedesigner_item",
 *   label = @Translation("Pagedesigner content"),
 *   module = "pagedesigner",
 *   description = @Translation("Displays the pagedesigner content for this node."),
 *   default_widget = "pagedesigner_widget",
 *   default_formatter = "pagedesigner_formatter"
 * )
 */
class PagedesignerItem extends FieldItemBase implements FieldItemInterface
{

    /**
     * {@inheritdoc}
     */
    public static function schema(FieldStorageDefinitionInterface $field_definition)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        return false;
        $value = $this->get('value')->getValue();
        return $value == null || empty($value);
    }

    /**
     * {@inheritdoc}
     */
    public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition)
    {
        $properties = [];
        $properties['content'] = DataDefinition::create('pagedesigner_item_data')
            ->setLabel(new TranslatableMarkup('Pagedesigner content'))
            ->setComputed(true)
            ->setClass('\Drupal\pagedesigner\Plugin\DataType\PagedesignerData')
            ->setInternal(false);
        return $properties;
    }
}
