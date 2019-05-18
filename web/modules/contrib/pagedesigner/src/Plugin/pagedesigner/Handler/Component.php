<?php
namespace Drupal\pagedesigner\Plugin\pagedesigner\Handler;

use Drupal\pagedesigner\Entity\Element;
use Drupal\pagedesigner\Service\Renderer;

/**
 * @PagedesignerHandler(
 *   id = "component",
 *   name = @Translation("Component renderer"),
 *   types = {
 *     "component",
 *   },
 * )
 */
class Component extends Structural
{
    public function patch(Element $entity, $data)
    {
        $build = parent::patch($entity, $data);
        $build['type'] = 'component';
        return $build;
    }
    public function serialize(Element $entity)
    {
        $build = parent::serialize($entity);
        $build['type'] = 'component';
        return $build;
    }
}
