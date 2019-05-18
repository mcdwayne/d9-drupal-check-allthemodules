<?php

namespace Drupal\mass_contact\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Mass contact category entity.
 *
 * @ConfigEntityType(
 *   id = "mass_contact_category",
 *   label = @Translation("Mass contact category"),
 *   handlers = {
 *     "access" = "\Drupal\mass_contact\CategoryAccessControlHandler",
 *     "list_builder" = "Drupal\mass_contact\CategoryListBuilder",
 *     "form" = {
 *       "add" = "Drupal\mass_contact\Form\CategoryForm",
 *       "edit" = "Drupal\mass_contact\Form\CategoryForm",
 *       "delete" = "Drupal\mass_contact\Form\CategoryDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider"
 *     }
 *   },
 *   config_prefix = "category",
 *   admin_permission = "mass contact administer",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "recipients",
 *     "selected"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/mass-contact/category/{mass_contact_category}/edit",
 *     "add-form" = "/admin/config/mass-contact/category/add",
 *     "edit-form" = "/admin/config/mass-contact/category/{mass_contact_category}/edit",
 *     "delete-form" = "/admin/config/mass-contact/category/{mass_contact_category}/delete",
 *     "collection" = "/admin/config/mass-contact/category"
 *   }
 * )
 */
class MassContactCategory extends ConfigEntityBase implements MassContactCategoryInterface {

  /**
   * GroupingInterface method plugin manager.
   *
   * @var \Drupal\Core\Plugin\DefaultPluginManager
   */
  protected $groupingMethodManager;

  /**
   * The Mass contact category ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Mass contact category label.
   *
   * @var string
   */
  protected $label;

  /**
   * The recipient categories, keyed by plugin ID.
   *
   * @var array
   *
   * The structure of each item is, for instance:
   * @code
   *   categories:
   *     - role_1
   *     - role_2
   * @endcode
   */
  protected $recipients = [];

  /**
   * The grouping plugins, keyed by plugin ID.
   *
   * @var \Drupal\mass_contact\Plugin\MassContact\GroupingMethod\GroupingInterface[]
   */
  protected $groupings;

  /**
   * Boolean indicating if this category should be selected by default.
   *
   * @var bool
   */
  protected $selected = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getGroupings() {
    $this->generateGroupings();
    return $this->groupings;
  }

  /**
   * {@inheritdoc}
   */
  public function setGroupings(array $groupings) {
    $this->recipients = $groupings;
  }

  /**
   * {@inheritdoc}
   */
  public function getSelected() {
    return $this->selected;
  }

  /**
   * {@inheritdoc}
   */
  public function setSelected($selected) {
    $this->selected = $selected;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupingCategories($grouping_id) {
    $groupings = $this->getGroupings();
    if (isset($groupings[$grouping_id])) {
      return $groupings[$grouping_id];
    }
    return FALSE;
  }

  /**
   * Constructs the grouping plugins.
   */
  protected function generateGroupings() {
    if (!is_array($this->groupings)) {
      $this->groupings = [];
      foreach ($this->recipients as $plugin_id => $configuration) {
        $this->groupings[$plugin_id] = $this->getGroupingManager()->createInstance($plugin_id, $configuration);
      }
    }
  }

  /**
   * Gets the grouping plugin manager.
   *
   * @todo Can this be injected on construction?
   *
   * @return \Drupal\Core\Plugin\DefaultPluginManager
   *   The plugin manager.
   */
  protected function getGroupingManager() {
    if (!isset($this->groupingMethodManager)) {
      $this->groupingMethodManager = \Drupal::service('plugin.manager.mass_contact.grouping_method');
    }
    return $this->groupingMethodManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipients() {
    return $this->recipients;
  }

  /**
   * {@inheritdoc}
   */
  public function setRecipients(array $recipients) {
    $this->recipients = $recipients;
    // Need to re-generate groupings.
    $this->groupings = NULL;
  }

}
