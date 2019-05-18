<?php

namespace Drupal\entity_split;

use Drupal\content_translation\ContentTranslationHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Defines the translation handler for entity_split.
 */
class EntitySplitTranslationHandler extends ContentTranslationHandler {

  /**
   * {@inheritdoc}
   */
  public function entityFormAlter(array &$form, FormStateInterface $form_state, EntityInterface $entity) {
    parent::entityFormAlter($form, $form_state, $entity);

    unset($form['actions']['delete']);
    unset($form['actions']['delete_translation']);

    if (isset($form['content_translation'])) {
      $form['content_translation']['#access'] = FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslationAccess(EntityInterface $entity, $op) {
    if ($op === 'delete') {
      return AccessResult::forbidden();
    }

    return parent::getTranslationAccess($entity, $op);
  }

}
