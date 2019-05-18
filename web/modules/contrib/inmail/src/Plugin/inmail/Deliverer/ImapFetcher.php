<?php

namespace Drupal\inmail\Plugin\inmail\Deliverer;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Fetches messages over IMAP.
 *
 * @ingroup deliverer
 *
 * @Deliverer(
 *   id = "imap",
 *   label = @Translation("IMAP / POP3")
 * )
 */
class ImapFetcher extends FetcherBase implements ContainerFactoryPluginInterface {

  /**
   * MimeMessage id used for marking and deletion of messages.
   */
  protected $message_id;

  /**
   * Injected Inmail logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $loggerChannel;

  /**
   * Injected site state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelInterface $logger_channel, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->loggerChannel = $logger_channel;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('inmail'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function fetchUnprocessedMessages() {
    return $this->doImap(function($imap_stream) {
      // @todo: After release of 8.3, https://www.drupal.org/node/2819597.
      // Capture current timestamp, not the request starting time.
      $time = time();
      // Find IDs of unread messages.
      // @todo Introduce options for message selection, https://www.drupal.org/node/2405767
      $unread_ids = $this->doImapSearch($imap_stream, 'UNSEEN');

      // Save number of unread messages.
      $this->setUnprocessedCount(count($unread_ids));
      $this->setLastCheckedTime($time);

      $batch_ids = array_splice($unread_ids, 0, \Drupal::config('inmail.settings')->get('batch_size'));

      // Get the header + body of each message.
      $raws = array();
      foreach ($batch_ids as $unread_id) {
        $raws[$unread_id] = imap_fetchheader($imap_stream, $unread_id) . imap_body($imap_stream, $unread_id);
      }

      return $raws;
    }) ?: array();
  }

  /**
   * Deletes the message.
   *
   * @param mixed $key
   *   Key of the message.
   */
  public function deleteMessage($key) {
    $this->message_id = $key;
    // Delete fetched messages if it is specified in configuration, and
    // after successful processing.
    if ($this->configuration['delete_processed']) {
      $this->doImap(function($imap_stream) {
        // Key of the message is ID for deletion process.
        // Mark the messages for deletion.
        imap_delete($imap_stream, $this->message_id);
        // Delete all messages marked for deletion.
        imap_expunge($imap_stream);
        $unread_ids = $this->doImapSearch($imap_stream, 'UNSEEN');
        $read_ids = $this->doImapSearch($imap_stream, 'SEEN');
        $this->setTotalCount(count($unread_ids) + count($read_ids));
      });
    }
  }

  /**
   * {@inheritdoc}
   */
  public function success($key) {
    parent::success($key);

    $this->deleteMessage($key);
  }

