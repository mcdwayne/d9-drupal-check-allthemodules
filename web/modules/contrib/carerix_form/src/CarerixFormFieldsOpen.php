<?php

namespace Drupal\carerix_form;

/**
 * Class CarerixFormFieldsOpen.
 *
 * @package Drupal\carerix_form
 */
class CarerixFormFieldsOpen extends CarerixFormFieldsBase {

  const NAME = 'open_application';

  /**
   * Default settings.
   *
   * @var array
   *
   * @todo Form fields availability sync with Carerix system
   */
  protected $defaultSettings = [
    'lastName' => ['mandatory', 'locked'],
    'firstName' => ['mandatory', 'locked'],
    'password' => ['enabled', 'locked'],
    'gender' => ['enabled'],
    'cv' => ['mandatory', 'locked'],
    'photo' => ['enabled'],
    'emailAddress' => ['mandatory', 'locked'],
    'phoneNumber' => ['enabled'],
  ];

  /**
   * Get default settings.
   *
   * @return mixed
   *   Default settings.
   */
  public function getDefaultSettings() {
    return $this->defaultSettings;
  }

}
