<?php
namespace Drupal\pagedesigner\Plugin\pagedesigner\Handler;

use Drupal\pagedesigner\Entity\Element;
use Drupal\pagedesigner\Plugin\pagedesigner\Handler\Standard;
use Drupal\ui_patterns\Definition\PatternDefinitionField;

/**
 * @PagedesignerHandler(
 *   id = "hide",
 *   name = @Translation("Hide handler"),
 *   types = {
 *      "hide"
 *   },
 * )
 */
class Hide extends Standard
{

    /**
     * {@inheritDoc}
     */
    public function prepare(PatternDefinitionField &$field, &$fieldArray)
    {
        parent::prepare($field, $fieldArray);
    }

    /**
     * {@inheritDoc}
     */
    public function serialize(Element $entity)
    {
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function get(Element $entity)
    {
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function patch(Element $entity, $data)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function generate($definition, $data)
    {
        return parent::generate(['type' => 'content', 'name' => 'hide'], $data);
    }
}
