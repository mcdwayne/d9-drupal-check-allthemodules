<?php

namespace Drupal\views_evi;

interface ViewsEviHandlerInterface {



  /**
   * @return string
   */
  public function getHandlerType();

  public function setFilterWrapper(ViewsEviFilterWrapper $filter_wrapper);

  /**
   * @return \Drupal\views_evi\ViewsEviFilterWrapper
   */
  public function getFilterWrapper();

  /**
   * @param array $settings
   * @param array $form
   * @return array
   */
  public function settingsForm($settings, &$form);

  /**
   * @param array $form_values
   */
  public function settingsFormValidate(&$form_values);

  /**
   * @param array $form_values
   * @return array
   */
  public function settingsFormSubmit($form_values);

  /**
   * @return array
   */
  public function defaultSettings();
}
