<?php

namespace Drupal\views_dynamic_entity_row\Entity\Render;

use Drupal\views\Entity\Render\TranslationLanguageRenderer;

/**
 * Renders entity translations in their row language.
 */
class DynamicViewModeTranslationLanguageRenderer extends TranslationLanguageRenderer {

  /**
   * {@inheritdoc}
   */
  public function preRender(array $result) {
    $view_builder = $this->view->rowPlugin->entityManager->getViewBuilder($this->entityType->id());

    /** @var \Drupal\views\ResultRow $row */
    foreach ($result as $row) {
      $entity = $row->_entity;
      $entity->view = $this->view;
      $langcode = $this->getLangcode($row);
      $view_mode = $this->view->rowPlugin->options['view_mode'];

      if ($dynamic_view_mode = \Drupal::service('views_dynamic_entity_row.manager')->getDynamicViewMode($entity)) {
        $view_mode = $dynamic_view_mode;
      }

      $this->build[$entity->id()][$langcode] = $view_builder->view($entity, $view_mode, $this->getLangcode($row));
    }
  }

}
