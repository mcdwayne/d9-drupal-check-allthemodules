<?php

/**
 * @file
 * Contains Drupal\akv_pagepeels\Form\DefaultForm.
 */

namespace Drupal\akv_pagepeels\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class DefaultForm.
 *
 * @package Drupal\akv_pagepeels\Form
 */
class DefaultForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'akv_pagepeels.settings'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'akv_pagepeels_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('akv_pagepeels.settings');
    $form['akv_pagepeels_type'] = array(
      '#type' => 'radios',
      '#required' => true,
      '#title' => $this->t('Pagepeel type'),
      '#description' => $this->t('Choose the type of collapsed pagepeel'),
      '#options' => array(
        'akv_pagepeels_latest' => $this->t('Latest pagepeel from AKV'),
        'akv_pagepeels_schaeuble' => $this->t('Stasi 2.0 classic (Schäublone)'),
        'akv_pagepeels_schaeuble_green' => $this->t('Stasi 2.0 green (Schäublone)'),
        'akv_pagepeels_cam' => $this->t('Surveilance camera(2D)'),
        'akv_pagepeels_3d_cam' => $this->t('Surveilance camera(3D)'),
        'akv_pagepeels_ani_cam' => $this->t('Surveilance camera(3D + animated)'),
        'akv_pagepeels_trauer' => $this->t('Condolement, 1949 - 2007 t'),
        'akv_pagepeels_de_maiziere' => $this->t('Thomas de Maizière'),
      ),
      '#default_value' => $config->get('akv_pagepeels_type'),
    );
    $form['akv_pagepeels_info'] = array(
      '#type' => 'radios',
      '#required' => true,
      '#title' => $this->t('Show info'),
      '#description' => t('Show info icon under the pagepeel'),
      '#options' => array(
        'akv_pagepeels' => $this->t('Show'),
        'akv_pagepeels_no_info' => $this->t('Don´t show')
      ),
      '#default_value' => $config->get('akv_pagepeels_info'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('akv_pagepeels.settings')
      ->set('akv_pagepeels_type', $form_state->getValue('akv_pagepeels_type'))
      ->set('akv_pagepeels_info', $form_state->getValue('akv_pagepeels_info'))
      ->save();
    parent::submitForm($form, $form_state);
  }
}