<?php

namespace Drupal\dcat_import\Plugin\migrate\source;

use Drupal\Component\Utility\Unicode;
use Drupal\taxonomy\Plugin\views\wizard\TaxonomyTerm;
use EasyRdf_Resource;
use Drupal\migrate\Row;

/**
 * DCAT Dataset feed source.
 *
 * @MigrateSource(
 *   id = "dcat.dataset"
 * )
 */
class DatasetDcatFeedSource extends DcatFeedSource {

  /**
   * {@inheritdoc}
   */
  public function getDcatType() {
    return 'dcat:Dataset';
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'uri' => t('URI / ID'),
      'title' => t('Title'),
      'description' => t('Description'),
      'issued' => t('Issued'),
      'modified' => t('Modified'),
      'landing_page' => t('Landing Page'),
      'distribution' => t('Distribution'),
      'accrual_periodicity' => t('Frequency'),
      'keyword' => t('Keyword'),
      'spatial_geographical' => t('Spatial/geographical coverage'),
      'temporal' => t('Temporal'),
      'theme' => t('Theme'),
      'publisher' => t('Publisher'),
      'source' => t('Source'),
      'contact_point' => t('Contact point'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function convertResource(EasyRdf_Resource $resource) {
    return parent::convertResource($resource) + [
      'title' => $this->getValue($resource, 'dc:title'),
      'description' => $this->getValue($resource, 'dc:description'),
      'issued' => $this->getDateValue($resource, 'dc:issued'),
      'modified' => $this->getDateValue($resource, 'dc:modified'),
      'landing_page' => $this->getValue($resource, 'dcat:landingPage'),
      'distribution' => $this->getValue($resource, 'dcat:distribution'),
      'accrual_periodicity' => $this->getValue($resource, 'dc:accrualPeriodicity'),
      'keyword' => $this->getValue($resource, 'dcat:keyword'),
      'spatial_geographical' => $this->getValue($resource, 'dc:spatial'),
      'temporal' => $this->getValue($resource, 'dc:temporal'),
      'theme' => $this->getValue($resource, 'dcat:theme'),
      'publisher' => $this->getValue($resource, 'dc:publisher'),
      'contact_point' => $this->getValue($resource, 'dcat:contactPoint'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Allow themes to be remapped.
    if (!empty($this->configuration['global_theme'])) {
      $themes = $row->getSourceProperty('theme');
      $themes = is_array($themes) ? $themes : [$themes];
      $new_themes = [];

      foreach ($themes as $theme) {
        $query = \Drupal::entityQuery('taxonomy_term');
        $idcondition = $query->orConditionGroup()
          ->condition('mapping', $theme)
          ->condition('external_id', $theme);

        $ids = $query
          ->condition('vid', 'dataset_theme')
          ->condition($idcondition)
          ->execute();

        /** @var TaxonomyTerm $term */
        foreach (\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadMultiple($ids) as $term) {
          $uri = $term->get('external_id')->getValue();
          $new_themes[] = $uri[0]['uri'];
        }
      }

      $row->setSourceProperty('theme', $new_themes);
    }

    if (!empty($this->configuration['lowercase_taxonomy_terms'])) {
      $terms = $row->getSourceProperty('keyword');
      $terms = is_array($terms) ? $terms : [$terms];
      $terms = array_map('strtolower', $terms);
      $row->setSourceProperty('keyword', array_unique($terms));
    }

    $keywords = [];
    foreach ((array) $row->getSourceProperty('keyword') as $keyword) {
      $keywords[] = Unicode::truncate($keyword, 255);
    }
    array_filter($keywords);
    $row->setSourceProperty('keyword', $keywords);

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['uri']['type'] = 'string';
    return $ids;
  }

}
