<?php

namespace Drupal\contest;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Render controller for contests.
 */
class ContestViewBuilder extends EntityViewBuilder {

  /**
   * The view render controller for a contest.
   *
   * @param Drupal\Core\Entity\EntityInterface $contest
   *   A contest entity.
   * @param string $view_mode
   *   The view mode.
   * @param string $langcode
   *   The language code.
   *
   * @return array
   *   An array with the contest's view render settings.
   */
  public function view(EntityInterface $contest, $view_mode = 'full', $langcode = NULL) {
    $view = [
      '#contest' => $contest,
      'contest'  => [
        '#create_placeholder' => TRUE,
        '#cache'              => ['tags' => $contest->getCacheTags()],
        '#lazy_builder'       => [
          'contest.post_render_cache:renderViewForm',
          [
            'id'        => $contest->id(),
            'view_mode' => $view_mode,
          ],
        ],
      ],
    ];
    return $view;
  }

}
