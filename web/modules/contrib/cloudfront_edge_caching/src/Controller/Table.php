<?php

/**
 * @file
 * Contains \Drupal\cloudfront_edge_caching\Controller\Table
 */

namespace Drupal\cloudfront_edge_caching\Controller;
 
use Drupal\Core\Controller\ControllerBase;
use Aws\CloudFront\CloudFrontClient;
 
class Table extends ControllerBase {

  public function getInvalidations() {
    // Return values.
    $return[0] = FALSE;
    $return[1] = 'message';

    // Get the AWS Credentials.
    $config = \Drupal::config('cec.settings');

    // Load AWS SDK.
    $cloudFront = new CloudFrontClient([
      'version'     => 'latest',
      'region'      => $config->get('cec_region'),
      'credentials' => [
        'key'    => $config->get('cec_key'),
        'secret' => $config->get('cec_secret'),
      ],
    ]);

    try {
      $a = $cloudFront->listInvalidations([
        'DistributionId' => $config->get('cec_distribution_id'),
      ]);
      ksm($a);
    }

    catch (AwsException $e) {
      $catch = TRUE;
      $return[1] = $e->getMessage();

      // Logs an error.
      \Drupal::logger('cloudfront_edge_caching')->error($e->getMessage());
    }

    return $return;
  }

  public function pager() {

    $a = ksm($this->getInvalidations());
    kint($a);

    $header = array(
      // We make it sortable by name.
      array('data' => $this->t('Name'), 'field' => 'name', 'sort' => 'asc'),
      array('data' => $this->t('Content')),
    );
 
    $db = \Drupal::database();
    $query = $db->select('config','c');
    $query->fields('c', array('name'));
    // The actual action of sorting the rows is here.
    $table_sort = $query->extend('Drupal\Core\Database\Query\TableSortExtender')
                        ->orderByHeader($header);
    // Limit the rows to 20 for each page.
    $pager = $table_sort->extend('Drupal\Core\Database\Query\PagerSelectExtender')
                        ->limit(20);
    $result = $pager->execute();
 
    // Populate the rows.
    $rows = array();
    foreach($result as $row) {
      $rows[] = array('data' => array(
        'name' => $row->name,
        'content' => '[BLOB]', // This hardcoded [BLOB] is just for display purpose only.
      ));
    }
 
    // The table description.
    $build = array(
      '#markup' => t('List of All Configurations')
    );
 
    // Generate the table.
    $build['config_table'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    );
 
    // Finally add the pager.
    $build['pager'] = array(
      '#type' => 'pager'
    );
 
    return $build;
  }
}
