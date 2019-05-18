<?php

namespace Drupal\devel_clipboard\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DevelCliboardSettingsForm.
 */
class DevelClipboardSettingsForm extends ConfigFormBase {

  const COUNTS = [
    '5' => '5',
    '10' => '10',
    '20' => '20',
    '30' => '30',
    '40' => '40',
    '50' => '50',
    '70' => '70',
    '100' => '100',
  ];

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('library.discovery.collector')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'devel_clipboard_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('devel_clipboard.settings');
    $form['enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Clipboard'),
      '#default_value' => $config->get('enable'),
    ];
    $form['clipboardCount'] = [
      '#type' => 'select',
      '#title' => $this->t('Clipboard Count'),
      '#description' => $this->t('How many clipboard code want to store.'),
      '#options' => self::COUNTS,
      '#default_value' => $config->get('clipboardCount'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $form_state->cleanValues();

    $this->config('devel_clipboard.settings')
      ->setData($form_state->getValues())
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'devel_clipboard.settings',
    ];
  }

}
