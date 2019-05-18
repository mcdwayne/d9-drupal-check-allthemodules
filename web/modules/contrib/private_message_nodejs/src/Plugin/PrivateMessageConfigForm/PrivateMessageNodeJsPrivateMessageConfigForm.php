<?php

namespace Drupal\private_message_nodejs\Plugin\PrivateMessageConfigForm;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Utility\Token;
use Drupal\private_message\Plugin\PrivateMessageConfigForm\PrivateMessageConfigFormBase;
use Drupal\private_message\Plugin\PrivateMessageConfigForm\PrivateMessageConfigFormPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds Private Message Nodejs settings to the Private Message settings page.
 *
 * @PrivateMessageConfigForm(
 *   id = "private_message_node_js_settings",
 *   name = @Translation("Private Message Nodejs settings"),
 * )
 */
class PrivateMessageNodeJsPrivateMessageConfigForm extends PrivateMessageConfigFormBase implements PrivateMessageConfigFormPluginInterface {

  /**
   * The file handler service.
   *
   * @var \Drupal\file\FileStorageInterface
   */
  protected $fileHandler;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Constructs a PrivateMessageForm object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definitions.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeHandler
   *   The entity type handler service.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system service.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $configFactory,
    EntityTypeManagerInterface $entityTypeHandler,
    FileSystemInterface $fileSystem,
    Token $token
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $configFactory);

    $this->fileSystem = $fileSystem;
    $this->fileHandler = $entityTypeHandler->getStorage('file');
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
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('file_system'),
      $container->get('token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(FormStateInterface $formState) {

    $config = $this->configFactory->get('private_message_nodejs.settings');

    $form['enable_debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debug mode'),
      '#default_value' => $config->get('enable_debug'),
      '#description' => $this->t('Debug mode will write PHP debug info to the Drupal log, and JavaScript debug info to the console. Debugging adds overhead to the system, so it should be disabled by default. Only enable it if you are trying to debug an issue.'),
    ];

    $form['nodejs_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Nodejs url'),
      '#description' => $this->t('The URL at which the Nodejs server can be accessed'),
      '#default_value' => $config->get('nodejs_url'),
    ];

    $nodejs_secret = $config->get('nodejs_secret');
    if (!strlen($nodejs_secret)) {
      $nodejs_secret = bin2hex(random_bytes(32));
      $settings = $this->configFactory->getEditable('private_message_nodejs.settings');
      $settings->set('nodejs_secret', $nodejs_secret)->save();
    }

    $form['nodejs_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nodejs secret'),
      '#default_value' => $nodejs_secret,
      '#required' => TRUE,
      '#description' => $this->t('An arbitrary secret used to identify yourself to the Nodejs server for security purposes. Copy the value here to the relevant field in the Node.js configuration file.'),
    ];

    $form['browser_push_notifications'] = [
      '#type' => 'details',
      '#title' => $this->t('Browser Push Notifications'),
      '#open' => TRUE,
    ];

    $form['browser_push_notifications']['browser_notification_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable browser push notifications'),
      '#default_value' => $config->get('browser_notification_enable'),
    ];

    $form['browser_push_notifications']['browser_notification_settings_wrapper'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="private_message_node_js_settings[browser_push_notifications][browser_notification_enable]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['browser_push_notifications']['browser_notification_settings_wrapper']['header'] = [
      '#prefix' => '<p><i>',
      '#suffix' => '</i></p>',
      '#markup' => $this->t('Enter the notification details below. You can use tokens in these fields. Note that some browsers require HTTPS (SSL) and will not work on HTTP.'),
    ];

    $form['browser_push_notifications']['browser_notification_settings_wrapper']['browser_notification_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Notification title'),
      '#default_value' => $config->get('browser_notification_title'),
      '#description' => $this->t('Note that each browser has a different maximum length. Titles over 30 characters in length will be trimmed in some browers.'),
    ];

    $form['browser_push_notifications']['browser_notification_settings_wrapper']['browser_notification_body'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Notification body'),
      '#default_value' => $config->get('browser_notification_body'),
      '#description' => $this->t('Note that each browser has a different maximum length. Bodies over 30 characters in length will be trimmed in some browers.'),
    ];

    $form['browser_push_notifications']['browser_notification_settings_wrapper']['browser_notification_link'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Notification link'),
      '#default_value' => $config->get('browser_notification_link'),
      '#description' => $this->t('If this field is filled out, users will be redirected to the link provided when clicking on the notification. Leave blank to disable.'),
    ];

    $form['browser_push_notifications']['browser_notification_settings_wrapper']['browser_notification_icon'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Icon path'),
      '#default_value' => $config->get('browser_notification_icon'),
      '#description' => $this->t('The file path or uri to the icon to be shown in the notification. This field allows for the use of private message, private message thread, and global tokens. You can use system paths as well as public, private and vendor URIs.'),
    ];

    $form['browser_push_notifications']['browser_notification_settings_wrapper']['browser_notification_icon_upload'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Icon upload'),
      '#description' => $this->t("If you don't have direct file access to the server, use this field to upload your icon."),
      '#upload_validators' => [
        'file_validate_is_image' => [],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $formState) {
    parent::validateForm($form, $formState);

    // If the user provided a path for the icond, make sure a file exists at
    // that path.
    if ($icon_path = $formState->getValue([
      'private_message_node_js_settings',
      'browser_push_notifications',
      'browser_notification_settings_wrapper',
      'browser_notification_icon',
    ])) {
      $path = $this->validatePath($icon_path);
      if (!$path) {
        $formState->setError($form['private_message_node_js_settings']['browser_push_notifications']['browser_notification_settings_wrapper']['browser_notification_icon'], $this->t('The browser notification icon path is invalid.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array $values) {

    $settings = $this->configFactory->getEditable('private_message_nodejs.settings');
    $settings->set('enable_debug', $values['enable_debug'])
      ->set('nodejs_url', $values['nodejs_url'])
      ->set('nodejs_secret', $values['nodejs_secret'])
      ->set('browser_notification_enable', $values['browser_push_notifications']['browser_notification_enable'])
      ->set('browser_notification_title', $values['browser_push_notifications']['browser_notification_settings_wrapper']['browser_notification_title'])
      ->set('browser_notification_body', $values['browser_push_notifications']['browser_notification_settings_wrapper']['browser_notification_body'])
      ->set('browser_notification_link', $values['browser_push_notifications']['browser_notification_settings_wrapper']['browser_notification_link'])
      ->set('browser_notification_icon', $values['browser_push_notifications']['browser_notification_settings_wrapper']['browser_notification_icon']);

    // If the user uploaded a icon, save it to a permanent location and set it
    // as the icon path.
    $fid = isset($values['browser_push_notifications']['browser_notification_settings_wrapper']['browser_notification_icon_upload'][0]) ? $values['browser_push_notifications']['browser_notification_settings_wrapper']['browser_notification_icon_upload'][0] : FALSE;
    if ($fid) {
      $file = $this->fileHandler->load($fid);
      $filename = file_unmanaged_copy($file->getFileUri());
      $settings->set('browser_notification_icon', $filename);
    }

    $settings->save();
  }

  /**
   * Validate that the given path exists.
   *
   * Attempts to validate normal system paths, paths relative to the public
   * files directory, or stream wrapper URIs. If the given path is any of the
   * above, returns a valid path or URI that the system can display.
   *
   * @param string $path
   *   A path relative to the Drupal root or to the public files directory, or
   *   a stream wrapper URI.
   *
   * @return mixed
   *   A valid path that can be displayed through the theme system, or FALSE if
   *   the path could not be validated.
   */
  protected function validatePath($path) {
    $path = $this->token->replace($path);
    // Absolute local file paths are invalid.
    if ($this->fileSystem->realpath($path) == $path) {
      return FALSE;
    }
    // A path relative to the Drupal root or a fully qualified URI is valid.
    if (is_file($path)) {
      return $path;
    }
    // Prepend 'public://' for relative file paths within public filesystem.
    if (file_uri_scheme($path) === FALSE) {
      $path = 'public://' . $path;
    }
    if (is_file($path)) {
      return $path;
    }

    return FALSE;
  }

}
