<?php

/**
 * @file
 * Contains \Drupal\hubspot_forms\Plugin\Block\HubspotBlock.
 */

namespace Drupal\hubspot_forms\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hubspot_forms\HubspotFormsCore;

/**
 * Display Hubspot Form.
 *
 * @Block(
 *   id = "hubspot_forms",
 *   admin_label = @Translation("Hubspot Form")
 * )
 */
class HubspotBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'form_id' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $HubspotFormsCore = new HubspotFormsCore();
    $form['form_id'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Hubspot Form'),
      '#description'   => $this->t('Please choose a form you would like to display.'),
      '#options'       => $HubspotFormsCore->getFormIds(),
      '#default_value' => $this->configuration['form_id'],
      '#required'      => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['form_id'] = $form_state->getValue('form_id');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    list($portal_id, $form_id) = explode('::', $this->configuration['form_id']);
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    return [
      '#theme'     => 'hubspot_form',
      '#portal_id' => $portal_id,
      '#form_id'   => $form_id,
      '#locale'    => $langcode,
    ];
  }

}
