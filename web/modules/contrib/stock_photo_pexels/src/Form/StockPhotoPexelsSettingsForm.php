<?php

namespace Drupal\stock_photo_pexels\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Implements the Stock Photo Pexels Settings form controller.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class StockPhotoPexelsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'stock_photo_pexels_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'stock_photo_pexels.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('stock_photo_pexels.settings');

    $form['pexels_auth'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Pexels API Settings'),
    ];

    $form['pexels_auth']['help'] = [
      '#type' => '#markup',
      '#markup' => $this->t('To get your Pexels Authorization, you can request access @link.', [
        '@link' => Link::fromTextAndUrl('here', Url::fromUri('https://www.pexels.com/api/new/'))->toString()
      ]),
    ];

    $form['pexels_auth']['pexels_auth_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Authorization key'),
      '#default_value' => $config->get('pexels_auth_key'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('stock_photo_pexels.settings')
         ->set('pexels_auth_key', $form_state->getValue('pexels_auth_key'))
         ->save();

    parent::submitForm($form, $form_state);
  }

}
