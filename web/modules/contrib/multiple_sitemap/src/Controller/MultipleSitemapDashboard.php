<?php
/**
 * @file
 * Contains \Drupal\multiple_sitemap\Controller\MultipleSitemapDashboard.
 */

namespace Drupal\multiple_sitemap\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Returns responses for MultipleSitemapDashboard.
 */
class MultipleSitemapDashboard extends ControllerBase {
  function multipleSitemapDashboard() {

    $conn = \Drupal::database();
    if ($conn->schema()->tableExists('multiple_sitemap')) {
      $output = t('You have not created any sitemap yet.');
      $results = $conn->select('multiple_sitemap', 'ms')
        ->fields('ms')
        ->execute()
        ->fetchAll();

      $header = array(
        'File Name',
        'Custom links',
        'Edit',
        'Delete',
      );

      $rows = array();
      if (!empty($results)) {
        foreach ($results as $key => $result) {
          $ms_id = $result->ms_id;
          // $rows[$key]['msid'] = $result->ms_id.
          $rows[$key]['fname'] = $result->file_name;
          $rows[$key]['custom_links'] = $result->custom_links;

          $rows[$key]['edit'] = Link::fromTextAndUrl(t('Edit'), Url::fromRoute('multiple_sitemap.edit_multiple_sitemap', array('ms_id' => $ms_id)))->toString();
          $rows[$key]['delete'] = Link::fromTextAndUrl(t('Delete'), Url::fromRoute('multiple_sitemap.delete_multiple_sitemap', array('ms_id' => $ms_id)))->toString();
        }

        $table['table'] = [
          '#type' => 'table',
          '#header' => $header,
          '#rows' => $rows,
        ];


        return $table;
      }
    }


    return array(
      '#type' => 'markup',
      '#markup' => $this->t('Your installation of multiple sitemap module  is not correct.'),
    );
  }
}
