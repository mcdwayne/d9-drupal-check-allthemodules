<?php

/**
 * @file
 * Contains \Drupal\mpac\AutocompleteSettingsForm.
 */

namespace Drupal\mpac;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\Context\ContextInterface;
use Drupal\Core\Extension\ModuleHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure user settings for this site.
 */
class AutocompleteSettingsForm extends ConfigFormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Constructs a \Drupal\mpac\AutocompleteSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\Context\ContextInterface $context
   *   The configuration context.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactory $config_factory, ContextInterface $context, ModuleHandler $module_handler) {
    parent::__construct($config_factory, $context);
    $this->moduleHandler = $module_handler;
  }

  /**
   * Implements \Drupal\Core\ControllerInterface::create().
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'), $container->get('config.context.free'), $container->get('module_handler')
    );
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'mpac_autocomplete_settings';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, array &$form_state) {
    $config = $this->configFactory->get('mpac.autocomplete');

    $form['mpac_max_items'] = array(
      '#type' => 'select',
      '#title' => t('Maximum number of items'),
      '#description' => t('Select the maximum number of items in an autocomplete list provided by %mpac.', array('%mpac' => 'Multi-path autocomplete')),
      '#default_value' => $config->get('items.max'),
      '#options' => drupal_map_assoc(array(10, 20, 50, 100)),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {
    parent::submitForm($form, $form_state);

    $this->configFactory->get('mpac.autocomplete')
      ->set('items.max', $form_state['values']['mpac_max_items'])
      ->save();
  }

}
