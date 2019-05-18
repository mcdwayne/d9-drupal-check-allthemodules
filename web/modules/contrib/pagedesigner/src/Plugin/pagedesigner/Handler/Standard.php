<?php
namespace Drupal\pagedesigner\Plugin\pagedesigner\Handler;

use Drupal\pagedesigner\Entity\Element;
use Drupal\pagedesigner\Plugin\HandlerPluginBase;
use Drupal\ui_patterns\Definition\PatternDefinitionField;

/**
 * @PagedesignerHandler(
 *   id = "standard",
 *   name = @Translation("Default render"),
 *   types = {
 *      "*",
 *   },
 * )
 */
class Standard extends HandlerPluginBase
{

    public function renderForPublic(Element $entity)
    {
        return parent::renderForPublic($entity);
    }

    public function render(Element $entity)
    {
        return parent::render($entity);
    }

    public function renderforEdit(Element $entity)
    {
        return parent::renderForEdit($entity);
    }

    /**
     * {@inheritDoc}
     */
    public function prepare(PatternDefinitionField &$field, &$fieldArray)
    {
        parent::prepare($field, $fieldArray);
    }

    public function serialize(Element $entity)
    {
        return $this->get($entity);
    }

    /**
     * {@inheritDoc}
     */
    public function get(Element $entity)
    {
        return parent::get($entity);
    }

    /**
     * {@inheritDoc}
     */
    public function patch(Element $entity, $data)
    {
        return parent::patch($entity, $data);
    }

    /**
     * {@inheritDoc}
     */
    public function generate($definition, $data)
    {
        $element = parent::generate($definition, $data);
        $element->parent->target_id = $data['parent'];
        $element->container->target_id = $data['container'];
        $element->field_placeholder->value = $data['placeholder'];
        if (!empty($data)) {
            $this->patch($element, $data);
        } else {
            $element->saveEdit();
        }
        return $element;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Element $entity)
    {
        parent::delete($entity);
    }

    public function publish(Element $entity)
    {
        return parent::publish($entity);
    }

    public function unpublish(Element $entity)
    {
        return parent::publish($entity);
    }
}
