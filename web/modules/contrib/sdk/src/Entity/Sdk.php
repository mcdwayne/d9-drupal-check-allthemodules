<?php

namespace Drupal\sdk\Entity;

use Drupal\Core\Url;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * SDK entity.
 *
 * @ConfigEntityType(
 *   id = "sdk",
 *   label = @Translation("SDK"),
 *   config_prefix = "sdk",
 *   admin_permission = "administer sdk configurations",
 *   handlers = {
 *     "list_builder" = "Drupal\sdk\Entity\ListBuilder\Sdk\DefaultListBuilder",
 *     "form" = {
 *       "default" = "Drupal\sdk\Entity\Form\Sdk\DefaultForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "collection" = "/admin/config/development/sdk",
 *     "edit-form" = "/admin/config/development/sdk/{sdk}",
 *     "delete-form" = "/admin/config/development/sdk/{sdk}/delete",
 *   },
 * )
 */
class Sdk extends ConfigEntityBase implements SdkInterface {

  /**
   * Type of SDK.
   *
   * @var string
   */
  public $id = '';
  /**
   * Human-readable label.
   *
   * @var string
   */
  public $label = '';
  /**
   * Custom list of settings.
   *
   * @var array
   */
  public $settings = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values) {
    parent::__construct($values, static::ENTITY_TYPE);
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->{$this->getEntityType()->getKey('id')};
  }

  /**
   * Returns an URL to a callback page.
   *
   * @param bool $absolute
   *   Indicates whether should returning URL be absolute.
   *
   * @return string
   *   An absolute callback URL.
   */
  public function getCallbackUrl($absolute = TRUE) {
    $id = $this->id();

    if (empty($id)) {
      throw new \RuntimeException('You must set the type of SDK before continue.');
    }

    return (new Url('sdk.callback', ['sdk' => $id], ['absolute' => $absolute]))
      ->toString(TRUE)
      ->getGeneratedUrl();
  }

}
