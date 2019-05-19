<?php

namespace Drupal\simpleads;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface SimpleAdsCampaign plugins.
 */
interface SimpleAdsCampaignInterface extends PluginInspectionInterface {

  /**
   * Return SimpleAds type name.
   *
   * @return string
   */
  public function getName();

  /**
   * Return ad campaign form to create an ad.
   *
   * @return array form
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type = NULL, $id = NULL);

  /**
   * Create an ad campaign.
   */
  public function createFormSubmit($options, FormStateInterface $form_state, $type = NULL);

  /**
   * Update an ad campaign.
   */
  public function updateFormSubmit($options, FormStateInterface $form_state, $type = NULL, $id = NULL);

  /**
   * Delete an ad campaign.
   */
  public function deleteFormSubmit($options, FormStateInterface $form_state, $type = NULL, $id = NULL);

  /**
   * Render an ad.
   *
   * @return string
   */
  public function activate();

  /**
   * Render an ad.
   *
   * @return string
   */
  public function deactivate();

}
