<?php

/**
 * @file
 * Definition of Drupal\entityreference\Plugin\entityreference\selection\SelectionBroken.
 *
 * Provide entity type specific access control of the node entity type.
 */

namespace Drupal\entityreference\Plugin\Type\Selection;

use Drupal\Core\Entity\EntityFieldQuery;
use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Entity\EntityInterface;

use Drupal\entityreference\Plugin\entityreference\selection\SelectionBase;


/**
 * A null implementation of EntityReference_SelectionHandler.
 */
class SelectionBroken implements SelectionInterface {

  protected function __construct($field, $instance) {
    $this->field = $field;
    $this->instance = $instance;
  }

  public static function settingsForm($field, $instance) {
    $form['selection_handler'] = array(
      '#markup' => t('The selected selection handler is broken.'),
    );
    return $form;
  }

  public function getReferencableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    return array();
  }

  public function countReferencableEntities($match = NULL, $match_operator = 'CONTAINS') {
    return 0;
  }

  public function validateReferencableEntities(array $ids) {
    return array();
  }

  public function validateAutocompleteInput($input, &$element, &$form_state, $form) {
  }

  public function entityFieldQueryAlter(AlterableInterface $query) {
  }
}
