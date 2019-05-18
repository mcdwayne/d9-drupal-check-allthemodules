<?php

namespace Drupal\link_partners\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a form that configures Sape forms module settings.
 */
class SapeForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'link_partners_sape_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'link_partners.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('link_partners.settings');
    $form = [];

    $form['sape'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('@partner settings', [
        '@partner' => 'Sape',
      ]),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];

    $form['sape']['id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ID'),
      '#description' => $this->t('This is ID you have get on site <a href="@link" target="_blank">@partner</a> (example, bla3bla2bla1bla6blabla3bla2bla1).', [
        '@link' => 'https://www.sape.ru/site.php?act=add',
        '@partner' => 'Sape',
      ]),
      '#default_value' => $config->get('sape.id'),
      '#size' => 60,
      '#maxlength' => 100,
      '#weight' => -10,
      '#required' => TRUE,
    ];

    $form['sape']['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debug links'),
      '#description' => $this->t('If debug is enabled...'),
      '#default_value' => $config->get('sape.debug'),
      '#weight' => -1,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message($this->t('Configuration success saved'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->config('link_partners.settings');

    if ($values['id'] !== $config->get('sape.id') && !file_exists('public://link_partners/sape/' . $config->get('sape.id'))) {
      $directory = \Drupal::service('file_system')
        ->realpath(file_default_scheme() . '://link_partners/sape/' . $config->get('sape.id'));
      array_map('unlink', glob("$directory/*.*"));
      \Drupal::service('file_system')
        ->rmdir(file_default_scheme() . '://link_partners/sape/' . $config->get('sape.id'));
    }

    if (!file_exists('public://link_partners/')) {
      \Drupal::service('file_system')
        ->mkdir(file_default_scheme() . '://link_partners/');
    }
    if (!file_exists('public://link_partners/sape/' . $values['id'])) {
      \Drupal::service('file_system')
        ->mkdir(file_default_scheme() . '://link_partners/sape/' . $values['id']);
    }

    $config->set('sape.debug', $values['debug'])
      ->set('sape.id', $values['id'])
      ->save();

  }

}
