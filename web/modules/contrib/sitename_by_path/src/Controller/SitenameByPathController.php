<?php

namespace Drupal\sitename_by_path\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\sitename_by_path\SitenameByPathStorage;
use Drupal\Core\Url;

/**
 * Controller for Sitename By Path.
 */
class SitenameByPathController extends ControllerBase {

  /**
   * Render a list of entries in the database.
   */
  public function entryList() {

    // Setup page links.
    $url = Url::fromRoute('sbp_add');
    $content['action_links'] = [
      '#markup' => \Drupal::l($this->t('Add Item'), $url),
    ];

    // Initialize table variables.
    $headers = [
      $this->t('Path'),
      $this->t('Sitename'),
      $this->t('Frontpage URL'),
      '',
      '',
    ];

    // Loop through all entries and setup table data.
    $entries = SitenameByPathStorage::load();
    foreach ($entries as $entry) {
      $row = [];

      // Setup table data.
      $row[] = $entry->path;
      $row[] = $entry->sitename;
      $row[] = $entry->frontpage;
      $url   = Url::fromRoute('sbp_update', ['sbp_id' => $entry->id]);
      $row[] = \Drupal::l($this->t('Edit'), $url);
      $url   = Url::fromRoute('sbp_delete', ['sbp_id' => $entry->id]);
      $row[] = \Drupal::l($this->t('Delete'), $url);

      // Insert row into render table array.
      $rows[] = $row;
    }

    // Render table.
    $content['table'] = [
      '#type'   => 'table',
      '#header' => $headers,
      '#rows'   => $rows,
      '#empty'  => $this->t('No entries available.'),
    ];

    $content['message'] = [
      '#markup' => $this->t('Bottom items weigh most.'),
    ];

    // Don't cache this page.
    $content['#cache']['max-age'] = 0;

    return $content;

  }

}
