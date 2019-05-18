<?php
namespace Drupal\pagedesigner_pagetree\Plugin\pagetree\StateChange;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\pagetree\Plugin\pagetree\StateChange\Standard;

/**
 * @StateChangeHandler(
 *   id = "pagedesigner",
 *   name = @Translation("Pagedesigner publisher"),
 *   weight = 200
 * )
 */
class Pagedesigner extends Standard
{


    public function publish(ContentEntityBase &$entity, $message)
    {
        \Drupal::service('pagedesigner.service.statechanger')->publish($entity);
    }

    public function unpublish(ContentEntityBase &$entity, $message)
    {
        \Drupal::service('pagedesigner.service.statechanger')->unpublish($entity);
    }

    public function copy(ContentEntityBase &$entity, ContentEntityBase &$clone = null)
    {
        \Drupal::service('pagedesigner.service.statechanger')->copy($entity, $clone);
        return $clone;
    }

    public function generate(ContentEntityBase &$entity)
    {
        \Drupal::service('pagedesigner.service.statechanger')->generate($entity);
    }

    public function delete(ContentEntityBase &$entity)
    {
        \Drupal::service('pagedesigner.service.statechanger')->delete($entity);
    }

}
