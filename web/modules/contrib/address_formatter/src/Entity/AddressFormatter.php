<?php

namespace Drupal\address_formatter\Entity;

use Drupal\address_formatter\AddressFormatterInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the AddressFormatter entity.
 *
 * @ConfigEntityType(
 *   id = "address_formatter",
 *   label = @Translation("Address Formatter options"),
 *   handlers = {
 *     "list_builder" = "Drupal\address_formatter\Controller\AddressFormatterListBuilder",
 *     "form" = {
 *       "add" = "Drupal\address_formatter\Form\AddressFormatterForm",
 *       "edit" = "Drupal\address_formatter\Form\AddressFormatterForm",
 *       "delete" = "Drupal\address_formatter\Form\AddressFormatterDeleteForm"
 *     }
 *   },
 *   config_prefix = "options",
 *   admin_permission = "administer address formatter",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/address-formatter/{address_formatter}",
 *     "edit-form" = "/admin/config/address-formatter/{address_formatter}/edit",
 *     "enable" = "/admin/config/address-formatter/{address_formatter}/enable",
 *     "disable" = "/admin/config/address-formatter/{address_formatter}/disable",
 *     "delete-form" = "/admin/config/address-formatter/{address_formatter}/delete",
 *     "collection" = "/admin/config/address-formatter"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "options",
 *   }
 * )
 */
class AddressFormatter extends ConfigEntityBase implements AddressFormatterInterface {
  /**
   * Options ID.
   *
   * @var string
   */
  protected $id;

  /**
   * Options label.
   *
   * @var string
   */
  protected $label;

  /**
   * Options.
   *
   * @var array
   */
  protected $options = [];

  /**
   * {@inheritdoc}
   */
  public function getOptions($strict = FALSE) {
    if ($strict) {
      $options = $this->options;
      if (isset($options['controlNav']) && $options['controlNav'] != 'thumbnails') {
        $options['controlNav'] = boolval($options['controlNav']);
      }
      return $options;
    }
    else {
      return $this->options;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setOptions(array $options) {
    $this->options = $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getOption($name) {
    return isset($this->options[$name]) ? $this->options[$name] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(array $values = []) {
    $addressFormatter = parent::create($values);
    $addressFormatter->setOptions($addressFormatter->getOptions());
    return $addressFormatter;
  }

}
