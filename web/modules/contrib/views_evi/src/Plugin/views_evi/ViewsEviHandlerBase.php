<?php

namespace Drupal\views_evi\Plugin\views_evi;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views_evi\ViewsEviFilterWrapper;
use Drupal\views_evi\ViewsEviHandlerInterface;

abstract class ViewsEviHandlerBase implements ViewsEviHandlerInterface {

  use StringTranslationTrait;

  /** @var \Drupal\views_evi\ViewsEviFilterWrapper $filter_wrapper */
  protected $filter_wrapper;

  /**
   * {@inheritdoc}
   */
  public function setFilterWrapper(ViewsEviFilterWrapper $filter_wrapper) {
    $this->filter_wrapper = $filter_wrapper;
  }

  /**
   * {@inheritdoc}
   */
  public function getFilterWrapper() {
    return $this->filter_wrapper;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm($settings, &$form) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormValidate(&$form_values) {}

  /**
   * {@inheritdoc}
   */
  public function settingsFormSubmit($form_values) {
    return $form_values;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultSettings() {
    return array();
  }

}
