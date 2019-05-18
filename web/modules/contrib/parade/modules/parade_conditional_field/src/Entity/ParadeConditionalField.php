<?php

namespace Drupal\parade_conditional_field\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\parade_conditional_field\ParadeConditionalFieldInterface;

/**
 * Defines the Parade conditional field config entity.
 *
 * @ConfigEntityType(
 *   id = "parade_conditional_field",
 *   label = @Translation("Parade conditional field"),
 *   handlers = {
 *     "list_builder" = "Drupal\parade_conditional_field\ParadeConditionalFieldListBuilder",
 *     "form" = {
 *       "add" = "Drupal\parade_conditional_field\Form\ParadeConditionalFieldForm",
 *       "edit" = "Drupal\parade_conditional_field\Form\ParadeConditionalFieldForm",
 *       "delete" = "Drupal\parade_conditional_field\Form\ParadeConditionalFieldDeleteForm"
 *     }
 *   },
 *   config_prefix = "parade_conditional_field",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id"
 *   },
 *   links = {
 *     "edit-form" = "/admin/structure/parade_conditional_field/{parade_conditional_field}/edit",
 *     "delete-form" = "/admin/structure/parade_conditional_field/{parade_conditional_field}/delete",
 *   }
 * )
 */
class ParadeConditionalField extends ConfigEntityBase implements ParadeConditionalFieldInterface {

  /**
   * The Parade conditional field ID.
   *
   * @var string
   */
  protected $id;

  /**
   * A Paragraph bundle machine names.
   *
   * @var string
   */
  protected $bundle;

  /**
   * An array of Classy paragraphs styles.
   *
   * @var array
   */
  protected $layouts;

  /**
   * The View mode.
   *
   * @var string
   */
  protected $view_mode;

  /**
   * An array of Classy paragraphs styles.
   *
   * @var array
   */
  protected $classes;

  /**
   * {@inheritdoc}
   */
  public function getNumericId() {
    return (int) str_replace($this->bundle . '_', '', $this->id());
  }

  /**
   * {@inheritdoc}
   */
  public function getBundle() {
    return isset($this->bundle) ? $this->bundle : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLayouts() {
    return isset($this->layouts) ? $this->layouts : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getViewMode() {
    return isset($this->view_mode) ? $this->view_mode : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getClasses() {
    return isset($this->classes) ? $this->classes : [];
  }

  /**
   * {@inheritdoc}
   */
  public static function loadConditionsMultiple(array $ids = NULL) {
    $entities = parent::loadMultiple($ids);
    $conditions = [];
    foreach ($entities as $id => $condition) {
      $conditions[$condition->getBundle()][$id] = [
        'parade_layout' => [
          'on_values' => $condition->getLayouts(),
          'dependents' => [
            'parade_view_mode' => [
              'effect' => 'hide',
              'values' => [
                $condition->getViewMode(),
              ],
            ],
            'parade_color_scheme' => [
              'options' => $condition->getClasses(),
            ],
          ],
        ],
      ];
    }
    return $conditions;
  }

}
