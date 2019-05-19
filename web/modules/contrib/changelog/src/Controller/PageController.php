<?php

namespace Drupal\changelog\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\changelog\Entity\ChangelogEntity;

/**
 * Class PageController.
 */
class PageController extends ControllerBase {

  /**
   * View a tabled list of changes.
   *
   * @return array
   *   Markup for the page.
   */
  public function view() {
    $out = [];
    $entities = ChangelogEntity::getLogEntries();
    $table_data = [];
    /** @var \Drupal\changelog\Entity\ChangelogEntity $entity */
    foreach (ChangelogEntity::loadMultiple($entities) as $entity) {
      $time = \Drupal::service('date.formatter')->format($entity->getCreatedTime(), 'html_date');
      $entry = $entity->getLogValue();
      $entry = str_replace(['<p>', '</p>'], '', $entry);
      $table_data[] = [
        ['data' => $time],
        ['data' => ['#markup' => $entry]],
      ];
    }
    $out['table'] = [
      '#type' => 'table',
      '#header' => [t('Date'), t('Changed')],
      '#rows' => $table_data,
    ];
    if (\Drupal::moduleHandler()->moduleExists('git_info')) {
      $git = \Drupal::service('git_info.git_info');
      $out['git_info']['#markup'] =
        t('<p>Current version information: %v</p>', ['%v' => $git->getApplicationVersionString()]);
    }
    return $out;
  }

}
