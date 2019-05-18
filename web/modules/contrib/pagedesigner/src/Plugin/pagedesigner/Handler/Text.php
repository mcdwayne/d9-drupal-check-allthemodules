<?php
namespace Drupal\pagedesigner\Plugin\pagedesigner\Handler;

use Drupal\pagedesigner\Entity\Element;

/**
 * @PagedesignerHandler(
 *   id = "text",
 *   name = @Translation("Text renderer"),
 *   types = {
 *     "text",
 *   },
 * )
 */
class Text extends Content
{
/**
     * {@inheritdoc}
     */
    public function render(Element $entity)
    {
        return strip_tags($this->get($entity));
    }

    /**
     * {@inheritdoc}
     */
    public function get(Element $entity)
    {
        return $entity->field_content->value;
    }
}
