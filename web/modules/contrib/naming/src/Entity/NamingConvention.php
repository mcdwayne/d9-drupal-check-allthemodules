<?php

/**
 * @file
 * Contains Drupal\naming\Entity\NamingConvention.
 */

namespace Drupal\naming\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\naming\NamingConventionInterface;
use \Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the NamingConvention entity.
 *
 * @ConfigEntityType(
 *   id = "naming_convention",
 *   label = @Translation("Naming convention"),
 *   admin_permission = "administer naming conventions",
 *   config_prefix = "convention",
 *   handlers = {
 *     "list_builder" = "Drupal\naming\NamingConventionListBuilder",
 *     "form" = {
 *       "add" = "Drupal\naming\NamingConventionForm",
 *       "edit" = "Drupal\naming\NamingConventionForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "add-form" = "/admin/config/development/naming/add",
 *     "edit-form" = "/admin/config/development/naming/manage/{naming_convention}",
 *     "delete-form" = "/admin/config/development/naming/{naming_convention}/delete",
 *     "collection" = "/admin/config/development/naming/manage",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "content",
 *     "path",
 *     "category",
 *     "weight",
 *   }
 * )
 */
class NamingConvention extends ConfigEntityBase implements NamingConventionInterface {

  /**
   * The NamingConvention id.
   *
   * @var string
   */
  protected $id;

  /**
   * The NamingConvention UUID.
   *
   * @var string
   */
  protected $uuid;

  /**
   * The NamingConvention label.
   *
   * @var string
   */
  protected $label;

  /**
   * The NamingConvention path.
   *
   * @var string
   */
  protected $path;

  /**
   * The NamingConvention format.
   *
   * @var array
   */
  protected $content = [
    'value' => '',
    'format' => '',
  ];

  /**
   * The NamingConvention category.
   *
   * @var string
   */
  protected $category;

  /**
   * The NamingConvention weight.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    return $this->path;
  }

  /**
   * {@inheritdoc}
   */
  public function getContent() {
    return $this->content ?: [
      'value' => '',
      'format' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCategory() {
    return $this->category;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight ?: 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteUrl() {
    try {
      $url = Url::fromRoute($this->id());
      $url->toString();
      return $url;
    }
    catch (\Exception $exception) {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPathUrl() {
    return \Drupal::service('path.validator')->getUrlIfValid(($this->getPath()));
  }

  /**
   * {@inheritdoc}
   */
  public static function loadFromRouteMatch(RouteMatchInterface $route_match) {
    $route_name = $route_match->getRouteName();

    // Check for route naming convention.
    $naming_convention = self::load($route_name);
    if ($naming_convention) {
      return $naming_convention;
    }

    // Check base add field route.
    if (strpos($route_name, 'field_ui.field_storage_config_add_') === 0) {
      $naming_convention = self::load('field_ui.field_storage_config_add');
      if ($naming_convention) {
        return $naming_convention;
      }
    }

    // Check for route parameter name/value pair naming convention.
    // This is only used to Views naming convention because the view serves
    // all configuration form via the same route with varying parameters.
    if (strpos($route_name, 'views_ui.') === 0) {
      $route_parameters = $route_match->getRawParameters()->all();
      foreach ($route_parameters as $parameter_name => $parameter_value) {
        $naming_convention = self::load("$route_name.$parameter_name.$parameter_value");
        if ($naming_convention) {
          return $naming_convention;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function sort(ConfigEntityInterface $a, ConfigEntityInterface $b) {
    // Build custom sortable string.
    $a_sort = $a->getCategory() . '-' . str_pad($a->getWeight() + 1000, 10, '0', STR_PAD_LEFT) . '-' . $a->label();
    $b_sort = $b->getCategory() . '-' . str_pad($b->getWeight() + 1000, 10, '0', STR_PAD_LEFT) . '-' . $b->label();
    return strnatcasecmp($a_sort, $b_sort);
  }

}
