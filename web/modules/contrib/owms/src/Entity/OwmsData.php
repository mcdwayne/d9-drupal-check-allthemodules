<?php

namespace Drupal\owms\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines the OWMS Data entity.
 *
 * @ConfigEntityType(
 *   id = "owms_data",
 *   label = @Translation("OWMS Data Object"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\owms\OwmsDataListBuilder",
 *     "form" = {
 *       "add" = "Drupal\owms\Form\OwmsDataForm",
 *       "edit" = "Drupal\owms\Form\OwmsDataForm",
 *       "delete" = "Drupal\owms\Form\OwmsDataDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\owms\OwmsDataHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "owms",
 *   admin_permission = "administer owms configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/owms/{owms_data}",
 *     "add-form" = "/admin/config/owms/add",
 *     "edit-form" = "/admin/config/owms/{owms_data}/edit",
 *     "delete-form" = "/admin/config/owms/{owms_data}/delete",
 *     "collection" = "/admin/config/owms"
 *   }
 * )
 */
class OwmsData extends ConfigEntityBase implements OwmsDataInterface {

  use StringTranslationTrait;

  /**
   * The base url for the OWMS endpoints.
   */
  const ENDPOINT_BASE_URL = 'http://standaarden.overheid.nl/owms/terms/';

  /**
   * The machine name.
   *
   * @var string
   */
  protected $id;

  /**
   * The OWMS Endpoint.
   *
   * @var string
   *   The endpoint.
   */
  protected $endpoint;

  /**
   * @var array
   */
  protected $items = [];


  /**
   * The OWMS Factory Service instance.
   *
   * @var \Drupal\owms\OwmsManagerInterface
   */
  protected $OwmsManager;

  /**
   * The stored xml object that was fetched from the endpoint.
   *
   * @var \SimpleXMLElement
   */
  protected $xml = NULL;

  /**
   * {@inheritdoc}
   */
  public function getEndpointUrl() {
    return self::ENDPOINT_BASE_URL . $this->getEndpointIdentifier() . '.xml';
  }

  /**
   * {@inheritdoc}
   */
  public function getEndpointIdentifier() {
    return $this->endpoint;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    $this->set('id', strtolower($this->get('endpoint')));
    return parent::save();
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    $this->validate();
    if ($this->isNew()) {
      // In normal circumstances the xml property would be set.
      // However when testing it might not be. That's why we have to add
      // this check.
      if ($this->xml) {
        $items = $this->getOwmsManagerInstance()->parseDataValues($this->xml);
        $this->set('items', $items);
      }
    }
    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $errors = [];
    try {
      $xml = $this->getOwmsManagerInstance()->validateEndpoint($this->getEndpointUrl());
      $this->xml = $xml;
    }
    catch (\Exception $exception) {
      $errors['endpoint'] = $exception;
    }
    return !empty($errors) ? $errors : TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwmsManagerInstance() {
    if (!$this->OwmsManager) {
      $this->OwmsManager = \Drupal::getContainer()->get('owms.manager');
    }
    return $this->OwmsManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getXml() {
    if (!$this->xml) {
      $xml = $this->getOwmsManagerInstance()->fetchXmlFromEndpoint($this);
      $this->xml = $xml;
      return $this->xml;
    }
    return $this->xml;
  }

  /**
   * {@iinheritdoc}
   */
  public function label() {
    return $this->getEndpointIdentifier();
  }

  /**
   * {@inheritdoc}
   */
  public function getItems() {
    return $this->get('items');
  }

  /**
   * {@inheritdoc}
   */
  public function getValidItems(){
    $validItems = [];
    foreach ($this->getItems() as $item) {
      $label = $item['label'];
      $label .= $item['deprecated'] ? ' (' . $this->t('deprecated') . ')' : '';
      $validItems[$item['identifier']] = $label;
    }
    return $validItems;
  }

  /**
   * {@inheritdoc}
   */
  public function getDeprecatedItems() {
    $deprecated = [];
    foreach ($this->getItems() as $item) {
      if ($item['deprecated']) {
        $deprecated[] = $item;
      }
    }
    return $deprecated;
  }

}
