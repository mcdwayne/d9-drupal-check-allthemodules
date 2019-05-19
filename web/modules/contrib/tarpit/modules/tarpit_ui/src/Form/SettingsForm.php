<?php

/**
 * @file
 * Contains \Drupal\tarpit_ui\Form\SettingsForm.
 */
namespace Drupal\tarpit_ui\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class SettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tarpit_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('tarpit.config');

    $form['paths'] = array(
      '#type' => 'textarea',
      '#title' => 'Paths where the Tarpit is enabled',
      '#description' => 'Path on which to enable the trap. One per line.',
      '#default_value' => trim(implode(PHP_EOL, $config->get('paths'))),
    );

    $client = new \GuzzleHttp\Client();
    $url = Url::fromUri('internal:/robots.txt');
    $url->setAbsolute(TRUE);
    $response = $client->get($url->toString());

    if ($response->getStatusCode() == 200) {
      $form['paths_documentation'] = array(
        '#type' => 'details',
        '#description' => 'Look at the content of the robots.txt file to help you find paths.',
        '#title' => 'Content of robots.txt',
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
        'robots' => array(
          '#type' => 'textarea',
          '#rows' => 15,
          '#default_value' => $response->getBody(),
          '#disabled' => TRUE,
        )
      );
    }

    $form['page_title'] = array(
      '#type' => 'textfield',
      '#title' => 'Tarpits page title',
      '#default_value' => $config->get('page_title'),
      '#description' => 'Tarpits page title.',
    );

    $form['depth'] = array(
      '#type' => 'number',
      '#title' => 'Tarpit maximum depth',
      '#min' => 1,
      '#step' => 1,
      '#default_value' => $config->get('depth'),
    );

    $form['size'] = array(
      '#type' => 'number',
      '#title' => 'Content size',
      '#min' => 1,
      '#step' => 1,
      '#default_value' => $config->get('size'),
      '#description' => 'Number of words in the content.',
    );

    $form['links'] = array(
      '#type' => 'number',
      '#title' => 'Links',
      '#min' => 1,
      '#step' => 1,
      '#default_value' => $config->get('links'),
      '#description' => 'Number of links in the content.',
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state->setValue('depth', intval($form_state->getValue('depth')));
    $form_state->setValue('paths', explode(PHP_EOL, $form_state->getValue('paths')));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('tarpit.config')
      ->set('depth', $form_state->getValue('depth'))
      ->set('paths', $form_state->getValue('paths'))
      ->set('size', $form_state->getValue('size'))
      ->set('links', $form_state->getValue('links'))
      ->set('page_title', $form_state->getValue('page_title'))
      ->save();

    \Drupal::cache('config')->deleteAll();
    \Drupal::service("router.builder")->rebuild();

    parent::submitForm($form, $form_state);
  }

  protected function getEditableConfigNames() {
    return ['tarpit.config'];
  }

}