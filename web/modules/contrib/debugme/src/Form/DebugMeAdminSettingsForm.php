<?php

namespace Drupal\debugme\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure DebugMe settings for this site.
 */
class DebugMeAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'debugme_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['debugme.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('debugme.settings');

    $form['debugme_project'] = [
      '#default_value' => $config->get('project'),
      '#description' => $this->t('This ID is unique to each project'),
      '#maxlength' => 20,
      '#required' => TRUE,
      '#size' => 15,
      '#title' => $this->t('DebugMe Project ID'),
      '#type' => 'textfield',
    ];

    // Page specific visibility configurations.
    $visibility_request_path_pages = $config->get('visibility.request_path_pages');

    $options = [
      $this->t('Every page except the listed pages'),
      $this->t('The listed pages only'),
    ];
    $description = $this->t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", [
      '%blog' => '/blog',
      '%blog-wildcard' => '/blog/*',
      '%front' => '<front>',
    ]);

    $form['debugme_visibility_request_path_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Add DebugMe to specific pages'),
      '#options' => $options,
      '#default_value' => $config->get('visibility.request_path_mode'),
    ];
    $form['debugme_visibility_request_path_pages'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Pages'),
      '#title_display' => 'invisible',
      '#default_value' => !empty($visibility_request_path_pages) ? $visibility_request_path_pages : '',
      '#description' => $description,
      '#rows' => 10,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Trim some text values.
    $form_state->setValue('debugme_visibility_request_path_pages', trim($form_state->getValue('debugme_visibility_request_path_pages')));

    // Verify that every path is prefixed with a slash, but don't check PHP
    // code snippets.
    $pages = preg_split('/(\r\n?|\n)/', $form_state->getValue('debugme_visibility_request_path_pages'));
    foreach ($pages as $page) {
      if (strpos($page, '/') !== 0 && $page !== '<front>') {
        $form_state->setErrorByName('debugme_visibility_request_path_pages', $this->t('Path "@page" not prefixed with slash.', ['@page' => $page]));
        // Drupal forms show one error only.
        break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('debugme.settings');
    $config
      ->set('project', $form_state->getValue('debugme_project'))
      ->set('visibility.request_path_mode', $form_state->getValue('debugme_visibility_request_path_mode'))
      ->set('visibility.request_path_pages', $form_state->getValue('debugme_visibility_request_path_pages'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
