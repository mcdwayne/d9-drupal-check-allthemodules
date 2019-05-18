<?php

namespace Drupal\formfactorykits\Kits\Field\Entity;

use Drupal\formfactorykits\Kits\Field\Select\SelectKit;
use Drupal\formfactorykits\Kits\Traits\ConfigFactoryTrait;
use Drupal\formfactorykits\Kits\Traits\EntityTrait;
use Drupal\kits\Services\KitsInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Class TaxonomyTermSelectKit
 *
 * @package Drupal\formfactorykits\Kits\Field\Entity
 */
class TaxonomyTermSelectKit extends SelectKit {
  use ConfigFactoryTrait;
  use EntityTrait;
  const ID = 'term';
  const TRIM_LOADED_NAME_KEY = 'trim_loaded_name';

  /**
   * @inheritdoc
   */
  public function __construct(KitsInterface $kitsService,
                              $id = NULL,
                              array $parameters = [],
                              array $context = []) {
    if (!array_key_exists(self::TRIM_LOADED_NAME_KEY, $context)) {
      $context[self::TRIM_LOADED_NAME_KEY] = TRUE;
    }
    parent::__construct($kitsService, $id, $parameters, $context);
  }

  /**
   * @param string $vid
   *
   * @return static
   */
  public function loadTaxonomyVocabulary($vid) {
    if (!$this->has('title')) {
      $name = $this->getTaxonomyName($vid);
      if (!$this->isMultiple() && $this->isTrimLoadedName()) {
        $name = rtrim($name, 's');
      }
      $this->setTitle($name);
    }
    foreach ($this->getTerms($vid) as $term) {
      $this->appendOption([$term->id() => $this->t($term->getName())]);
    }
    return $this;
  }

  /**
   * @param string $vid
   *
   * @return string
   */
  private function getTaxonomyName($vid) {
    $configName = sprintf('taxonomy.vocabulary.%s', $vid);
    $data = $this->getConfigData($configName);
    return array_key_exists('name', $data) ? $data['name'] : '';
  }

  /**
   * @param string $vid
   *
   * @return Term[]
   */
  private function getTermIds($vid) {
    $result = $this->getEntityQuery('taxonomy_term')
      ->condition('vid', $vid)
      ->execute();
    return empty($result) ? [] : $result;
  }

  /**
   * @param string $vid
   *
   * @return \Drupal\Core\Entity\EntityInterface[]|Term[]
   */
  private function getTerms($vid) {
    $tids = $this->getTermIds($vid);
    return empty($tids) ? $tids : Term::loadMultiple($tids);
  }

  /**
   * @param bool $isMultiple
   *
   * @return static
   */
  public function setMultiple($isMultiple = TRUE) {
    $this->setTrimLoadedName(FALSE);
    parent::setMultiple($isMultiple);
    return $this;
  }

  /**
   * @param bool $trim
   *
   * @return static
   */
  public function setTrimLoadedName($trim = TRUE) {
    return $this->setContext(self::TRIM_LOADED_NAME_KEY, $trim);
  }

  /**
   * @return bool
   */
  public function isTrimLoadedName() {
    return (bool) $this->getContext(self::TRIM_LOADED_NAME_KEY);
  }
}
