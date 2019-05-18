<?php

/**
 * @file
 * Contains \Drupal\page_message\PagemessageController.
 */

namespace Drupal\page_message;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Controller for Page Message.
 */
class PagemessageController extends ControllerBase {

  /**
   * Render a list of entries in the database.
   */
  public function messages() {
    $content = array();

    $content['message'] = array(
      '#markup' => $this->t('Page Messages.'),
    );

    $rows = array();
    $headers = array(t('Page URL'), t('Message'), t('Edit'), t('Created'), t('Updated'));

    foreach ($entries = PagemessageStorage::load() as $entry) {

      // Sanitize each entry.
      $entry = array_map('Drupal\Component\Utility\SafeMarkup::checkPlain', (array) $entry);

      // The order of the elements here has to match $headers.
      $display_entry = array();
      $display_entry[] = $entry['page'];
      $display_entry[] = $entry['message'];

      // Add the Edit link to the array.
      $link = '/admin/config/user-interface/page_message/add_update?pmid=' . $entry['pmid'];
      $tag = '<a href="' . $link . '">Edit</a>';
      $markup = \Drupal\Core\Render\Markup::create($tag);
      $display_entry[] = $markup;

      $display_entry[] = $entry['created'];

      // Adjust entries that have never been updated.
      $display_entry[] = ($entry['updated'] == '1969-12-31 19:00:00') ? '-' : $entry['updated'];

      // Add the array to the list of rows.
      $rows[] = $display_entry;
    }
    $content['table'] = array(
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => t('No entries available.'),
    );
    // Don't cache this page.
    $content['#cache']['max-age'] = 0;

    return $content;
  }


}
