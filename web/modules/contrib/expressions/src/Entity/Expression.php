<?php

/**
 * @file
 * Contains Drupal\expressions\Entity\Expression.
 */

namespace Drupal\expressions\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\expressions\ExpressionInterface;

/**
 * Defines the Expression entity.
 *
 * @ConfigEntityType(
 *   id = "expression",
 *   label = @Translation("Expression"),
 *   handlers = {
 *     "list_builder" = "Drupal\expressions\Controller\ExpressionListBuilder",
 *     "form" = {
 *       "add" = "Drupal\expressions\Form\ExpressionForm",
 *       "edit" = "Drupal\expressions\Form\ExpressionForm",
 *       "delete" = "Drupal\expressions\Form\ExpressionDeleteForm"
 *     }
 *   },
 *   config_prefix = "expression",
 *   admin_permission = "administer site configuration",
 *   links = {
 *     "collection" = "/admin/structure/expression",
 *     "add-form" = "/admin/structure/expression/add",
 *     "edit-form" = "/admin/structure/expression/{expression}",
 *     "delete-form" = "/admin/structure/expression/{expression}/delete"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class Expression extends ConfigEntityBase implements ExpressionInterface {
  /**
   * The Expression ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Expression label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Expression status.
   *
   * @var boolean
   */
  protected $status;

  /**
   * The expression code.
   *
   * @var string
   */
  protected $code;

  /**
   * status getter
   *
   * @return string
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * Code getter
   *
   * @return string
   */
  public function getCode() {
    return $this->code;
  }

  /**
   * Evaluate expression code.
   */
  public function evaluate() {
    // @TODO: Find a way to inject language service.
    $language = \Drupal::service('expressions.language');
    try {
      $result = $language->evaluate($this->getCode());
    }
    catch (\Exception $e) {
      $result = 'Error: ' . $e->getMessage();
    }
    return $result;

  }

}
