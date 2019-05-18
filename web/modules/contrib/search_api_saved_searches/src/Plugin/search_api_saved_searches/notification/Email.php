<?php

namespace Drupal\search_api_saved_searches\Plugin\search_api_saved_searches\notification;

use Drupal\Component\Utility\Html;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\Token;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Query\ResultSetInterface;
use Drupal\search_api_saved_searches\BundleFieldDefinition;
use Drupal\search_api_saved_searches\Entity\SavedSearchAccessControlHandler;
use Drupal\search_api_saved_searches\Notification\NotificationPluginBase;
use Drupal\search_api_saved_searches\SavedSearchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides e-mails as a notification mechanism.
 *
 * @SearchApiSavedSearchesNotification(
 *   id = "email",
 *   label = @Translation("E-mail"),
 *   description = @Translation("Sends new results via e-mail."),
 * )
 */
class Email extends NotificationPluginBase implements PluginFormInterface {

  use PluginFormTrait;

  /**
   * Drupal mail key for the "Activate saved search" mail.
   */
  const MAIL_ACTIVATE = 'activate';

  /**
   * Drupal mail key for the "New results" mail.
   */
  const MAIL_NEW_RESULTS = 'new_results';

  /**
   * The mail service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface|null
   */
  protected $mailService;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|null
   */
  protected $configFactory;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token|null
   */
  protected $tokenService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $plugin */
    $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $plugin->setMailService($container->get('plugin.manager.mail'));
    $plugin->setConfigFactory($container->get('config.factory'));
    $plugin->setTokenService($container->get('token'));

