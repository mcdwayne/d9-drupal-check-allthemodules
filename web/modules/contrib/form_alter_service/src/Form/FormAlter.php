<?php

namespace Drupal\form_alter_service\Form;

use Drupal\form_alter_service\FormAlterBase;

/**
 * Container for the form alter services.
 *
 * @ingroup form_api
 */
class FormAlter {

  const SERVICE_ID = 'form_alter';

  /**
   * Stores the form alter services.
   *
   * @var \Drupal\form_alter_service\FormAlterBase[][]
   */
  protected $services = [];

  /**
   * Registers a form alter service.
   *
   * @param string $id
   *   The form ID or base form ID.
   * @param \Drupal\form_alter_service\FormAlterBase $service
   *   A form alter service.
   */
  public function registerService(string $id, FormAlterBase $service) {
    $this->services[$id][] = $service;
  }

  /**
   * Returns the list of form alter services.
   *
   * @param string $id
   *   The form ID or base form ID.
   *
   * @return \Drupal\form_alter_service\FormAlterBase[]
   *   The list of form alter services.
   */
  public function getServices(string $id): array {
    return $this->services[$id] ?? [];
  }

}
