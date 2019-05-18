<?php
namespace Drupal\pagedesigner\Plugin\pagedesigner\Handler;

use Drupal\pagedesigner\Entity\Element;
use Drupal\ui_patterns\Definition\PatternDefinitionField;

/**
 * @PagedesignerHandler(
 *   id = "select",
 *   name = @Translation("Select processor"),
 *   types = {
 *      "select",
 *   }
 * )
 */
class Select extends Standard
{
    /**
     * {@inheritDoc}
     */
    public function prepare(PatternDefinitionField &$field, &$fieldArray)
    {
        parent::prepare($field, $fieldArray);
        foreach ($field->toArray()['options'] as $key => $option) {
            if (is_string($option)) {
                $option = t($option);
            }
            $fieldArray['options'][$key] = $option;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function render(Element $entity)
    {
        return $this->get($entity);
    }

    /**
     * {@inheritDoc}
     */
    public function get(Element $entity)
    {
        return $entity->field_content->value;
    }

    /**
     * {@inheritDoc}
     */
    public function generate($definition, $data)
    {
        return parent::generate(['type' => 'content', 'name' => 'select'], $data);
    }
}
