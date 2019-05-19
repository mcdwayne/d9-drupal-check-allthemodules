<?php

namespace Drupal\simpleads;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface SimpleAdsType plugins.
 */
interface SimpleAdsTypeInterface extends PluginInspectionInterface {

  /**
   * Return SimpleAds type name.
   *
   * @return string
   */
  public function getName();

  /**
   * Return ad form to create an ad.
   *
   * @return array form
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type = NULL, $id = NULL);

  /**
   * Create an ad.
   */
  public function createFormSubmit($options, FormStateInterface $form_state, $type = NULL);

  /**
   * Update an ad.
   */
  public function updateFormSubmit($options, FormStateInterface $form_state, $type = NULL, $id = NULL);

  /**
   * Delete an ad.
   */
  public function deleteFormSubmit($options, FormStateInterface $form_state, $type = NULL, $id = NULL);

  /**
   * Theme function for the ad.
   *
   * @return array
   */
  public function theme();

  /**
   * Render an ad.
   *
   * @return string
   */
  public function render();

}