    return $plugin;
  }

  /**
   * Retrieves the mail service.
   *
   * @return \Drupal\Core\Mail\MailManagerInterface
   *   The mail service.
   */
  public function getMailService() {
    return $this->mailService ?: \Drupal::service('plugin.manager.mail');
  }

  /**
   * Sets the mail service.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_service
   *   The new mail service.
   *
   * @return $this
   */
  public function setMailService(MailManagerInterface $mail_service) {
    $this->mailService = $mail_service;
    return $this;
  }

  /**
   * Retrieves the config factory.
   *
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   *   The config factory.
   */
  public function getConfigFactory() {
    return $this->configFactory ?: \Drupal::service('config.factory');
  }

  /**
   * Sets the config factory.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The new config factory.
   *
   * @return $this
   */
  public function setConfigFactory(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
    return $this;
  }

  /**
   * Retrieves the token service.
   *
   * @return \Drupal\Core\Utility\Token
   *   The token service.
   */
  public function getTokenService() {
    return $this->tokenService ?: \Drupal::service('token');
  }

  /**
   * Sets the token service.
   *
   * @param \Drupal\Core\Utility\Token $token_service
   *   The new token service.
   *
   * @return $this
   */
  public function setTokenService(Token $token_service) {
    $this->tokenService = $token_service;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();

    $configuration += [
      'registered_choose_mail' => FALSE,
      'activate' => [
        'send' => TRUE,
        'title' => NULL,
        'body' => NULL,
      ],
      'notification' => [
        'title' => NULL,
        'body' => NULL,
      ],
    ];

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['registered_choose_mail'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Let logged-in users also enter a different mail address'),
      '#default_value' => $this->configuration['registered_choose_mail'],
    ];

    $form['activate'] = [
      '#type' => 'details',
      '#title' => $this->t('Activation mail'),
      '#open' => !$this->configuration['activate']['title'],
    ];
    $form['activate']['send'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use activation mail for anonymous users'),
      '#description' => $this->t("Will require that saved searches created by anonymous users, or by normal users with an e-mail address that isn't their own, are activated by clicking a link in an e-mail."),
      '#default_value' => $this->configuration['activate']['send'],
    ];
    $states = [
      'visible' => [
        ':input[name="activate[send]"]' => [
          'checked' => TRUE,
        ],
      ],
    ];
    $args = ['@site_name' => '[site:name]'];
    $default_title = $this->configuration['activate']['title'] ?: $this->t('Activate your saved search at @site_name', $args);
    $args['@user_name'] = '[user:display-name]';
    $args['@activation_link'] = '[search-api-saved-search:activate-url]';
    $default_body = $this->configuration['activate']['body']
      ?: $this->t("@user_name,

A saved search on @site_name with this e-mail address was created.
To activate this saved search, click the following link:

@activation_link

If you didn't create this saved search, just ignore this mail and the saved search will be deleted.

--  @site_name team", $args);
    $form['activate']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#description' => $this->t("Enter the mail's subject.") . ' ' .
        $this->t('See below for available replacements.'),
      '#default_value' => $default_title,
      '#required' => TRUE,
      '#states' => $states,
    ];
    $form['activate']['body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#description' => $this->t("Enter the mail's body.") . ' ' .
        $this->t('See below for available replacements.'),
      '#default_value' => $default_body,
      '#rows' => 12,
      '#required' => TRUE,
      '#states' => $states,
    ];

    $types = ['site', 'user', 'search-api-saved-search'];
    $available_tokens = $this->getAvailableTokensList($types);
    $form['activate']['available_tokens'] = $available_tokens;

    $form['notification'] = [
      '#type' => 'details',
      '#title' => $this->t('Notification mail'),
      '#open' => !$this->configuration['notification']['title'],
    ];
    $args = ['@site_name' => '[site:name]'];
    $default_title = $this->configuration['notification']['title'] ?: $this->t('New results for your saved search at @site_name', $args);
    $args['@user_name'] = '[user:display-name]';
    $args['@search_label'] = '[search-api-saved-search:label]';
    $args['@results_links'] = '[search-api-saved-search-results:links]';
    $default_body = $this->configuration['notification']['body']
      ?: $this->t('@user_name,

There are new results for your saved search "@search_label":

@results_links

--  @site_name team', $args);
    $form['notification']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#description' => $this->t("Enter the mail's subject.") . ' ' .
        $this->t('See below for available replacements.'),
      '#default_value' => $default_title,
      '#required' => TRUE,
    ];
    $form['notification']['body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#description' => $this->t("Enter the mail's body.") . ' ' .
        $this->t('See below for available replacements.'),
      '#default_value' => $default_body,
      '#rows' => 12,
      '#required' => TRUE,
    ];

    $types[] = 'search-api-saved-search-results';
    $available_tokens = $this->getAvailableTokensList($types);
    $form['notification']['available_tokens'] = $available_tokens;

    return $form;
  }

  /**
   * Provides an overview of available tokens.
   *
   * @param string[] $types
   *   The token types for which to list tokens.
   *
   * @return array
   *   A form/render element for displaying the available tokens for the given
   *   types.
   */
  protected function getAvailableTokensList(array $types) {
    // Code taken from \Drupal\views\Plugin\views\PluginBase::globalTokenForm().
    $token_items = [];
    $infos = $this->getTokenService()->getInfo();
    foreach ($infos['tokens'] as $type => $tokens) {
      if (!in_array($type, $types)) {
        continue;
      }
      $item = [
        '#markup' => Html::escape($type),
        'children' => [],
      ];
      foreach ($tokens as $name => $info) {
        $item['children'][$name] = "[$type:$name] - {$info['name']}: {$info['description']}";
      }

      $token_items[$type] = $item;
    }
    $available_tokens = [
      '#type' => 'details',
      '#title' => $this->t('Available token replacements'),
    ];
    $available_tokens['list'] = [
      '#theme' => 'item_list',
      '#items' => $token_items,
    ];
    return $available_tokens;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDefinitions() {
    $fields['mail'] = BundleFieldDefinition::create('email')
      ->setLabel(t('E-mail'))
      ->setDescription(t('The email address to which notifications should be sent.'))
      ->setDefaultValueCallback(static::class . '::getDefaultMail')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

  /**
   * Returns the default mail address to set for a new saved search.
   *
   * @return array
   *   An array with the default value.
   */
  public static function getDefaultMail() {
    $mail = \Drupal::currentUser()->getEmail();
    return $mail ? [$mail] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultFieldFormDisplay() {
    return [
      'mail' => [
        'type' => 'email_default',
        'weight' => 2,
        'region' => 'content',
        'settings' => [
          'size' => 60,
          'placeholder' => 'user@example.com',
        ],
        'third_party_settings' => [],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
    // Make sure this is really our e-mail field.
    if ($field_definition->getName() !== 'mail') {
      return parent::checkFieldAccess($operation, $field_definition, $account, $items);
    }

    if (!$this->configuration['registered_choose_mail']) {
      $permission = SavedSearchAccessControlHandler::ADMIN_PERMISSION;
      return AccessResult::allowedIf($account->isAnonymous())
        ->addCacheableDependency($account)
        ->orIf(AccessResult::allowedIfHasPermission($account, $permission));
    }

    return parent::checkFieldAccess($operation, $field_definition, $account, $items);
  }

  /**
   * {@inheritdoc}
   */
  public function notify(SavedSearchInterface $search, ResultSetInterface $results) {
    $params = [
      'search' => $search,
      'results' => $results,
      'plugin' => $this,
    ];
    $this->getMailService()
      ->mail('search_api_saved_searches', self::MAIL_NEW_RESULTS, $search->get('mail')->value, $this->getPreferredLangcode($search), $params);
  }

  /**
   * Prepares a message containing new saved search results.
   *
   * @param array|\ArrayAccess $message
   *   An array to be filled in. Elements in this array include:
   *   - id: An ID to identify the mail sent. Look at module source code or
   *     MailManagerInterface->mail() for possible id values.
   *   - to: The address or addresses the message will be sent to. The
   *     formatting of this string must comply with RFC 2822.
   *   - subject: Subject of the email to be sent. This must not contain any
   *     newline characters, or the mail may not be sent properly.
   *     MailManagerInterface->mail() sets this to an empty string when the hook
   *     is invoked.
   *   - body: An array of lines containing the message to be sent. Drupal will
   *     format the correct line endings for you. MailManagerInterface->mail()
   *     sets this to an empty array when the hook is invoked. The array may
   *     contain either strings or objects implementing
   *     \Drupal\Component\Render\MarkupInterface.
   *   - from: The address the message will be marked as being from, which is
   *     set by MailManagerInterface->mail() to either a custom address or the
   *     site-wide default email address when the hook is invoked.
   *   - headers: Associative array containing mail headers, such as From,
   *     Sender, MIME-Version, Content-Type, etc.
   *     MailManagerInterface->mail() pre-fills several headers in this array.
   * @param array|\ArrayAccess $params
   *   An associative array with the following keys:
   *   - search: The saved search entity for which results are being reported.
   *   - results: A Search API result set containing the new results.
   *
   * @see hook_mail()
   * @see search_api_saved_searches_mail()
   */
  public function getNewResultsMail(&$message, $params) {
    /** @var \Drupal\search_api_saved_searches\SavedSearchInterface $search */
    $search = $params['search'];
    $account = $search->getOwner();
    /** @var \Drupal\search_api\Query\ResultSetInterface $results */
    $results = $params['results'];
    $data = [
      'search_api_saved_search' => $search,
      'search_api_results' => $results,
      'user' => $account,
    ];
    $options['langcode'] = $this->getPreferredLangcode($search);
    $options['clear'] = TRUE;

    $subject = $this->configuration['notification']['title'];
    $subject = $this->getTokenService()->replace($subject, $data, $options);
    $body = $this->configuration['notification']['body'];
    $body = $this->getTokenService()->replace($body, $data, $options);

    $message['subject'] = $subject;
    $message['body'][] = $body;
  }

  /**
   * Prepares a message for activating a new saved search.
   *
   * @param array|\ArrayAccess $message
   *   An array to be filled in. Elements in this array include:
   *   - id: An ID to identify the mail sent. Look at module source code or
   *     MailManagerInterface->mail() for possible id values.
   *   - to: The address or addresses the message will be sent to. The
   *     formatting of this string must comply with RFC 2822.
   *   - subject: Subject of the email to be sent. This must not contain any
   *     newline characters, or the mail may not be sent properly.
   *     MailManagerInterface->mail() sets this to an empty string when the hook
   *     is invoked.
   *   - body: An array of lines containing the message to be sent. Drupal will
   *     format the correct line endings for you. MailManagerInterface->mail()
   *     sets this to an empty array when the hook is invoked. The array may
   *     contain either strings or objects implementing
   *     \Drupal\Component\Render\MarkupInterface.
   *   - from: The address the message will be marked as being from, which is
   *     set by MailManagerInterface->mail() to either a custom address or the
   *     site-wide default email address when the hook is invoked.
   *   - headers: Associative array containing mail headers, such as From,
   *     Sender, MIME-Version, Content-Type, etc.
   *     MailManagerInterface->mail() pre-fills several headers in this array.
   * @param array|\ArrayAccess $params
   *   An associative array with the following keys:
   *   - search: The saved search entity which can be activated.
   *
   * @see hook_mail()
   * @see search_api_saved_searches_mail()
   */
  public function getActivationMail(&$message, $params) {
    /** @var \Drupal\search_api_saved_searches\SavedSearchInterface $search */
    $search = $params['search'];
    $account = $search->getOwner();
    $data = [
      'search_api_saved_search' => $search,
      'user' => $account,
    ];
    $options['langcode'] = $this->getPreferredLangcode($search);
    $options['clear'] = TRUE;

    $subject = $this->configuration['activate']['title'];
    $subject = $this->getTokenService()->replace($subject, $data, $options);
    $body = $this->configuration['activate']['body'];
    $body = $this->getTokenService()->replace($body, $data, $options);

    $message['subject'] = $subject;
    $message['body'][] = $body;
  }

  /**
   * Retrieves the preferred langcode to use in mails for the given search.
   *
   * @param \Drupal\search_api_saved_searches\SavedSearchInterface $search
   *   The saved search in question.
   *
   * @return string
   *   The search's owner's preferred langcode, if one could be determined. The
   *   site default language otherwise.
   */
  protected function getPreferredLangcode(SavedSearchInterface $search) {
    $account = $search->getOwner();
    if ($account) {
      return $account->getPreferredLangcode();
    }
    return \Drupal::languageManager()->getDefaultLanguage()->getId();
  }

}
