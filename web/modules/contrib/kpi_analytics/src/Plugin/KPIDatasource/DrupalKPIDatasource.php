<?php

/**
 * @file
 * Contains \Drupal\kpi_analytics\Plugin\KPIDatasource\DrupalKPIDatasource.php.
 */

namespace Drupal\kpi_analytics\Plugin\KPIDatasource;

use Drupal\kpi_analytics\Plugin\KPIDatasourceBase;

/**
 * Provides a 'DrupalKPIDatasource' KPI Datasource.
 *
 * @KPIDatasource(
 *  id = "drupal_kpi_datasource",
 *  label = @Translation("Drupal datasource"),
 * )
 */
class DrupalKPIDatasource extends KPIDatasourceBase {

  /**
   * @inheritdoc
   */
  public function query($query) {
    $data = [];
    // TODO: deprecated use dependency injection.
    // TODO: check if we can use Views module.
    $results = db_query($query)->fetchAll();
    foreach ($results as $result) {
      $data[] = (array) $result;
    }
    return $data;
  }
}
