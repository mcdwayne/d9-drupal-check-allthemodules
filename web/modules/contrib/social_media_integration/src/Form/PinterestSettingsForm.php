<?php

/**
 * @file
 * Contains \Drupal\sjisocialconnect\PinterestSettingsForm.
 */

namespace Drupal\sjisocialconnect\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Plugin\Context\ContextInterface;
use Drupal\Core\Extension\ModuleHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;


/**
 * Configure user settings for this site.
 */
class PinterestSettingsForm extends ConfigFormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Constructs a \Drupal\user\AccountSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Plugin\Context\ContextInterface $context
   *   The configuration context.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactory $config_factory, ModuleHandler $module_handler) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
  }

  /**
   * Implements \Drupal\Core\ControllerInterface::create().
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'sjisocialconnect_pinterest';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['sjisocialconnect.pinterest'];
  }


  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type = 'new') {
    $config = $this->config('sjisocialconnect.pinterest');

    $form['config'] = array(
      '#type' => 'select',
      '#title' => t('Pin Count'),
      '#options' => array(
        'above' => t('Above the Button'),
        'beside' => t('Beside the Button'),
        'none' => t('Not Shown'),
      ),
      '#default_value' => $config->get('config'),
    );

    $form['image'] = array( //provide a list of images?
      '#type' => 'textfield',
      '#title' => t('Image'),
      '#default_value' => $config->get('image'),
    );

    $form['description'] = array(
      '#type' => 'textfield',
      '#title' => t('Description'),
      '#default_value' => $config->get('description'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('sjisocialconnect.pinterest');

    $config
      ->set('config', $form_state->getValue('config'))
      ->set('image', $form_state->getValue('image'))
      ->set('description', $form_state->getValue('description'))
      ->save();
  }

}
