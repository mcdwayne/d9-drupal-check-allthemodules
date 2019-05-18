<?php

namespace Drupal\search_365\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class AdministrationForm.
 *
 * @package Drupal\search_365\Form
 */
class AdministrationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_365_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return 'search_365.settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $settings = $this->configFactory()->get($this->getEditableConfigNames());

    $form['connection_info'] = [
      '#title' => $this->t('Connection Information'),
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['connection_info']['baseurl'] = [
      '#type' => 'url',
      '#title' => $this->t('Search 365 base URL'),
      '#description' => $this->t('Valid URL, including <em>http://</em> or <em>https://</em>. Do <b>not</b> include <em>/search</em> at the end, or a trailing slash, but you should include a port number if needed. Example: <em>http://my.example.com:8443</em>'),
      '#default_value' => $settings->get('connection_info.baseurl'),
      '#required' => TRUE,
    ];
    $form['connection_info']['collection'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Collection'),
      '#description' => $this->t('The name of a valid collection (case sensitive).'),
      '#default_value' => $settings->get('connection_info.collection'),
      '#required' => TRUE,
    ];
    $form['display_settings'] = [
      '#title' => $this->t('Search Interface Settings'),
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['display_settings']['drupal_path'] = [
      '#title' => $this->t('Search path'),
      '#type' => 'textfield',
      '#field_prefix' => '<span dir="ltr">' . Url::fromUserInput('/', [
        'absolute' => TRUE,
      ])->toString(),
      '#field_suffix' => '</span>&lrm;',
      '#default_value' => $settings->get('display_settings.drupal_path'),
      '#description' => $this->t('The URL of the search page provided by this module. Include neither leading nor trailing slash.'),
      '#required' => TRUE,
    ];
    $form['display_settings']['search_title'] = [
      '#title' => $this->t('Search Name'),
      '#type' => 'textfield',
      '#default_value' => $settings->get('display_settings.search_title'),
      '#description' => $this->t('Serves as the page title on results pages and the default menu item title.'),
      '#required' => FALSE,
    ];
    $form['display_settings']['page_size'] = [
      '#title' => $this->t('Page Size'),
      '#type' => 'number',
      '#min' => 1,
      '#max' => 100,
      '#default_value' => $settings->get('display_settings.page_size'),
      '#description' => $this->t('The number of results to return per page.'),
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->configFactory->getEditable($this->getEditableConfigNames())
      ->set('connection_info.baseurl', $form_state->getValue('baseurl'))
      ->set('connection_info.collection', $form_state->getValue('collection'))
      ->set('display_settings.drupal_path', trim($form_state->getValue('drupal_path'), '/'))
      ->set('display_settings.search_title', $form_state->getValue('search_title'))
      ->set('display_settings.page_size', $form_state->getValue('page_size'))
      ->save();

    \Drupal::service('router.builder')->rebuild();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (substr($form_state->getValue('baseurl'), -1) == '/') {
      $form_state->setErrorByName(
        'connection_info][baseurl',
        $this->t('Base URL must not end with a slash')
      );
    }
  }

}
