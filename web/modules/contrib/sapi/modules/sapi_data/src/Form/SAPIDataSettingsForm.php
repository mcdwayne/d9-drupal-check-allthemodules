<?php

namespace Drupal\sapi_data\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\sapi\Plugin\StatisticsPluginManager;

/**
 * Class SAPIDataSettingsForm.
 *
 * @package Drupal\sapi_data\Form
 */
class SAPIDataSettingsForm extends ConfigFormBase {

  /**
   * Drupal\sapi\Plugin\StatisticsPluginManager definition.
   *
   * @var \Drupal\sapi\Plugin\StatisticsPluginManager
   */
  protected $plugin_manager_statisticsplugin;

  public function __construct(
    ConfigFactoryInterface $config_factory,
      StatisticsPluginManager $plugin_manager_statisticsplugin
    ) {
    parent::__construct($config_factory);
    $this->plugin_manager_statisticsplugin = $plugin_manager_statisticsplugin;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.statisticsplugin')
    );
  }


  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'sapi_data.sapidatasettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sapi_data_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('sapi_data.sapidatasettings');
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

    $this->config('sapi_data.sapidatasettings')
      ->save();
  }

}
