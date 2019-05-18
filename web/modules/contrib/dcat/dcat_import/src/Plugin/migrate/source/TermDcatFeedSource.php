<?php

namespace Drupal\dcat_import\Plugin\migrate\source;

use Drupal\Component\Utility\Unicode;
use EasyRdf_Resource;
use EasyRdf_Graph;
use Drupal\migrate\Row;

/**
 * DCAT Term feed source.
 */
abstract class TermDcatFeedSource extends DcatFeedSource {

  /**
   * Returns the DCAT term field to extract from the feed.
   *
   * E.g. dcat:keyword.
   *
   * @return string
   *   The DCAT term field to extract.
   */
  public abstract function getTermField();

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'name' => t('Name'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDcatData(EasyRdf_Graph $graph) {
    $data = array();
    $datasets = $graph->allOfType('dcat:Dataset');

    /** @var EasyRdf_Resource $dataset */
    foreach ($datasets as $dataset) {
      $keywords = $this->getValue($dataset, $this->getTermField());
      if ($keywords) {
        $keywords = is_array($keywords) ? $keywords : array($keywords);
        $data += array_combine($keywords, $keywords);
      }
    }

    foreach ($data as &$keyword) {
      $keyword = Unicode::truncate($keyword, 255);
    }

    if (!empty($this->configuration['lowercase_taxonomy_terms'])) {
      $data = array_map('strtolower', $data);
      $data = array_unique($data);
    }

    array_filter($data);

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    $data = array();

    foreach ($this->getSourceData() as $term) {
      $data[] = array(
        'name' => $term,
      );
    }

    return new \ArrayIterator($data);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['name']['type'] = 'string';
    return $ids;
  }

}
