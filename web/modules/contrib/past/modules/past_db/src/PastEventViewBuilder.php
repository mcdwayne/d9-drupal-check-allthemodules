<?php

namespace Drupal\past_db;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Url;
use Drupal\past_db\Entity\PastEvent;

/**
 * Render controller for taxonomy terms.
 */
class PastEventViewBuilder extends EntityViewBuilder {
  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    parent::buildComponents($build, $entities, $displays, $view_mode);

    foreach ($entities as $id => $entity) {
      /** @var PastEvent $entity */

      // Global information about the event.
      $build[$id]['actor'] = [
        '#type' => 'item',
        '#title' => t('Actor'),
      ];
      $build[$id]['actor'][] = $entity->getActorDropbutton(FALSE);

      // Output URLs as links.
      if ($entity->getReferer()) {
        $build[$id]['referer'][0] = [
          '#markup' => \Drupal::l($entity->getReferer(), Url::fromUri($entity->getReferer())),
        ];
      }
      if ($entity->getLocation()) {
        $build[$id]['location'][0] = [
          '#markup' => \Drupal::l($entity->getLocation(), Url::fromUri($entity->getLocation())),
        ];
      }

      // @todo Display as vertical_tabs if that is enabled outside forms.
      foreach ($entity->getArguments() as $key => $argument) {
        $build[$id]['fieldset_' . $key] = $entity->formatArgument($key, $argument);
      }

    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
    $build = parent::getBuildDefaults($entity, $view_mode);
    // There is no template, unset it to avoid a watchdog notice.
    unset($build['#theme']);
    return $build;
  }

}
