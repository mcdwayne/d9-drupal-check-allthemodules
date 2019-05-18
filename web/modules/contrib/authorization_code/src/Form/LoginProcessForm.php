<?php

namespace Drupal\authorization_code\Form;

use Drupal\authorization_code\Entity\LoginProcess;
use Drupal\authorization_code\Exceptions\InvalidLoginProcessConfiguration;
use Drupal\authorization_code\Plugin\AuthorizationCodePluginBase;
use Drupal\authorization_code\PluginManager;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginFormInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * LoginProcess create/edit form.
 */
class LoginProcessForm extends EntityForm {

  /**
   * The user identifier plugin manager.
   *
   * @var \Drupal\authorization_code\PluginManager
   */
  protected $userIdentifierManager;

  /**
   * The code generator plugin manager.
   *
   * @var \Drupal\authorization_code\PluginManager
   */
  protected $codeGeneratorManager;

  /**
   * The code sender plugin manager.
   *
   * @var \Drupal\authorization_code\PluginManager
   */
  protected $codeSenderManager;

  /**
   * LoginProcessForm constructor.
   *
   * @param \Drupal\authorization_code\PluginManager $user_identifier_manager
   *   The user identifier plugin manager.
   * @param \Drupal\authorization_code\PluginManager $code_generator_manager
   *   The code generator plugin manager.
   * @param \Drupal\authorization_code\PluginManager $code_sender_manager
   *   The code sender plugin manager.
   */
  public function __construct(PluginManager $user_identifier_manager, PluginManager $code_generator_manager, PluginManager $code_sender_manager) {
    $this->userIdentifierManager = $user_identifier_manager;
    $this->codeGeneratorManager = $code_generator_manager;
    $this->codeSenderManager = $code_sender_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.user_identifier'),
      $container->get('plugin.manager.code_generator'),
      $container->get('plugin.manager.code_sender')
    );
  }

  /**
   * The plugin manager.
   *
   * @param string $plugin_type
   *   The plugin type.
   *
   * @return \Drupal\authorization_code\PluginManager
   *   The plugin manager.
   */
  private function pluginManager(string $plugin_type) {
    switch ($plugin_type) {
      case 'user_identifier':
        return $this->userIdentifierManager;

      case 'code_generator':
        return $this->codeGeneratorManager;

      case 'code_sender':
        return $this->codeSenderManager;
    }
    throw new \InvalidArgumentException("No such plugin manager: $plugin_type");
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $this->loginProcess()->label(),
      '#maxlength' => 255,
      '#description' => $this->t("Label for the Login process."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->loginProcess()->id(),
      '#machine_name' => [
        'exists' => '\Drupal\authorization_code\Entity\LoginProcess::load',
      ],
      '#disabled' => !$this->loginProcess()->isNew(),
    ];

    $form['user_identifier'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('User identifier'),
      '#tree' => TRUE,
    ];
    $form['user_identifier']['plugin_id'] = [
      '#type' => 'select',
      '#required' => 'true',
      '#title' => $this->t('plugin'),
      '#description' => $this->t('The plugin that will identify the user.'),
      '#options' => $this->formOptionsForPlugin('user_identifier'),
      '#empty_option' => $this->t('- Select -'),
      '#ajax' => [
        'wrapper' => 'user-identifier-settings',
        'callback' => '::returnPluginSettings',
      ],
    ];
    $form['user_identifier']['settings'] = [
      '#type' => 'details',
      '#id' => 'user-identifier-settings',
      '#title' => $this->t('Settings'),
      '#tree' => TRUE,
    ];

    $form['code_generator'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Code generator'),
      '#tree' => TRUE,
    ];
    $form['code_generator']['plugin_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Plugin'),
      '#required' => 'true',
      '#description' => $this->t('The plugin that will generate the authorization codes.'),
      '#options' => $this->formOptionsForPlugin('code_generator'),
      '#empty_option' => $this->t('- Select -'),
      '#ajax' => [
        'wrapper' => 'code-generator-settings',
        'callback' => '::returnPluginSettings',
      ],
    ];
    $form['code_generator']['settings'] = [
      '#type' => 'details',
      '#id' => 'code-generator-settings',
      '#title' => $this->t('Settings'),
      '#tree' => TRUE,
    ];

    $form['code_sender'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Code sender'),
      '#tree' => TRUE,
    ];
    $form['code_sender']['plugin_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Plugin'),
      '#required' => 'true',
      '#description' => $this->t('The plugin that will send the authorization code to the user.'),
      '#options' => $this->formOptionsForPlugin('code_sender'),
      '#empty_option' => $this->t('- Select -'),
      '#ajax' => [
        'wrapper' => 'code-sender-settings',
        'callback' => '::returnPluginSettings',
      ],
    ];
    $form['code_sender']['settings'] = [
      '#type' => 'details',
      '#id' => 'code-sender-settings',
      '#title' => $this->t('Settings'),
      '#tree' => TRUE,
    ];

    $this->foreachPluginFromStateOrEntity($form_state, function (string $plugin_type, AuthorizationCodePluginBase $plugin) use (&$form, $form_state) {
      $form[$plugin_type]['plugin_id']['#default_value'] = $plugin->getPluginId();

      if (!$plugin->isBroken() && $plugin instanceof PluginFormInterface) {
        $form[$plugin_type]['settings'] = $this->buildPluginConfigurationForm($plugin_type, $plugin, $form, $form_state);
      }
      else {
        $form[$plugin_type]['settings']['#attributes']['class'] = ['visually-hidden'];
      }
    });

    return $form;
  }

  /**
   * Returns the plugin settings subform.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return mixed
   *   The plugin settings subform.
   */
  public function returnPluginSettings(array &$form, FormStateInterface $form_state) {
    $plugin_type = $form_state->getTriggeringElement()['#array_parents'][0];

    return $form[$plugin_type]['settings'];
  }

  /**
   * Builds the plugin configuration subform.
   *
   * @param string $plugin_type
   *   The plugin type.
   * @param \Drupal\authorization_code\Plugin\AuthorizationCodePluginBase $plugin
   *   The plugin.
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The plugin configuration subform.
   */
  private function buildPluginConfigurationForm(string $plugin_type, AuthorizationCodePluginBase $plugin, array &$form, FormStateInterface $form_state) {
    $element = $form[$plugin_type]['settings'];

    if (!$plugin->isBroken()) {
      $element['#open'] = TRUE;
      $element = $plugin->buildConfigurationForm($element,
        SubformState::createForSubform($element, $form, $form_state));
    }

    return $element;
  }

  /**
   * Executes a callback function for each plugin from the form state or entity.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param callable $callback
   *   The callback, should accept 2 arguments plugin_type (string) and the
   *   plugin object.
   */
  private function foreachPluginFromStateOrEntity(FormStateInterface $form_state, callable $callback) {
    foreach (LoginProcess::PLUGIN_TYPES as $plugin_type) {
      $plugin = $this->getPluginFromStateOrEntity($plugin_type, $form_state);
      call_user_func_array($callback, [$plugin_type, $plugin]);
    }
  }

  /**
   * Loads the plugin from either the form state or the entity.
   *
   * @param string $plugin_type
   *   The plugin type.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return false|\Drupal\authorization_code\CodeGeneratorInterface|\Drupal\authorization_code\CodeSenderInterface|\Drupal\authorization_code\UserIdentifierInterface
   *   The loaded plugin, or false if no plugin could have been loaded.
   */
  private function getPluginFromStateOrEntity(string $plugin_type, FormStateInterface $form_state) {
    if (!empty($form_state->getValue([$plugin_type, 'plugin_id']))) {
      $plugin = $this->pluginInstanceFromState($plugin_type, $form_state);
    }
    else {
      try {
        $plugin = $this->loginProcess()->getPluginOrThrowException($plugin_type);
      }
      catch (InvalidLoginProcessConfiguration $e) {
        $plugin = $this->pluginManager($plugin_type)
          ->createPluginInstanceWithFallback('broken');
      }
    }

    return $plugin;
  }

  /**
   * Tries to load the plugin from the form state.
   *
   * @param string $plugin_type
   *   The plugin type.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return false|\Drupal\authorization_code\CodeGeneratorInterface|\Drupal\authorization_code\CodeSenderInterface|\Drupal\authorization_code\UserIdentifierInterface
   *   The loaded plugin, or false if no plugin could have been loaded.
   */
  private function pluginInstanceFromState(string $plugin_type, FormStateInterface $form_state) {
    $plugin_id = $form_state->getValue([$plugin_type, 'plugin_id'], 'broken');
    $plugin_settings = $form_state->getValue($plugin_type, []);
    try {
      return $this->pluginManager($plugin_type)
        ->createInstance($plugin_id, $plugin_settings);
    }
    catch (PluginException $e) {
      return FALSE;
    }
  }

  /**
   * The form options array for a plugin type.
   *
   * @param string $plugin_type
   *   The plugin type.
   *
   * @return array
   *   The form options.
   */
  private function formOptionsForPlugin(string $plugin_type) {
    $options = [];
    $definitions = $this->pluginManager($plugin_type)->getDefinitions();
    foreach ($definitions as $definition) {
      if ('broken' !== $definition['id']) {
        $options[$definition['id']] = $definition['title'];
      }
    }
    return $options;
  }

  /**
   * The login process entity.
   *
   * @return \Drupal\authorization_code\Entity\LoginProcess
   *   The login process entity.
   */
  private function loginProcess(): LoginProcess {
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $this->foreachPluginFromStateOrEntity($form_state, function ($plugin_type, PluginFormInterface $plugin) use (&$form, $form_state) {
      if ($plugin) {
        $plugin->validateConfigurationForm($form[$plugin_type],
          SubformState::createForSubform($form[$plugin_type], $form, $form_state));
      }
    });
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->foreachPluginFromStateOrEntity($form_state, function ($plugin_type, PluginFormInterface $plugin) use (&$form, $form_state) {
      if ($plugin) {
        $plugin->submitConfigurationForm($form[$plugin_type],
          SubformState::createForSubform($form[$plugin_type], $form, $form_state));
      }
    });
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    parent::copyFormValuesToEntity($entity, $form, $form_state);
    foreach (LoginProcess::PLUGIN_TYPES as $plugin_type) {
      $this->loginProcess()
        ->set($plugin_type, $form_state->getValue($plugin_type));
    }
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = $this->loginProcess()->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Login process.', [
          '%label' => $this->loginProcess()->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Login process.', [
          '%label' => $this->loginProcess()->label(),
        ]));
    }
    $form_state->setRedirectUrl($this->loginProcess()->toUrl('collection'));
  }

}
