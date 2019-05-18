<?php

namespace Drupal\link_partners\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a form that configures Linkfeed forms module settings.
 */
class MainlinkForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'link_partners_mainlink_settings';
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

    $form['mainlink'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('@partner settings', [
        '@partner' => 'Mainlink',
      ]),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];

    $form['mainlink']['id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ID'),
      '#description' => $this->t('This is ID you have get on site <a href="@link" target="_blank">@partner</a> (example, bla3bla2bla1bla6blabla3bla2bla1).', [
        '@link' => 'http://www.mainlink.ru/xmy/web/xscript/start.aspx?id=87',
        '@partner' => 'MainLink',
      ]),
      '#default_value' => $config->get('mainlink.id'),
      '#size' => 60,
      '#maxlength' => 100,
      '#weight' => -10,
      '#required' => TRUE,
    ];

    $form['mainlink']['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debug links'),
      '#description' => $this->t('If debug is enabled...'),
      '#default_value' => $config->get('mainlink.debug'),
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

    if ($values['id'] !== $config->get('mainlink.id') && !file_exists('public://link_partners/mainlink/' . $config->get('mainlink.id'))) {
      $directory = \Drupal::service('file_system')
        ->realpath(file_default_scheme() . '://link_partners/mainlink/' . $config->get('mainlink.id'));
      array_map('unlink', glob("$directory/*.*"));
      \Drupal::service('file_system')
        ->rmdir(file_default_scheme() . '://link_partners/mainlink/' . $config->get('mainlink.id'));
    }

    if (!file_exists('public://link_partners/')) {
      \Drupal::service('file_system')
        ->mkdir(file_default_scheme() . '://link_partners/');
    }
    if (!file_exists('public://link_partners/mainlink/' . $values['id'])) {
      \Drupal::service('file_system')
        ->mkdir(file_default_scheme() . '://link_partners/mainlink/' . $values['id']);
    }

    $config->set('mainlink.debug', $values['debug'])
      ->set('mainlink.id', $values['id'])
      ->save();

  }

}
