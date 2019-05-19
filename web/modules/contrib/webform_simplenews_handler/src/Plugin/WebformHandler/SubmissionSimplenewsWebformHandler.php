<?php

namespace Drupal\webform_simplenews_handler\Plugin\WebformHandler;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\webform\WebformThemeManagerInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\Utility\WebformOptionsHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Save a webform submission's with automatic subscription to newsletter.
 *
 * @WebformHandler(
 *   id = "submission_newsletter",
 *   label = @Translation("Submission Newsletter"),
 *   category = @Translation("Newsletter"),
 *   description = @Translation("Sends a webform submission into newsletter."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class SubmissionSimplenewsWebformHandler extends WebformHandlerBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The webform token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * The webform theme manager.
   *
   * @var \Drupal\webform\WebformThemeManagerInterface
   */
  protected $themeManager;

  /**
   * A webform element plugin manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * Cache of default configuration values.
   *
   * @var array
   */
  protected $defaultValues;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, WebformSubmissionConditionsValidatorInterface $conditions_validator, AccountInterface $current_user, ModuleHandlerInterface $module_handler, LanguageManagerInterface $language_manager, WebformThemeManagerInterface $theme_manager, WebformTokenManagerInterface $token_manager, WebformElementManagerInterface $element_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger_factory, $config_factory, $entity_type_manager, $conditions_validator);
    $this->currentUser = $current_user;
    $this->moduleHandler = $module_handler;
    $this->languageManager = $language_manager;
    $this->themeManager = $theme_manager;
    $this->tokenManager = $token_manager;
    $this->elementManager = $element_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory'),
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('webform_submission.conditions_validator'),
      $container->get('current_user'),
      $container->get('module_handler'),
      $container->get('language_manager'),
      $container->get('webform.theme_manager'),
      $container->get('webform.token_manager'),
      $container->get('plugin.manager.webform.element')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'states' => [WebformSubmissionInterface::STATE_COMPLETED],
      'newsletters_lst' => [],
      'token' => 'default',
      'debug' => FALSE,
    ];
  }

  /**
   * Get configuration default values.
   *
   * @return array
   *   Configuration default values.
   */
  protected function getDefaultConfigurationValues() {
    if (isset($this->defaultValues)) {
      return $this->defaultValues;
    }

    $webform_settings = $this->configFactory->get('webform.settings');

    $this->defaultValues = [
      'states' => [WebformSubmissionInterface::STATE_COMPLETED],
      'token' => $webform_settings->get('submission_newsletter.token') ?: '',
      'newsletters_lst' => $webform_settings->get('submission_newsletter.newsletters_lst') ?: '',
    ];

    return $this->defaultValues;
  }

  /**
   * Get configuration default value.
   *
   * @param string $name
   *   Configuration name.
   *
   * @return string|array
   *   Configuration default value.
   */
  protected function getDefaultConfigurationValue($name) {
    $default_values = $this->getDefaultConfigurationValues();
    return $default_values[$name];
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {

    $settings = $this->getSubmissionNewsletterConfiguration();
    // Simplify the [webform_submission:values:.*] tokens.
    array_walk($settings, function (&$value, $key) {
      if (is_string($value)) {
        $value = preg_replace('/\[webform:([^:]+)\]/', '[\1]', $value);
        $value = preg_replace('/\[webform_role:([^:]+)\]/', '[\1]', $value);
        $value = preg_replace('/\[webform_submission:(?:node|source_entity|values):([^]]+)\]/', '[\1]', $value);
        $value = preg_replace('/\[webform_submission:([^]]+)\]/', '[\1]', $value);
        $value = preg_replace('/(:raw|:value)(:html)?\]/', ']', $value);
      }
    });

    $newsletter_options = $this->getNewsletterOptions();

    $settings['newsletters_lst'] = array_intersect_key($newsletter_options, array_combine($settings['newsletters_lst'], $settings['newsletters_lst']));

    $states = [
      WebformSubmissionInterface::STATE_DRAFT => $this->t('Draft Saved'),
      WebformSubmissionInterface::STATE_CONVERTED => $this->t('Converted'),
      WebformSubmissionInterface::STATE_COMPLETED => $this->t('Completed'),
      WebformSubmissionInterface::STATE_UPDATED => $this->t('Updated'),
    ];
    $settings['states'] = array_intersect_key($states, array_combine($settings['states'], $settings['states']));

    return [
      '#settings' => $settings,
    ] + parent::getSummary();
  }

  /**
   * Get mail configuration values.
   *
   * @return array
   *   An associative array containing email configuration values.
   */
  protected function getSubmissionNewsletterConfiguration() {
    $configuration = $this->getConfiguration();
    $submission_newsletter = [];
    foreach ($configuration['settings'] as $key => $value) {
      $submission_newsletter[$key] = ($value === 'default') ? $this->getDefaultConfigurationValue($key) : $value;
    }
    return $submission_newsletter;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    parent::applyFormStateToConfiguration($form_state);

    // Cleanup states.
    $this->configuration['states'] = array_values(array_filter($this->configuration['states']));
    $this->configuration['newsletters_lst'] = array_values(array_filter($this->configuration['newsletters_lst']));

    $values = $form_state->getValues();

    foreach ($this->configuration as $name => $value) {
      if (isset($values[$name])) {
        // Convert options array to safe config array to prevent errors.
        // @see https://www.drupal.org/node/2297311
        if (preg_match('/_options$/', $name)) {
          $this->configuration[$name] = WebformOptionsHelper::encodeConfig($values[$name]);
        }
        else {
          $this->configuration[$name] = $values[$name];
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getMessageSummary(array $message) {
    return [
      '#settings' => $message,
    ] + parent::getSummary();
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    $values = $form_state->getValues();

    $form_state->setValues($values);
    $this->applyFormStateToConfiguration($form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $results_disabled = $this->getWebform()->getSetting('results_disabled');

    $form['trigger'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Trigger'),
    ];
    $form['trigger']['states'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Execute'),
      '#options' => [
        WebformSubmissionInterface::STATE_DRAFT => $this->t('...when <b>draft</b> is saved.'),
        WebformSubmissionInterface::STATE_CONVERTED => $this->t('...when anonymous submission is <b>converted</b> to authenticated.'),
        WebformSubmissionInterface::STATE_COMPLETED => $this->t('...when submission is <b>completed</b>.'),
        WebformSubmissionInterface::STATE_UPDATED => $this->t('...when submission is <b>updated</b>.'),
      ],
      '#required' => TRUE,
      '#access' => $results_disabled ? FALSE : TRUE,
      '#default_value' => $results_disabled ? [WebformSubmissionInterface::STATE_COMPLETED] : $this->configuration['states'],
    ];

    // Get options, mail, and text elements as options (text/value).
    $element_options_value = [];

    $elements = $this->webform->getElementsInitializedAndFlattened();
    foreach ($elements as $key => $element) {
      $element_plugin = $this->elementManager->getElementInstance($element);
      if (!$element_plugin->isInput($element) || !isset($element['#type'])) {
        continue;
      }

      $title = (isset($element['#title'])) ? new FormattableMarkup('@title (@key)', ['@title' => $element['#title'], '@key' => $key]) : $key;
      $element_options_value[$key] = $title;
    }

    $newsletter_options = $this->getNewsletterOptions();

    $form['submission_newsletter'] = [
      '#type' => 'details',
      '#title' => $this->t('Newsletter'),
      '#open' => TRUE,
    ];

    $token_options = [];
    $token_options['default'] = $this->t('Default');
    $token_options[(string) $this->t('Elements')] = $element_options_value;

    // Build body select menu.
    $form['submission_newsletter']['token'] = [
      '#type' => 'select',
      '#title' => $this->t('Token'),
      '#options' => $token_options,
      '#required' => TRUE,
      '#default_value' => $this->configuration['token'],
    ];

    $form['submission_newsletter']['newsletters_lst'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Newsletters'),
      '#options' => $newsletter_options,
      '#default_value' => $this->configuration['newsletters_lst'],
    ];

    return $this->setSettingsParentsRecursively($form);
  }

  /**
   * Get newsletter options values.
   *
   * @return array
   *   An associative array containing newsletter options
   */
  public function getNewsletterOptions() {
    $newsletter_options = [];
    $newsletters = simplenews_newsletter_get_visible();

    if (!empty($newsletters)) {
      foreach ($newsletters as $key => $newsletter) {
        $newsletter_options[$key] = $newsletter->name;
      }
    }

    return $newsletter_options;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $state = $webform_submission->getWebform()->getSetting('results_disabled') ? WebformSubmissionInterface::STATE_COMPLETED : $webform_submission->getState();

    if (in_array($state, $this->configuration['states'])) {
      $newsletter_lst = $this->configuration['newsletters_lst'];

      // We take the name's key.
      $token_field_key = $this->configuration['token'];
      $email_value = $webform_submission->getElementData($token_field_key);

      if ($email_value && $newsletter_lst) {
        foreach ($newsletter_lst as $newsletter_id) {
          if ($newsletter_id != '0') {
            // If it's okay we subscribe the mail adress.
            \Drupal::service('simplenews.subscription_manager')->subscribe($email_value, $newsletter_id, NULL, 'website');
          }
        }
      }
    }
  }

}
