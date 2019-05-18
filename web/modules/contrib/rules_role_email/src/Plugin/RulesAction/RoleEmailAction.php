<?php

namespace Drupal\rules_role_email\Plugin\RulesAction;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\NodeInterface;
use Drupal\rules\Core\RulesActionBase;
use Drupal\user\UserStorageInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Utility\Token;

/**
 * Provides a 'RoleEmailAction' action.
 *
 * @RulesAction(
 *  id = "role_email_action",
 *  label = @Translation("Send an email to all users of a role"),
 *  category = @Translation("User"),
 *  context = {
 *    "roles" = @ContextDefinition("entity:user_role",
 *      label = @Translation("Roles"),
 *      multiple = TRUE
 *    ),
 *    "subject" = @ContextDefinition("string",
 *      label = @Translation("Subject"),
 *      description = @Translation("The email's subject."),
 *    ),
 *   "message" = @ContextDefinition("string",
 *     label = @Translation("Message"),
 *     description = @Translation("The email's message body."),
 *   ),
 *   "node" = @ContextDefinition("entity:node",
 *     label = @Translation("Node"),
 *     description = @Translation("Optionally enter in 'node' as a typed data parameter to use tokens."),
 *     default_value = "node",
 *     required = false
 *   )
 *  }
 * )
 */
class RoleEmailAction extends RulesActionBase implements ContainerFactoryPluginInterface {

  /**
   * Mail manager object so we can send emails.
   *
   * @var \Drupal\Core\Mail\MailManager
   */
  protected $mailManager;

  /**
   * User storage handler.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Constructs an EntityCreate object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager service.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage handler.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MailManagerInterface $mail_manager, UserStorageInterface $user_storage, Token $token) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->mailManager = $mail_manager;
    $this->userStorage = $user_storage;
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
      $container->get('plugin.manager.mail'),
      $container->get('entity.manager')->getStorage('user'),
      $container->get('token')
    );
  }

  /**
   * Send email to users of specified roles.
   *
   * @param array $roles
   *   Array Roles.
   * @param string $subject
   *   String Subject.
   * @param string $message
   *   String message.
   * @param \Drupal\node\NodeInterface|string $node
   *   Node {@inheritdoc}.
   */
  protected function doExecute(array $roles, $subject, $message, $node = NULL) {
    $users = $this->retrieveUsersOfRoles($roles);

    // Enable token support if the user has provided a node context.
    if (isset($node) && $node instanceof NodeInterface) {
      $message = $this->token->replace($message, ['node' => $node]);
    }

    // Send out each email individually because certain users may have
    // different preferred languages such as French or Spanish.
    foreach ($users as $user) {
      if ($user instanceof User) {
        $langcode = $user->getPreferredLangcode();
        $params = [
          'subject' => $this->t('@subject', ['@subject' => $subject]),
          'message' => $message,
        ];
        // Set a unique key for this mail.
        $key = 'rules_action_mail_' . $this->getPluginId();

        $mail = $user->get('mail')->getValue();
        $to = $mail[0]['value'];
        $this->mailManager->mail('rules', $key, $to, $langcode, $params, TRUE);
      }
    }
  }

  /**
   * Returns an array of user objects based on the specified roles.
   *
   * Users that are blocked will NOT receive any email notifications.
   *
   * @param array $roles
   *   Array Roles.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of user entity objects indexed by their IDs.
   */
  protected function retrieveUsersOfRoles(array $roles) {
    $uids = $this->userStorage->getQuery()
      ->condition('roles', $roles, 'IN')
      ->condition('status', 1)
      ->execute();

    return $this->userStorage->loadMultiple($uids);
  }

}
