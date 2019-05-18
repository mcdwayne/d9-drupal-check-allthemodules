<?php /**
 * @file
 * Contains \Drupal\record_shorten\Controller\DefaultController.
 */

namespace Drupal\record_shorten\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Default controller for the record_shorten module.
 */
class DefaultController extends ControllerBase {
  /**
   * Page build for Record Shorten
   */
  public function page() {
    $build = [];
    $total = db_query("SELECT COUNT(sid) FROM {record_shorten}")->fetchField();

    $build['summary']['#markup'] = '<p>' . \Drupal::translation()->formatPlural($total, '1 shortened URL has been recorded.', '@count shortened URLs have been recorded.');

    $build['records_table']['#markup'] = record_shorten_records_table();

    $form = \Drupal::formBuilder()->getForm('Drupal\record_shorten\Form\RecordshortenClearAll');
    $build['clear']['#markup'] = \Drupal::service("renderer")->render($form);

    return $build;
  }
}
