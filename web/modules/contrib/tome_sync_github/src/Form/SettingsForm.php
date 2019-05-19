<?php

namespace Drupal\tome_sync_github\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;

/**
 * Form builder for the settings.
 *
 * @internal
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tome_sync_github_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['tome_sync_github.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('tome_sync_github.settings');

    $form['basic'] = [];

    $form['basic']['build_directory'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Build directory'),
      '#description' => $this->t('Build directory'),
      '#default_value' => $config->get('build_directory'),
    ];

    $form['basic']['github_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Github project'),
      '#description' => $this->t('Github project name. e.g. vijaycs85/static-site'),
      '#default_value' => $config->get('github.name'),
    ];

    $form['basic']['github_branch'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Github branch'),
      '#description' => $this->t('Github branch name. e.g. gh-pages'),
      '#default_value' => $config->get('github.branch'),
    ];

    $form['publish'] = [
      '#type' => 'details',
      '#title' => t('Publish to Github'),
      '#open' => TRUE,
    ];
    $form['publish']['publish'] = [
      '#type' => 'submit',
      '#value' => t('publish'),
      '#submit' => ['::publishToGithub'],
    ];


    return $form;
  }


  /**
   * Publish.
   */
  public function publishToGithub(array &$form, FormStateInterface $form_state) {
    $publisher = \Drupal::service('tome_sync_github.publisher');
    $source_directory = Settings::get('tome_content_directory', '../content');
    $config = \Drupal::config('tome_sync_github.settings');

    $publisher->publish($source_directory, $config->get('github.branch'));
    $this->messenger()->addStatus($this->t('Published to github. Visit <a href="https://github.com/:name/tree/:branch" target="_blank">here</a> to see your changes.', [':name' => $config->get('github.name'), ':branch' => $config->get('github.branch')]));
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('tome_sync_github.settings')
      ->set('build_directory', $form_state->getValue('build_directory'))
      ->set('github.name', $form_state->getValue('github_name'))
      ->set('github.branch', $form_state->getValue('github_branch'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
