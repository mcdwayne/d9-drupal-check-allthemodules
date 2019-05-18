<?php

namespace Drupal\loggable\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Loggable filter entity.
 *
 * @ConfigEntityType(
 *   id = "loggable_filter",
 *   label = @Translation("Loggable filter"),
 *   label_collection = @Translation("Loggable filters"),
 *   label_singular = @Translation("loggable filter"),
 *   label_plural = @Translation("loggable filters"),
 *   label_count = @PluralTranslation(
 *     singular = "@count loggable filter",
 *     plural = "@count loggable filters"
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\loggable\LoggableFilterListBuilder",
 *     "form" = {
 *       "add" = "Drupal\loggable\Form\LoggableFilterForm",
 *       "edit" = "Drupal\loggable\Form\LoggableFilterForm",
 *       "delete" = "Drupal\loggable\Form\LoggableFilterDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\loggable\LoggableFilterHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "loggable_filter",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/development/loggable/filters/{loggable_filter}",
 *     "add-form" = "/admin/config/development/loggable/filters/add",
 *     "edit-form" = "/admin/config/development/loggable/filters/{loggable_filter}/edit",
 *     "delete-form" = "/admin/config/development/loggable/filters/{loggable_filter}/delete",
 *     "collection" = "/admin/config/development/loggable/filters"
 *   }
 * )
 */
class LoggableFilter extends ConfigEntityBase implements LoggableFilterInterface {

  /**
   * The filter ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The filter label.
   *
   * @var string
   */
  protected $label;

  /**
   * The filter severity levels.
   *
   * @var array
   */
  protected $severity_levels = [];

  /**
   * The filter type values.
   *
   * @var array
   */
  protected $types = [];

  /**
   * The filter enabled status.
   *
   * @var bool
   */
  protected $enabled = TRUE;

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel(string $label) {
    $this->label = $label;
  }

  /**
   * {@inheritdoc}
   */
  public function getSeverityLevels() {
    return $this->severity_levels;
  }

  /**
   * {@inheritdoc}
   */
  public function setSeverityLevels(array $levels) {
    $this->severity_levels = $levels;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypes() {
    return $this->types;
  }

  /**
   * {@inheritdoc}
   */
  public function setTypes(array $types) {
    return $this->types = $types;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return $this->enabled;
  }

  /**
   * {@inheritdoc}
   */
  public function setEnabled() {
    $this->enabled = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function setDisabled() {
    $this->enabled = FALSE;
  }

}
