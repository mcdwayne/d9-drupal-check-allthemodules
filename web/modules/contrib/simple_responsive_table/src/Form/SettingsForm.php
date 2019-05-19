<?php

namespace Drupal\simple_responsive_table\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\simple_responsive_table\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Module Handler Interface.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * SettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config Factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module Handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['simple_responsive_table.settings'];
  }

  /**
   * Simple Responsive Table config form.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   Renderable form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('simple_responsive_table.settings');
    $form['max_width'] = [
      '#type' => 'textfield',
      '#title' => t('Max Width.'),
      '#description' => t('Make table responsive till the screen of size.'),
      '#default_value' => $config->get('max_width'),
    ];

    $form['enable_admin_page'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable for admin pages'),
      '#description' => t('Make table responsive for admin pages.'),
      '#default_value' => $config->get('enable_admin_page'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!is_numeric($form_state->getValue('max_width'))) {
      $form_state->setErrorByName('max_width', t('Max width should be number.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('simple_responsive_table.settings')
      ->set('max_width', $form_state->getValue('max_width'))
      ->set('enable_admin_page', (boolean) $form_state->getValue('enable_admin_page'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_responsive_table_settings';
  }

}
