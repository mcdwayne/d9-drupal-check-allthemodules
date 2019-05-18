<?php

/**
 * @file
 * Contains \Drupal\orgmode\Form\AdministerOrgmode.
 */

namespace Drupal\orgmode\Form;


use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;


/**
 * Class AdministerOrgmode.
 *
 * @package Drupal\orgmode\Form
 */
class AdministerOrgmode extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'administer_orgmode';
  }

  /**
   * Get the editable config names.
   */
  protected function getEditableConfigNames() {
    return [
      'orgmode.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('orgmode.settings');

    $form['published'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Published'),
      '#default_value' => $config->get('published'),
    );

    $form['sticky'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Sticky'),
      '#default_value' => $config->get('sticky'),
    );

    $form['promote'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Promote to front'),
      '#default_value' => $config->get('promote'),
    );

    return parent::buildForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('orgmode.settings')
      ->set('published', $form_state->getValue('published'))
      ->set('sticky', $form_state->getValue('sticky'))
      ->set('promote', $form_state->getValue('promote'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
