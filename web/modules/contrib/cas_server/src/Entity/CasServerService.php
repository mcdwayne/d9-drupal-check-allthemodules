<?php

/**
 * @file
 * Contains \Drupal\cas_server\Entity\CasServerService.
 */

namespace Drupal\cas_server\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\cas_server\CasServerServiceInterface;

/**
 * Defines a CasServerService entity.
 *
 * @ConfigEntityType(
 *   id = "cas_server_service",
 *   label = @Translation("Cas Server Service"),
 *   handlers = {
 *     "list_builder" = "Drupal\cas_server\Controller\ServicesListBuilder",
 *     "form" = {
 *       "add" = "Drupal\cas_server\Form\ServicesForm",
 *       "edit" = "Drupal\cas_server\Form\ServicesForm",
 *       "delete" = "Drupal\cas_server\Form\ServicesDeleteForm",
 *     }
 *   },
 *   config_prefix = "cas_server_service",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/people/cas_server/{cas_server_service}",
 *     "delete-form" = "/admin/config/people/cas_server/{cas_server_service}/delete",
 *   }
 * )
 */
class CasServerService extends ConfigEntityBase implements CasServerServiceInterface {

  /**
   * The machine id.
   *
   * @var string
   */
  public $id;

  /**
   * The label.
   *
   * @var string
   */
  public $label;

  /**
   * The service URL pattern.
   *
   * @var string
   */
  public $service;

  /**
   * Single sign on status.
   *
   * @var bool
   */
  public $sso;

  /**
   * Attributes to release.
   *
   * @var array
   */
  public $attributes;

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function setId($new_id) {
    $this->id = $new_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($new_label) {
    $this->label = $new_label;
  }

  /**
   * {@inheritdoc}
   */
  public function getService() {
    return $this->service;
  }

  /**
   * {@inheritdoc}
   */
  public function setService($new_service) {
    $this->service = $new_service;
  }

  /**
   * {@inheritdoc}
   */
  public function getSso() {
    return $this->sso;
  }

  /**
   * {@inheritdoc}
   */
  public function setSso($new_sso) {
    $this->sso = $new_sso;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttributes() {
    return $this->attributes;
  }

  /**
   * {@inheritdoc}
   */
  public function setAttributes($new_attributes) {
    return $this->attributes;
  }

}
