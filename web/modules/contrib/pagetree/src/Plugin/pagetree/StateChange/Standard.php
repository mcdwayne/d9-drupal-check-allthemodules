<?php
namespace Drupal\pagetree\Plugin\pagetree\StateChange;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\pagetree\Plugin\StateChangePluginBase;

/**
 * @StateChangeHandler(
 *   id = "standard",
 *   name = @Translation("Default render"),
 *   weight = 100
 * )
 */
class Standard extends StateChangePluginBase
{

    public function publish(ContentEntityBase &$entity, $message)
    {

        if (!$entity->isPublished()) {
            $entity->setPublished(true);
            $entity->save();
        } else {
            Cache::invalidateTags($entity->getCacheTags());
        }
    }

    public function unpublish(ContentEntityBase &$entity, $message)
    {
        if ($entity->isPublished()) {
            $entity->setPublished(false);
            $entity->save();
        } else {
            Cache::invalidateTags($entity->getCacheTags());
        }
    }

    public function copy(ContentEntityBase &$entity, ContentEntityBase &$clone = null)
    {
        if ($clone == null) {
            $clone = $entity->createDuplicate();
            $clone->save();
        }
        return $clone;
    }

    public function generate(ContentEntityBase &$entity)
    {

    }

    public function delete(ContentEntityBase &$entity)
    {

    }

    public function hasChanges(ContentEntityBase &$entity)
    {
        return false;
    }

}
