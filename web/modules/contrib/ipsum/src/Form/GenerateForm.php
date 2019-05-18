<?php

/**
 * @file
 * Contains \Drupal\ipsum\Form\GenerateForm.
 */

namespace Drupal\ipsum\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactory;
use Drupal\ipsum\Plugin\Type\IpsumPluginManager;
use Drupal\ipsum\Form\IpsumBaseForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure statistics settings for this site.
 */
class GenerateForm extends ConfigFormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The ipsum plugin manager.
   *
   * @var \Drupal\ipsum\Plugin\Type\IpsumPluginManager
   */
  protected $ipsumManager;

  /**
   * Constructs a \Drupal\ipsum\Form\GenerateForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\ipsum\Plugin\Type\IpsumPluginManager
   *   The ipsum plugin manager.
   */
  public function __construct(ConfigFactory $config_factory, ModuleHandlerInterface $module_handler, IpsumPluginManager $ipsum_manager) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
    $this->ipsumManager = $ipsum_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('plugin.manager.ipsum')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ipsum_generate_form';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, array &$form_state) {
    // Get base form.
    $base_form = IpsumBaseForm::buildForm($this->configFactory, $this->ipsumManager);

    // Override title.
    $base_form['provider']['#title'] = t('Flavor');

    // Merge forms.
    $form += $base_form;

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {
    // @TODO do something.

    parent::submitForm($form, $form_state);
  }
}
