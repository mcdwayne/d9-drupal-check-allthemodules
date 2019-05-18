<?php

declare(strict_types = 1);

namespace Drupal\sendwithus\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sendwithus\ApiManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to manage sendwithus settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The api manager.
   *
   * @var \Drupal\sendwithus\ApiManager
   */
  protected $apiManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\sendwithus\ApiManager $apiManager
   *   The api key service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The storage.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ApiManager $apiManager, ModuleHandlerInterface $moduleHandler, EntityStorageInterface $storage) {
    parent::__construct($config_factory);

    $this->apiManager = $apiManager;
    $this->moduleHandler = $moduleHandler;
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('sendwithus.api_manager'),
      $container->get('module_handler'),
      $container->get('entity_type.manager')->getStorage('sendwithus_template')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sendwithus_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $default_mailer = $this->config('system.mail')
      ->get('interface.default');

    $form['set_default'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Set sendwithus as default mail handler'),
      '#description' => $this->t('Check this to use sendwithus as default mail handler.'),
      '#default_value' => $default_mailer === 'sendwithus_mail',
    ];

    $form['api_key'] = [
      '#type' => 'key_select',
      '#default_value' => $this->apiManager->getApiKey(),
      '#title' => $this->t('API key'),
    ];

    $form['templates'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Templates'),
      '#tree' => TRUE,
    ];

    $form['templates']['template'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Template ID'),
      '#description' => $this->t('The sendwithus Template ID'),
      '#default_value' => '',
    ];

    $form['templates']['module'] = [
      '#type' => 'select',
      '#title' => $this->t('Module'),
      '#options' => $this->getModulesList(),
      '#empty_option' => $this->t('- Select -'),
    ];

    $form['templates']['key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key'),
      '#description' => $this->t('The key is used to identify specific mails if the module sends more than one. Leave empty to use the configuration for all mails sent by the selected module.'),
      '#default_value' => '',
    ];

    $form['templates']['templates'] = [
      '#type' => 'table',
      '#header' => [
        'original_id' => NULL,
        'template' => $this->t('Template ID'),
        'key' => $this->t('Key'),
        'module' => $this->t('Module'),
        'remove' => $this->t('Remove'),
      ],
      '#empty' => $this->t('No templates set.'),
    ];

    /** @var \Drupal\sendwithus\Entity\Template $entity */
    foreach ($this->storage->loadMultiple() as $entity) {
      if (!$this->moduleHandler->moduleExists($entity->getModule())) {
        continue;
      }
      $row = [
        'original_id' => [
          '#type' => 'hidden',
          '#value' => $entity->id() ?? 0,
        ],
        'template' => [
          '#type' => 'textfield',
          '#default_value' => $entity->id(),
        ],
        'key' => [
          '#type' => 'textfield',
          '#default_value' => $entity->getKey(),
        ],
        'module' => [
          '#type' => 'select',
          '#disabled' => TRUE,
          '#options' => $this->getModulesList(),
          '#default_value' => $entity->getModule(),
        ],
        'remove' => [
          '#type' => 'checkbox',
        ],
      ];

      $form['templates']['templates'][] = $row;
    }


    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $set_default = $form_state->getValue('set_default');

    if ($set_default) {
      $this->configFactory->getEditable('system.mail')
        ->set('interface.default', 'sendwithus_mail')
        ->save();
    }
    $template = $form_state->getValue('templates');

    // Attempt to add new template.
    if (!empty($template['template'])) {
      $this->storage->create([
        'id' => $template['template'],
        'module' => $template['module'],
        'key' => $template['key'],
      ])->save();
    }

    // This will default to empty string by default.
    if (empty($template['templates'])) {
      $template['templates'] = [];
    }

    foreach ($template['templates'] ?? [] as $value) {
      list(
        'original_id' => $original_id,
        'template' => $template,
        'key' => $key,
        'remove' => $remove
        ) = $value;

      /** @var \Drupal\sendwithus\Entity\Template $entity */
      // Attempt to load with original id to allow rename.
      $entity = $this->storage->load($original_id ?? $template);

      if ($remove) {
        // Delete selected entities.
        $entity->delete();

        continue;
      }
      if ($original_id) {
        // Rename template.
        $entity->set('id', $template);
      }
      $entity->setKey($key)
        ->save();
    }
    $this->apiManager->setApiKey($form_state->getValue('api_key'));
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['sendwithus.settings'];
  }

  /**
   * Returns a list with all modules that send e-mails.
   *
   * Currently this is evaluated by the hook_mail implementation.
   *
   * @return string[]
   *   List of modules, keyed by the machine name.
   */
  protected function getModulesList() {
    $list = [];
    foreach ($this->moduleHandler->getImplementations('mail') as $module) {
      $list[$module] = $this->moduleHandler->getName($module);
    }
    asort($list);

    return $list;
  }

}
