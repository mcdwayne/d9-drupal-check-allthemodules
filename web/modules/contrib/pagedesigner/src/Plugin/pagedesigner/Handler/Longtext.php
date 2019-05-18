<?php
namespace Drupal\pagedesigner\Plugin\pagedesigner\Handler;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Render\Markup;
use Drupal\pagedesigner\Entity\Element;

/**
 * @PagedesignerHandler(
 *   id = "longtext",
 *   name = @Translation("Longtext renderer"),
 *   types = {
 *     "textarea",
 *     "longtext"
 *   },
 * )
 */
class Longtext extends Content
{
    /**
     * {@inheritdoc}
     */
    public function get(Element $entity)
    {
        return Markup::create($entity->field_content->value);
    }
}
