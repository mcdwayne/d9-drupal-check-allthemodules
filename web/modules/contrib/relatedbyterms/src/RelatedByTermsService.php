<?php

namespace Drupal\relatedbyterms;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Implementation of a Drupal service.
 *
 * This service class will provide all the business logic of this module.
 */
class RelatedByTermsService implements RelatedByTermsServiceInterface {

  /**
   * The configuration object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $currentConfig;

  /**
   * The Language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LanguageManagerInterface $language_manager) {
    $this->currentConfig = $config_factory->getEditable('relatedbyterms.settings');
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getRelatedNodes($nid, $langcode = NULL, $limit = -1) {
    $nodes = [];

    if (empty($langcode)) {
      $langcode = $this->languageManager->getCurrentLanguage()->getId();
    }

    if ($limit == -1) {
      $limit = $this->getElementsDisplayed();
    }

    $query = $this->getQuery($nid, $langcode, $limit);
    $result = $query->execute();

    while ($record = $result->fetchAssoc()) {
      $nodes[] = $record['nid'];
    }

    return $nodes;
  }

  /**
   * Create DB Query.
   */
  protected function getQuery($nid, $langcode, $limit) {

    $subquery = db_select('taxonomy_index', 't1');
    $subquery->condition('nid', $nid);
    $subquery->addField('t1', 'tid');

    $query = db_select('taxonomy_index', 't');
    $query->join('node', 'n', 'n.nid = t.nid');
    $query->addTag('relatedbyterms_count');
    $query->addTag('node_access');
    $query->condition('n.langcode', $langcode);
    $query->condition('t.tid', $subquery, 'IN');
    $query->condition('t.nid', $nid, '<>');
    $query->addField('t', 'nid');
    $query->addExpression('count(\'t.nid\')', 'count');
    $query->orderBy('count', 'DESC');
    $query->orderBy('nid', 'DESC');
    $query->groupBy('t.nid');

    if ($limit > 0) {
      $query->range(0, $limit);
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementsDisplayed() {
    return $this->currentConfig->get('relatedbyterms.elements_displayed');
  }

  /**
   * {@inheritdoc}
   */
  public function setElementsDisplayed($limit) {
    $this->currentConfig->set('relatedbyterms.elements_displayed', $limit);
    $this->currentConfig->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayMode() {
    return $this->currentConfig->get('relatedbyterms.display_mode');
  }

  /**
   * {@inheritdoc}
   */
  public function setDisplayMode($displayMode) {
    $this->currentConfig->set('relatedbyterms.display_mode', $displayMode);
    $this->currentConfig->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultTitle() {
    return $this->currentConfig->get('relatedbyterms.block_title');
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultTitle($defaultTitle) {
    $this->currentConfig->set('relatedbyterms.block_title', $defaultTitle);
    $this->currentConfig->save();
  }

}