  /**
   * {@inheritdoc}
   */
  public function update() {
    $this->doImap(function($imap_stream) {
      $unread_ids = $this->doImapSearch($imap_stream, 'UNSEEN');
      $this->setUnprocessedCount(count($unread_ids));
      $read_ids = $this->doImapSearch($imap_stream, 'SEEN');
      $this->setTotalCount(count($unread_ids) + count($read_ids));
      $this->setLastCheckedTime(REQUEST_TIME);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function getQuota() {
    return $this->doImap(function($imap_stream) {
      $quota = imap_get_quotaroot($imap_stream, 'INBOX');
      if (!empty($quota) && is_array($quota)) {
        return $quota;
      }
      return NULL;
    });
  }

  /**
   * Connect to IMAP server and perform arbitrary operations.
   *
   * If connection fails, an exception is thrown and the callback is never
   * invoked.
   *
   * @param callable $callback
   *   A callable that takes an IMAP stream as argument.
   *
   * @return mixed
   *   The return value of the callback.
   *
   * @throws \Exception
   *   If connection fails.
   */
  protected function doImap(callable $callback) {
    // Connect to IMAP with details from configuration.
    $mailbox = '{' . $this->configuration['host'] . ':' . $this->getPort() . $this->getFlags() . '}';

    $imap_res = @imap_open(
      $mailbox,
      $this->configuration['username'],
      $this->configuration['password']
    );
    $errors = imap_errors();
    if (empty($imap_res)) {
      // @todo Return noisily if misconfigured or imap missing. Possibly stop retrying, https://www.drupal.org/node/2405757
      $this->loggerChannel->error('Deliverer connection failed: @error', ['@error' => implode("\n", $errors)]);
      return NULL;
    }

    // Call callback.
    $return = $callback($imap_res);

    // Close connection.
    imap_close($imap_res);
    return $return;
  }

  /**
   * Performs an IMAP Search with empty handling.
   *
   * @param $imap_res
   *   IMAP connection.
   * @param string $type
   *   Type of message, e.g. 'UNSEEN' or 'SEEN'.
   *
   * @return array
   *   The found ids of messages.
   */
  public function doImapSearch($imap_res, $type) {
    $result = imap_search($imap_res, $type);
    return $result ?: [];
  }

  /**
   * Returns the port number depending on protocol.
   *
   * @return integer
   *   Port number.
   */
  protected function getPort() {
    $port = $this->configuration['imap_port'];
    if ($this->configuration['protocol'] === 'pop3') {
      $port = $this->configuration['pop3_port'];
    }

    return $port;
  }

  /**
   * Returns the flags for mailbox.
   *
   * @return string
   *   Flags for mailbox.
   */
  protected function getFlags() {
    $flags = $this->configuration['ssl'] ? '/ssl' : '';

    if ($this->configuration['protocol'] === 'pop3') {
      $flags.= '/pop3';
    }

    if ($this->configuration['novalidate_ssl']) {
      $flags .= '/novalidate-cert';
    }

    return $flags;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'host' => '',
      // Standard non-SSL IMAP port as defined by RFC 3501.
      'imap_port' => 143,
      'pop3_port' => 110,
      'ssl' => FALSE,
      'novalidate_ssl' => FALSE,
      'protocol' => 'imap',
      'username' => '',
      'password' => '',
      'delete_processed' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['account'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Account'),
    );

    $form['account']['info'] = array(
      '#type' => 'item',
      '#markup' => $this->t('Please refer to your email provider for the appropriate values for these fields.'),
    );

    $form['account']['delete_processed'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Delete fetched'),
      '#default_value' => $this->configuration['delete_processed'],
      '#description' => $this->t('Makes Expunge of messages after fetching and successful processing.'),
    );

    $form['account']['protocol'] = [
      '#type' => 'select',
      '#title' => $this->t('Protocol'),
      '#options' => [
        'imap' => $this->t('IMAP'),
        'pop3' => $this->t('POP3'),
      ],
      '#default_value' => $this->configuration['protocol'],
    ];

    $form['account']['host'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Host'),
      '#default_value' => $this->configuration['host'],
    );

    $form['account']['imap_port'] = [
      '#type' => 'number',
      '#title' => $this->t('Port'),
      '#default_value' => $this->configuration['imap_port'],
      '#description' => t('The standard port number for IMAP is 143 (SSL:993)'),
      '#states' => [
        'visible' => [
          ':input[name = "protocol"]' => ['value' => 'imap']
        ]
      ],
    ];

    $form['account']['pop3_port'] = [
      '#type' => 'number',
      '#title' => $this->t('Port'),
      '#default_value' => $this->configuration['pop3_port'],
      '#description' => t('The standard port number for POP3 is 110 (SSL: 995).'),
      '#states' => [
        'visible' => [
          ':input[name = "protocol"]' => ['value' => 'pop3']
        ]
      ],
    ];

    $form['account']['ssl'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Use SSL'),
      '#default_value' => $this->configuration['ssl'],
    );

    $form['account']['novalidate_ssl'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do not validate certificates from TLS/SSL server.'),
      '#default_value' => $this->getConfiguration()['novalidate_ssl'],
      '#states' => [
        'visible' => [
          ':input[name = "ssl"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['account']['username'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#default_value' => $this->configuration['username'],
    );

    // Password field cannot have #default_value. To avoid forcing user to
    // re-enter password with each save, password updating is conditional on
    // this checkbox.
    $form['account']['password_update'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Update password'),
    );

    $form['account']['password'] = array(
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#states' => array(
        'visible' => array(
          ':input[name=password_update]' => array('checked' => TRUE),
        ),
      ),
    );

    // Always show password field if configuration is new.
    if ($form_state->getFormObject()->getEntity()->isNew()) {
      $form['account']['password_update']['#access'] = FALSE;
      $form['account']['password']['#states']['visible'] = array();
    }

    // Add a "Test connection" button.
    $form['account'] += parent::addTestConnectionButton();

    return $form;
  }

  /**
   * Updates the fetcher configuration.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function updateConfiguration(FormStateInterface $form_state) {
    $configuration = [
      'host' => $form_state->getValue('host'),
      'imap_port' => $form_state->getValue('imap_port'),
      'pop3_port' => $form_state->getValue('pop3_port'),
      'ssl' => $form_state->getValue('ssl'),
      'novalidate_ssl' =>
        $form_state->getValue('ssl') ? $form_state->getValue('novalidate_ssl') : FALSE,
      'protocol' => $form_state->getValue('protocol'),
      'username' => $form_state->getValue('username'),
      'delete_processed' => $form_state->getValue('delete_processed'),
    ] + $this->getConfiguration();

    // Only update password if "Update password" is checked.
    if ($form_state->getValue('password_update')) {
      $configuration['password'] = $form_state->getValue('password');
    }

    $this->setConfiguration($configuration);
  }

  /**
   * Checks the account credentials.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return bool
   *   TRUE if credentials are valid. Otherwise, FALSE.
   */
  protected function hasValidCredentials(FormStateInterface $form_state) {
    $this->updateConfiguration($form_state);
    try {
      $hasValidCredentials = $this->doImap(function ($imap_stream) {
        // At this point IMAP connection is open and credentials are valid.
        return TRUE;
      });
    }
    catch (\Exception $e) {
      $hasValidCredentials = FALSE;
    }

    return (bool) $hasValidCredentials;
  }

  /**
   * Handles submit call of "Test connection" button.
   */
  public function submitTestConnection(array $form, FormStateInterface $form_state) {
    if ($this->hasValidCredentials($form_state)) {
      drupal_set_message(t('Valid credentials!'));
    }
    else {
      drupal_set_message(t('Invalid credentials!'), 'error');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->updateConfiguration($form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function isAvailable() {
    // Checks if the imap extension is enabled.
    return function_exists('imap_open');
  }

  /**
   * {@inheritdoc}
   */
  public static function checkPluginRequirements() {
    $requirements = [
      'title' => t('PHP IMAP'),
      'severity' => REQUIREMENT_OK,
    ];
    if(!function_exists('imap_open')) {
      $requirements['severity'] = REQUIREMENT_ERROR;
      $requirements['description'] = t('The <a href=":imap">PHP IMAP</a> extension is missing, it must be enabled.', [':imap' => 'http://www.php.net/imap']);
    }
    else {
      $requirements['description'] = t('The <a href=":imap">PHP IMAP</a> extension is installed.', [':imap' => 'http://www.php.net/imap']);
    }
    return $requirements;
  }

  /**
   * {@inheritdoc}
   */
  public function getHost() {
    return $this->configuration['host'];
  }

}
