<?php

namespace Drupal\blank_node_title\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManager;

/**
 * Settings Form.
 */
class Settings extends ConfigFormBase {

  protected $moduleHandler;
  protected $entityManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('entity.manager')
    );
  }

  /**
   * SettingsFormWarning constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module handler service.
   * @param \Drupal\Core\Entity\EntityManager $entityManager
   *   Entity Manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $moduleHandler, EntityManager $entityManager) {
    $this->moduleHandler = $moduleHandler;
    $this->entityManager = $entityManager;
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['blank_node_title.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'blank_node_title_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // If contact.module enabled, add contact forms.
    $config = $this->config('blank_node_title.settings');
    $enity_types = [
      'node' => [
        'name' => 'Node',
        'module' => 'node',
      ],
    ];

    foreach ($enity_types as $enity_type => $enity_info) {
      $name = $enity_info['name'];
      $form[$enity_type] = [
        '#type' => 'details',
        '#title' => $this->t('Non required @name-Title', ['@name' => $name]),
        '#open' => TRUE,
      ];
      if (!$this->moduleHandler->moduleExists($enity_info['module'])) {
        $form[$enity_type]['#open'] = FALSE;
        $form[$enity_type]["$enity_type-miss"] = [
          '#markup' => '<p>' . $this->t("Module '@module' not enabled.", ['@module' => $name]) . '</p>',
        ];
      }
      else {
        $form[$enity_type]["$enity_type-mode"] = [
          '#title' => $this->t("Display mode"),
          '#type' => 'radios',
          '#options' => [
            'disable' => 'Disable',
            'all' => 'All',
            'custom' => 'Custom bundles',
          ],
          '#default_value' => $config->get("$enity_type-mode"),
        ];
        $options = [];
        $bundles = $this->entityManager->getBundleInfo($enity_type);
        if (!empty($bundles)) {
          foreach ($bundles as $key => $value) {
            $options[$key] = $value['label'];
          }
          $form[$enity_type]["$enity_type-bundles"] = [
            '#title' => $this->t("@name bundles warning display on", ['@name' => $name]),
            '#type' => 'checkboxes',
            '#options' => $options,
          ];
          $default = $config->get("$enity_type-bundles");
          if (!empty($default)) {
            $form[$enity_type]["$enity_type-bundles"]['#default_value'] = $default;
          }
        }
      }
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
    $config = $this->config('blank_node_title.settings');
    $enity_types = [
      'node' => [
        'name' => 'Node',
        'module' => 'node',
      ],
    ];
    foreach ($enity_types as $enity_type => $enity_info) {
      $config
        ->set("$enity_type-mode", $form_state->getValue("$enity_type-mode"))
        ->set("$enity_type-bundles", $form_state->getValue("$enity_type-bundles"));
    }
    $config->save();
  }

}
