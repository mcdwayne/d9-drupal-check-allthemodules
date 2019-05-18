<?php

namespace Drupal\bynder\Plugin\EntityBrowser\Widget;

use Drupal\bynder\BynderApiInterface;
use Drupal\bynder\Exception\BundleNotBynderException;
use Drupal\bynder\Exception\BundleNotExistException;
use Drupal\bynder\Exception\UnableToConnectException;
use Drupal\bynder\Plugin\media\Source\Bynder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\entity_browser\WidgetBase;
use Drupal\entity_browser\WidgetValidationManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Base class for Bynder Entity browser widgets.
 */
abstract class BynderWidgetBase extends WidgetBase {

  /**
   * Bynder API service.
   *
   * @var \Drupal\bynder\BynderApiInterface
   */
  protected $bynderApi;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * BynderWidgetBase constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\entity_browser\WidgetValidationManager $validation_manager
   *   The Widget Validation Manager service.
   * @param \Drupal\bynder\BynderApiInterface $bynder_api
   *   Bynder API service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Logger factory.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager, WidgetValidationManager $validation_manager, BynderApiInterface $bynder_api, LoggerChannelFactoryInterface $logger_factory, LanguageManagerInterface $language_manager, RequestStack $request_stack, ConfigFactoryInterface $config_factory) {
    $this->bynderApi = $bynder_api;
    $this->loggerFactory = $logger_factory;
    $this->languageManager = $language_manager;
    $this->requestStack = $request_stack;
    $this->config = $config_factory;

    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_type_manager, $validation_manager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('event_dispatcher'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.entity_browser.widget_validation'),
      $container->get('bynder_api'),
      $container->get('logger.factory'),
      $container->get('language_manager'),
      $container->get('request_stack'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'media_type' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['media_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Media type'),
      '#default_value' => $this->configuration['media_type'],
      '#required' => TRUE,
      '#options' => [],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters) {
    $form = parent::getForm($original_form, $form_state, $additional_widget_parameters);

    if (!$this->checkType()) {
      $form_state->setValue('errors', TRUE);
      return $form;
    }

    // Check if the API configuration is in place and exit early if not.
    foreach (['consumer_key', 'consumer_secret', 'token', 'token_secret', 'account_domain'] as $key) {
      if ($this->config->get('bynder.settings')->get($key) === '') {
        $form_state->setValue('errors', TRUE);
        (new UnableToConnectException())->logException()->displayMessage();
        return $form;
      }
    }

    // Require oAuth authorization if we don't have a valid access token yet.
    // If we are submitting "Reload after submit" button right now we also need
    // to add it to the form for submission to work as expected. When the form
    // will be rebuild after the submit on same request we won't add it anymore.
    if (!$this->bynderApi->hasAccessToken() || ($this->requestStack->getCurrentRequest()->getMethod() == 'POST' && $this->requestStack->getCurrentRequest()->request->get('op') == 'Reload after submit' && $form_state->isProcessingInput() === NULL)) {
      $form_state->setValue('errors', TRUE);
      $form['message'] = [
        '#markup' => $this->t(
          'You need to <a href="#login" class="oauth-link">log into Bynder</a> before importing assets.'
        ),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ];
      $form['reload'] = [
        '#type' => 'button',
        '#value' => 'Reload after submit',
        '#attached' => ['library' => ['bynder/oauth']],
        '#attributes' => ['class' => ['oauth-reload', 'visually-hidden']],
      ];
      return $form;
    }

    return $form;
  }

  /**
   * Check that media type is properly configured.
   *
   * @return bool
   *   Returns TRUE if media type is configured correctly.
   */
  protected function checkType() {
    /** @var \Drupal\media\MediaTypeInterface $type */
    $type = $this->entityTypeManager->getStorage('media_type')
      ->load($this->configuration['media_type']);

    if (!$type) {
      (new BundleNotExistException(
        $this->configuration['media_type']
      ))->logException()->displayMessage();
      return FALSE;
    }
    elseif (!($type->getSource() instanceof Bynder)) {
      (new BundleNotBynderException($type->label()))->logException()
        ->displayMessage();
      return FALSE;
    }
    return TRUE;
  }

}
