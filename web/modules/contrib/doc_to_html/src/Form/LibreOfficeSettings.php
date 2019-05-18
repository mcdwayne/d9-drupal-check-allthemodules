<?php

namespace Drupal\doc_to_html\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigManager;

/**
 * Class LibreOfficeSettings.
 *
 * @package Drupal\doc_to_html\Form
 */
class LibreOfficeSettings extends ConfigFormBase {

  /**
   * Drupal\Core\Config\ConfigManager definition.
   *
   * @var \Drupal\Core\Config\ConfigManager
   */
  protected $libreofficesettings;
  public function __construct(
    ConfigFactoryInterface $config_factory,
      ConfigManager $config_manager
    ) {
    parent::__construct($config_factory);
        $this->libreofficesettings = $config_manager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
            $container->get('config.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'doc_to_html.libreofficesettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'libre_office_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('doc_to_html.libreofficesettings');
    $form['base_path_application'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Base Path Application'),
      '#description' => $this->t('This is a base path for application libreoffice'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('base_path_application'),
    ];
    $form['command'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Command'),
      '#description' => $this->t('The command name to execute from commandline'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('command'),
    ];
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
    parent::submitForm($form, $form_state);

    $this->config('doc_to_html.libreofficesettings')
      ->set('base_path_application', $form_state->getValue('base_path_application'))
      ->set('command', $form_state->getValue('command'))
      ->save();
  }
}
