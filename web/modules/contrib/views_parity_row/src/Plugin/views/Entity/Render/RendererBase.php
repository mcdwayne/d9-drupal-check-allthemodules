<?php

declare(strict_types = 1);

namespace Drupal\views_parity_row\Plugin\views\Entity\Render;

use Drupal\views\Entity\Render\RendererBase as ViewsRendererBase;

/**
 * Renders entities in the current language.
 */
abstract class RendererBase extends ViewsRendererBase {

  /**
   * {@inheritdoc}
   */
  public function preRender(array $result) {
    $view_builder = $this->view->rowPlugin->entityManager->getViewBuilder($this->entityType->id());
    $previous_pages_item_count = $this->view->pager->getCurrentPage() * $this->view->pager->getItemsPerPage();
    $options = $this->view->rowPlugin->options;

    /** @var \Drupal\views\ResultRow $row */
    foreach ($result as $row) {
      $entity = $row->_entity;

      $view_mode = $options['view_mode'];

      if ($options['views_parity_row_enable']) {
        $view_mode_override = FALSE;
        $current_item = $previous_pages_item_count + $row->index;
        if ($current_item >= $options['views_parity_row']['start']) {
          if ($options['views_parity_row']['end'] !== '0') {
            if ($current_item <= $options['views_parity_row']['end']) {
              $view_mode_override = TRUE;
            }
          }
          else {
            $view_mode_override = TRUE;
          }
        }

        if ($view_mode_override) {
          if (($current_item - $options['views_parity_row']['start']) % $options['views_parity_row']['frequency'] === 0) {
            $view_mode = $options['views_parity_row']['view_mode'];
          }
        }
      }

      $this->build[$entity->id()] = $view_builder->view($entity, $view_mode, $this->getLangcode($row));
    }
  }

}
