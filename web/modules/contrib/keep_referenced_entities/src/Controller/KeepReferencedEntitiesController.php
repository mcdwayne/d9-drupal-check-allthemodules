<?php

namespace Drupal\keep_referenced_entities\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\keep_referenced_entities\KeepReferencedEntitiesManager;

/**
 * Class KeepReferencedEntitiesController
 */
class KeepReferencedEntitiesController extends ControllerBase {

  /**
   * Callback to show list of referenced entities.
   */
  public function showReferencesList($entity_type, $entity_id) {
    try {
      if ($entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id)) {
        $kre_manager = new KeepReferencedEntitiesManager($entity);
        $content = new FormattableMarkup($kre_manager->getReferencesList(), []);
      }
      else {
        $content = new FormattableMarkup($this->t('Entity cannot be loaded.'), []);
      }
      return [
        '#markup' => $content,
      ];
    }
    catch (\Exception $e) {
      throw new \Exception('Entity cannot be loaded.');
    }
  }
}
