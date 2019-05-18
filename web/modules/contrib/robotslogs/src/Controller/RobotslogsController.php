<?php

namespace Drupal\robotslogs\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides output robots.txt output.
 */
class RobotslogsController {
  /**
   * Serves the configured robots.txt file.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The robots.txt file as a response object with 'text/plain' content type.
   */

  /**
   * Implemets robotslogsFileRevision().
   *
   * Download robots.txt file.
   *
   * @param int $id
   *   Log ID.
   */
  public static function robotslogsFileRevision($id) {
    if (empty($id) || !is_numeric($id)) {
      drupal_set_message(t('Invalid Log Id'), 'error');
      $response = new RedirectResponse('/admin/config/search/robotstxt');
      $response->send();
    }
    $query = \Drupal::database()->select('robotslogs', 'rl');
    $query->addField('rl', 'content');
    $query->condition('id', $id);
    $data = $query->execute()->fetchField();
    if (empty($data)) {
      drupal_set_message(t('Invalid Entry'), 'error');
      $response = new RedirectResponse('/admin/config/search/robotstxt');
      $response->send();
    }
    header("Content-type: text/plain");
    header("Content-Disposition: attachment; filename=RobotsTxt_V" . $id . ".txt");
    print $data;
    exit();
    $response = new RedirectResponse('/admin/config/search/robotstxt');
    $response->send();
  }

  /**
   * Implemets robotslogsFileRestore().
   *
   * View previous version of robots.txt file.
   *
   * @param int $id
   *   Log ID.
   */
  public static function robotslogsFileRestore($id) {
    $query = \Drupal::database()->select('robotslogs', 'rl');
    $query->addField('rl', 'content');
    $query->condition('id', $id);
    $content = $query->execute()->fetchField();

    drupal_set_message(t('View of Version ' . $id . ' shown. Click on "Save Configuration" to Restore.'), 'warning');

    return $content;
  }

}
