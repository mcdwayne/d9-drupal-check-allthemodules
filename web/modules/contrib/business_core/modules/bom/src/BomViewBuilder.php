<?php

namespace Drupal\bom;

use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Url;

/**
 * View builder handler for BOMs.
 */
class BomViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    /** @var \Drupal\bom\BomInterface[] $entities */
    if (empty($entities)) {
      return;
    }

    parent::buildComponents($build, $entities, $displays, $view_mode);

    foreach ($entities as $id => $entity) {
      $bundle = $entity->bundle();
      $display = $displays[$bundle];

      if ($display->getComponent('components')) {
        $build[$id]['components'] = [
          '#type' => 'details',
          '#title' => $this->t('Components'),
          '#open' => TRUE,
          'add' => [
            '#type' => 'link',
            '#title' => $this->t('Add component'),
            '#url' => Url::fromRoute('bom.bom_component.add_form',
              ['bom' => $entity->id()],
              ['class' => ['button']]
            ),
          ],
          'components' => views_embed_view('bom_component'),
        ];
      }
    }
  }

}
