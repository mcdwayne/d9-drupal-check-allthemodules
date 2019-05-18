<?php

namespace Drupal\dcat_import\Plugin\migrate\source;

use EasyRdf_Resource;
use EasyRdf_Graph;

/**
 * Theme feed source.
 *
 * @MigrateSource(
 *   id = "dcat.theme"
 * )
 */
class ThemeDcatFeedSource extends DcatFeedSource {

  /**
   * Not in use.
   *
   * @see getDcatData()
   */
  public function getDcatType() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getDcatData(EasyRdf_Graph $graph) {
    $data = array();
    $datasets = $graph->allOfType('dcat:Dataset');

    /** @var EasyRdf_Resource $dataset */
    foreach ($datasets as $dataset) {
      $themes = $this->getValue($dataset, 'dcat:theme');
      if ($themes) {
        $themes = is_array($themes) ? $themes : array($themes);
        $data += array_combine($themes, $themes);
      }
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'uri' => t('URI / ID'),
      'name' => t('Name'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    $data = array();

    /** @var EasyRdf_Resource $theme */
    foreach ($this->getSourceData() as $theme) {
      // Until we have a better solution, we'll use the URI as name.
      $data[] = array(
        'uri' => $theme,
        'name' => $theme,
      );
    }

    return new \ArrayIterator($data);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['uri']['type'] = 'string';
    return $ids;
  }

}
