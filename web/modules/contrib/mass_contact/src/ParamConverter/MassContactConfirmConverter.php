<?php

namespace Drupal\mass_contact\ParamConverter;

use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\Routing\Route;
use Drupal\Core\ParamConverter\ParamConverterInterface;

/**
 * Provides upcasting information for mass contact email info to a confirm form.
 */
class MassContactConfirmConverter implements ParamConverterInterface {

  /**
   * Stores the tempstore factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Constructs a new MassContactConfirmConverter.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The factory for the temp store object.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory) {
    $this->tempStoreFactory = $temp_store_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    $store = $this->tempStoreFactory->get('mass_contact_confirm_info');
    if ($mass_contact_message_info = $store->get($value)) {
      return $mass_contact_message_info;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    if (!empty($definition['type']) && $definition['type'] == 'mass_contact_confirm_info') {
      return TRUE;
    }
    return FALSE;
  }

}
