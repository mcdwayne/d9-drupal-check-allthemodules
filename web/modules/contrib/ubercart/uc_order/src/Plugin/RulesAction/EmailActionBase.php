<?php

namespace Drupal\uc_order\Plugin\RulesAction;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\Token;
use Drupal\rules\Core\RulesActionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for RulesActions that send email.
 */
abstract class EmailActionBase extends RulesActionBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The logger.factory service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * Constructs the EmailActionBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger.factory service.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $current_user, Token $token, LoggerChannelFactoryInterface $logger, MailManagerInterface $mail_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
    $this->token = $token;
    $this->logger = $logger;
    $this->mailManager = $mail_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('token'),
      $container->get('logger.factory'),
      $container->get('plugin.manager.mail')
    );
  }

  /**
   * Options list callback for message formats.
   */
  public function messageFormats() {
    $options = [];
    $formats = filter_formats($this->currentUser);
    foreach ($formats as $format) {
      $options[$format->id()] = $format->label();
    }

    return $options;
  }

  /**
   * Option callback for invoice options.
   */
  public function invoiceOptions() {
    return [
      'print' => $this->t('Show the business header and shipping method.'),
      'admin-mail' => $this->t('Show all of the above plus the help text, email text, and store footer.'),
      'checkout-mail' => $this->t('Show all of the above plus the "thank you" message.'),
    ];
  }

  /**
   * Returns a list of options for a template select box.
   */
  public function templateOptions($custom = FALSE) {
    $list = $this->templateList();
    $templates = array_combine($list, $list);

    if ($custom) {
      $templates[0] = $this->t('Custom template');
    }

    return $templates;
  }

  /**
   * Returns an array of invoice templates found in ubercart/uc_order/templates.
   */
  protected function templateList() {
    $invoke = \Drupal::moduleHandler()->invokeAll('uc_invoice_templates');
    $templates = array_combine($invoke, $invoke);

    // Sort the template names alphabetically.
    sort($templates);

    return ['admin', 'customer'] + $templates;
  }

}
