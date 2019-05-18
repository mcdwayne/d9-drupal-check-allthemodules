<?php

namespace Drupal\link_partners\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\link_partners\vendor\Linkfeed\LinkfeedClient;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a form that configures Linkfeed forms module settings.
 */
class LinkfeedForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'link_partners_linkfeed_settings';
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
    $form = [];
    $config = $this->config('link_partners.settings');
    $linkfeed = LinkfeedClient::getInstance([]);

    $form['linkfeed'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('@partner settings', [
        '@partner' => 'Linkfeed',
      ]),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];

    $form['linkfeed']['id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ID'),
      '#description' => $this->t('This is ID you have get on site <a href="@link" target="_blank">@partner</a> (example, bla3bla2bla1bla6blabla3bla2bla1).', [
        '@link' => 'http://www.linkfeed.ru/platforms/new',
        '@partner' => 'Linkfeed',
      ]),
      '#default_value' => $config->get('linkfeed.id'),
      '#size' => 60,
      '#maxlength' => 100,
      '#weight' => -10,
      '#required' => TRUE,
    ];

    $form['linkfeed']['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debug links'),
      '#description' => $this->t('If debug is enabled...'),
      '#default_value' => $config->get('linkfeed.debug'),
      '#weight' => -1,
    ];


    $form['linkfeed']['version'] = [
      '#type' => 'item',
      '#title' => t('Version code'),
      '#markup' => t('The script found and its version: <strong>@version</strong>.', [
        '@version' => $linkfeed->lc_version,
      ]),
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

    if ($values['id'] !== $config->get('linkfeed.id') && !file_exists('public://link_partners/linkfeed/' . $config->get('linkfeed.id'))) {
      $directory = \Drupal::service('file_system')
        ->realpath(file_default_scheme() . '://link_partners/linkfeed/' . $config->get('linkfeed.id'));
      array_map('unlink', glob("$directory/*.*"));
      \Drupal::service('file_system')
        ->rmdir(file_default_scheme() . '://link_partners/linkfeed/' . $config->get('linkfeed.id'));
    }

    if (!file_exists('public://link_partners/')) {
      \Drupal::service('file_system')
        ->mkdir(file_default_scheme() . '://link_partners/');
    }
    if (!file_exists('public://link_partners/linkfeed/' . $values['id'])) {
      \Drupal::service('file_system')
        ->mkdir(file_default_scheme() . '://link_partners/linkfeed/' . $values['id']);
    }

    $config->set('linkfeed.debug', $values['debug'])
      ->set('linkfeed.id', $values['id'])
      ->save();

  }

}
