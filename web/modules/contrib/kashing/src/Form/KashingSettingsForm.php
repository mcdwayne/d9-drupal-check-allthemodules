<?php

namespace Drupal\kashing\form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\kashing\form\View\KashingFormConfiguration;
use Drupal\kashing\form\View\KashingFormEdit;
use Drupal\kashing\form\View\KashingFormGeneral;
use Drupal\kashing\form\View\KashingFormNew;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Kashing Settings Form class.
 */
class KashingSettingsForm extends ConfigFormBase {

  private $modulePath;

  /**
   * KashingSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Standard config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Standard module handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);

    $this->modulePath = $module_handler->getModule('kashing')->getPath();
  }

  /**
   * KashingSettingsForm create function.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('config.factory'),
        $container->get('module_handler')
    );
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return [
      'kashing.settings', 'kashing.blocks.forms',
    ];
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'kashing_settings';
  }

  /**
   * Build form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Settings form initial fields and config.
    $form['#theme'] = 'system_config_form';
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $form['kashing_settings'] = [
      '#type' => 'vertical_tabs',
    ];

    // Adding form tabs.
    $kashing_form_configuration = new KashingFormConfiguration();
    $kashing_form_configuration->addConfigurationPage($form);

    $kashing_form_general = new KashingFormGeneral();
    $kashing_form_general->addGeneralPage($form);

    $kashing_form_new = new KashingFormNew();
    $kashing_form_new->addNewFormPage($form);

    $kashing_form_delete = new KashingFormEdit();
    $kashing_form_delete->addDeleteFormPage($form);

    return $form;
  }

  /**
   * Submit form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
