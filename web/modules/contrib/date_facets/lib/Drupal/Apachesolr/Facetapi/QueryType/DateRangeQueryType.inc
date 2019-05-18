<?php

/**
 * @file
 * Numeric range query type plugin for the Apache Solr adapter.
 */

/**
 * Plugin for "numeric_range" query types.
 */
class Drupal_Apachesolr_Facetapi_QueryType_DateRangeQueryType extends FacetapiQueryType implements FacetapiQueryTypeInterface {

  /**
   * Implements FacetapiQueryType::getType().
   */
  static public function getType() {
    return 'date_range';
  }

  /**
   * Implements FacetapiQueryType::execute().
   *
   * @see http://searchhub.org/dev/2012/02/23/date-math-now-and-filter-queries/
   */
  public function execute($query) {
    $active = $this->getActiveItems();
    $field = $this->facet['field'];

    if (!empty($active)) {
      switch (key($active)) {
        case 'past_hour':
          $lower = "NOW/HOUR-1HOUR";
          break;

        case 'past_24_hours':
          $lower = "NOW/DAY-1DAY";
          break;

        case 'past_week':
          $lower = "NOW/DAY-7DAYS";
          break;

        case 'past_month':
          $lower = "NOW/MONTH-1MONTH";
          break;

        case 'past_year':
          $lower = "NOW/MONTH-1YEAR";
          break;

        default:
          return;
      }

      $query->addParam('fq', $this->facet['field'] . ":[$lower TO NOW/DAY+1DAY]");
    }
  }

  /**
   * Initializes the facet's build array.
   *
   * Any calls to this method need to be wrapped in a try-catch block.
   *
   * @return array
   *   The initialized render array.
   */
  public function build() {
    $build = array();

    $build['past_hour'] = array(
      '#count' => NULL,
      '#markup' => t('Past hour'),
    );

    $build['past_24_hours'] = array(
      '#count' => NULL,
      '#markup' => t('Past 24 hours'),
    );

    $build['past_week'] = array(
      '#count' => NULL,
      '#markup' => t('Past week'),
    );

    $build['past_month'] = array(
      '#count' => NULL,
      '#markup' => t('Past month'),
    );

    $build['past_year'] = array(
      '#count' => NULL,
      '#markup' => t('Past year'),
    );

    return $build;
  }
}
