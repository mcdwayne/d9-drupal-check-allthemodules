<?php

namespace Drupal\entity_tools;

/**
 * Class AbstractEntityQuery.
 */
abstract class AbstractEntityQuery implements EntityQueryInterface {

  // @todo by entity id (nid, term id, path, user id, role, ...)
  // @todo review auto negociation between DBTNG and EntityQuery
  // @todo review entity access https://www.drupal.org/node/777578

  /**
   * Drupal\Core\Entity\Query\QueryFactory definition.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  public $coreEntityQuery;

  /**
   * Has translation fallback.
   *
   * If true, when a translation is not found, fetch the source language
   * for the entity.
   *
   * @var bool
   */
  public $translationFallback;

  /**
   * Bypasses entity access control.
   *
   * @var bool
   */
  public $bypassEntityAccess;

  /**
   * Constructor.
   */
  public function __construct($entity_type_id, $conjunction = 'AND') {
    $this->coreEntityQuery = \Drupal::entityQuery($entity_type_id, $conjunction);
    $this->translationFallback = FALSE;
    $this->bypassEntityAccess = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isEntityMultilingual() {
    $result = FALSE;
    if (\Drupal::languageManager()->isMultilingual()) {
      // @todo if type translatable
      $result = TRUE;
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function setLanguage($id) {
    // @todo implement / review language manager delegation.
  }

  /**
   * Limits the amount of items, starting from the first value.
   *
   * @param int $items
   *   Amount of items.
   */
  public function limit($items) {
    $this->coreEntityQuery->range(0, $items);
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    return $this->coreEntityQuery->execute();
  }

}
