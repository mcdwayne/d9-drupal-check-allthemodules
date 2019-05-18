<?php

namespace Drupal\micro_webform\Plugin\WebformHandler;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\micro_site\SiteNegotiatorInterface;
use Drupal\webform\Plugin\WebformHandler\EmailWebformHandler;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Xss;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\Entity\File;
use Drupal\webform\Element\WebformMessage;
use Drupal\webform\Element\WebformSelectOther;
use Drupal\webform\Plugin\WebformElement\WebformManagedFileBase;
use Drupal\webform\Twig\TwigExtension;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\Utility\WebformOptionsHelper;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\Plugin\WebformHandlerMessageInterface;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformThemeManagerInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Emails a webform submission.
 *
 * @WebformHandler(
 *   id = "micro_webform_email",
 *   label = @Translation("Micro Webform Email"),
 *   category = @Translation("Notification"),
 *   description = @Translation("Sends a webform submission via an email according to the micro site context."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class MicroWebformEmailWebformHandler extends EmailWebformHandler {

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
   * A mail manager for sending email.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

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
   * The micro site negotiator.
   *
   * @var \Drupal\micro_site\SiteNegotiatorInterface
   */
  protected $negotiator;

  /**
   * Cache of default configuration values.
   *
   * @var array
   */
  protected $defaultValues;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, WebformSubmissionConditionsValidatorInterface $conditions_validator, AccountInterface $current_user, ModuleHandlerInterface $module_handler, LanguageManagerInterface $language_manager, MailManagerInterface $mail_manager, WebformThemeManagerInterface $theme_manager, WebformTokenManagerInterface $token_manager, WebformElementManagerInterface $element_manager, SiteNegotiatorInterface $site_negotiator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger_factory, $config_factory, $entity_type_manager, $conditions_validator, $current_user, $module_handler, $language_manager, $mail_manager, $theme_manager, $token_manager, $element_manager);
    $this->negotiator = $site_negotiator;
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
      $container->get('plugin.manager.mail'),
      $container->get('webform.theme_manager'),
      $container->get('webform.token_manager'),
      $container->get('plugin.manager.webform.element'),
      $container->get('micro_site.negotiator')
    );
  }

  /**
   * Override the getMessage method.
   *
   * If we are in a micro site context, then we replace the site tokens
   * site:mail, site:name, site:url, etc. with those from the micro site.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The webform submission.
   *
   * @return array
   *   An array containing message parameters.
   */
  public function getMessage(WebformSubmissionInterface $webform_submission) {
    return parent::getMessage($webform_submission);
  }

  /**
   * Get message to, cc, bcc, and from email addresses.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param string $configuration_name
   *   The email configuration name. (i.e. to, cc, bcc, or from)
   * @param string $configuration_value
   *   The email configuration value.
   *
   * @return array
   *   An array of email addresses and/or tokens.
   */
  protected function getMessageEmails(WebformSubmissionInterface $webform_submission, $configuration_name, $configuration_value) {
    return parent::getMessageEmails($webform_submission, $configuration_name, $configuration_value);
  }

}
