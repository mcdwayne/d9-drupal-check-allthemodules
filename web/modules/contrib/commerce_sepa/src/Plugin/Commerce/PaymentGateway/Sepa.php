<?php

namespace Drupal\commerce_sepa\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\Manual;
use Drupal\commerce_sepa\SepaInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Locale\CountryManager;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Utility\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the SEPA (Single Euro Payments Area) payment gateway.
 *
 * @todo Add the edit-payment-method form.
 * @see \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsUpdatingStoredPaymentMethodsInterface.
 *
 * @CommercePaymentGateway(
 *   id = "commerce_sepa",
 *   label = "SEPA",
 *   display_label = "Direct debit",
 *   modes = {
 *     "n/a" = @Translation("N/A"),
 *   },
 *   forms = {
 *     "add-payment-method" = "Drupal\commerce_sepa\PluginForm\SepaPaymentMethodAddForm",
 *   },
 *   payment_type = "payment_manual",
 *   payment_method_types = {"bank_account"},
 * )
 */
class Sepa extends Manual implements SepaInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Constructs a new Sepa object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_payment\PaymentTypeManager $payment_type_manager
   *   The payment type manager.
   * @param \Drupal\commerce_payment\PaymentMethodTypeManager $payment_method_type_manager
   *   The payment method type manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   Current route match service.
   * @param \Drupal\Core\Language\LanguageManagerInterface
   *   The language manager.
   * @param \Drupal\Core\Utility\Token
   *   The token service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time, ConfigFactoryInterface $config_factory, MailManagerInterface $mail_manager, AccountProxyInterface $current_user, RouteMatchInterface $route_match, LanguageManagerInterface $language_manager, Token $token) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);

    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
    $this->languageManager = $language_manager;
    $this->mailManager = $mail_manager;
    $this->routeMatch = $route_match;
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_payment_type'),
      $container->get('plugin.manager.commerce_payment_method_type'),
      $container->get('datetime.time'),
      $container->get('config.factory'),
      $container->get('plugin.manager.mail'),
      $container->get('current_user'),
      $container->get('current_route_match'),
      $container->get('language_manager'),
      $container->get('token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'allow_description' => TRUE,
      'valid_countries' => [],
      'notify' => FALSE,
      'notification_from' => NULL,
      'notification_subject' => $this->t('SEPA Direct Debit Mandate from [site:name]'),
      'notification_body' => $this->t("{{ Company Logo }}\n{{ Creditor Name }}\n{{ Creditor Identifier }}\n{{ Creditor Street Name and Number }}\n{{ Creditor Postal Code Creditor City }}\n{{ Creditor Country }}\n\nSEPA Business-to-Business Direct Debit Mandate\nBy signing this mandate form, you authorise (A) {{ NAME OF CREDITOR }} to send\ninstructions to your bank to debit your account and (B) your bank to debit your\naccount in accordance with the instructions from {{ NAME OF CREDITOR }}.\nThis mandate is only intended for business-to-business transactions. You are not\nentitled to a refund from your bank after your account has been debited, but you are\nentitled to request your bank not to debit your account up until the day on which the\npayment is due. Please contact your bank for detailed procedures in such a case.\n\nMandate Reference: [commerce_order:order_id]\n\nType of Payment: □ Recurrent or □ One-off\nDebtor Identification: (To be completed by the Debtor)\nCompany\nName:_____________________________________________________________\nAddress:____________________________________________________________\nPostcode:___________ City:____________________________________\nCountry:_________________________________________________________\nAccount number (IBAN):\n৷_৷_৷_৷_৷_৷_৷_৷_৷_৷_৷_৷_৷_৷_৷_৷_৷_৷_৷_৷_৷_৷_৷_৷_৷_৷_৷_৷_৷\nYour bank BIC: ৷_৷_৷_৷_৷_৷_৷_৷_৷_৷_৷_৷\n\nDate: ____/ ____/ ________ Place: _______________________________\nName:_________________________________________________________\nSignature(s):\n\n\n\n"),
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $site_config = $this->configFactory->get('system.site');
    $form = parent::buildConfigurationForm($form, $form_state);

    $countries = iban_countries();
    $valid_countries = array_filter(
      CountryManager::getStandardList(),
      function ($key) use ($countries) {
        return in_array($key, $countries);
      },
      ARRAY_FILTER_USE_KEY
    );
    $form['valid_countries'] = [
      '#type' => 'select',
      '#title' => $this->t('Valid countries'),
      '#description' => $this->t('The customer bank account number will be validated against the rules of the country selected.'),
      '#options' => $valid_countries,
      '#default_value' => $this->configuration['valid_countries'],
      '#size' => 8,
      '#required' => FALSE,
      '#multiple' => TRUE,
    ];
    $form['instructions']['#weight'] = 100;

    $form['notify'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send SEPA Direct Debit Mandate'),
      '#default_value' => $this->configuration['notify'],
    ];
    $form['notify_container'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('SEPA Direct Debit Mandate'),
      '#description' => $this->t('This notification will be sent immediately after the customer adds a new bank account, either through checkout or from their available payment methods in their user profile.'),
      '#states' => [
        'visible' => [
          ':input[name="configuration[commerce_sepa][notify]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['notify_container']['notification_from'] = [
      '#type' => 'email',
      '#title' => $this->t('SEPA Direct Debit Mandate notification email address'),
      '#parents' => array_merge($form['#parents'], ['notification_from']),
      '#default_value' => $this->configuration['notification_from'],
      '#description' => $this->t("The email address to be used as the 'from' address for all SEPA Direct Debit Mandate notifications. Leave empty to use the default system email address <em>(%site-email).</em>", ['%site-email' => $site_config->get('mail')]),
      '#maxlength' => 180,
    ];
    $form['notify_container']['notification_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#parents' => array_merge($form['#parents'], ['notification_subject']),
      '#default_value' => $this->configuration['notification_subject'],
      '#maxlength' => 180,
      '#states' => [
        'required' => [
          ':input[name="configuration[commerce_sepa][notify]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['notify_container']['notification_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#parents' => array_merge($form['#parents'], ['notification_body']),
      '#default_value' => $this->configuration['notification_body'],
      '#rows' => 16,
      '#states' => [
        'required' => [
          ':input[name="configuration[commerce_sepa][notify]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['notify_container']['token_tree'] = [
      '#theme' => 'token_tree_link',
      '#text' => t('Browse available tokens.'),
      '#token_types' => ['commerce_order', 'commerce_payment_method', 'profile'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['valid_countries'] = $values['valid_countries'];
      $this->configuration['notify'] = $values['notify'];
      $this->configuration['notification_from'] = $values['notification_from'];
      $this->configuration['notification_subject'] = $values['notification_subject'];
      $this->configuration['notification_body'] = $values['notification_body'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createPaymentMethod(PaymentMethodInterface $payment_method, array $payment_details) {
    // Convert IBAN to human format.
    $payment_method->iban = iban_to_human_format($payment_details['iban']);
    $payment_method->save();

    // Exit if notifications are disabled.
    if (empty($this->configuration['notify'])) {
      return;
    }

    // For token replacement.
    $data = ['commerce_payment_method' => $payment_method];

    if ($this->currentUser->isAnonymous()) {
      /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
      $order = $this->routeMatch->getParameter('commerce_order');
      $data['commerce_order'] = $order;

      $to = $order->getEmail();
    }
    else {
      $to = $payment_method->getOwner()->getEmail();
    }

    $current_langcode = $this->languageManager->getCurrentLanguage()->getId();

    if ($billing_profile = $payment_method->getBillingProfile()) {
      $data['profile'] = $billing_profile;
    }
    $params['subject'] = $this->token->replace($this->configuration['notification_subject'], $data);
    $params['body'] = $this->token->replace($this->configuration['notification_body'], $data);

    $site_config = $this->configFactory->get('system.site');
    $from = empty($this->configuration['notification_from']) ? $site_config->get('mail') : $this->configuration['notification_from'];

    // Send notification message.
    $this->mailManager->mail('commerce_sepa', 'sepa_notification', $to, $current_langcode, $params, $from);
  }

  /**
   * {@inheritdoc}
   */
  public function deletePaymentMethod(PaymentMethodInterface $payment_method) {
    $payment_method->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function updatePaymentMethod(PaymentMethodInterface $payment_method) {
    // @todo Send email notification if the account number change.
    $payment_method->save();
  }

}
