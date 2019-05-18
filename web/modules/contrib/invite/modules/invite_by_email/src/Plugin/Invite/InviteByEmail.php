<?php

namespace Drupal\invite_by_email\Plugin\Invite;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\invite\InvitePluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for Invite by Email.
 *
 * @Plugin(
 *   id="invite_by_email",
 *   label = @Translation("Invite By Email")
 * )
 */
class InviteByEmail extends PluginBase implements InvitePluginInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Getter for the messenger service.
   *
   * @return \Drupal\Core\Messenger\MessengerInterface
   */
  public function getMessenger() {
    return $this->messenger;
  }

  /**
   * Constructs invite_by_email plugin.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MessengerInterface $messenger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function send($invite) {
    /*
     * @var $token \Drupal\token\Token
     * @var $mail \Drupal\Core\Mail\MailManager
     */
    $bubbleable_metadata = new BubbleableMetadata();
    $token = \Drupal::service('token');
    $mail = \Drupal::service('plugin.manager.mail');
    $mail_key = $invite->get('type')->value;
    // Prepare message.
    $message = $mail->mail('invite_by_email', $mail_key, $invite->get('field_invite_email_address')->value, $invite->activeLangcode, [], $invite->getOwner()
      ->getEmail(), FALSE);
    // If HTML email.
    if (unserialize(\Drupal::config('invite.invite_type.' . $invite->get('type')->value)
      ->get('data'))['html_email']
    ) {
      $message['headers']['Content-Type'] = 'text/html; charset=UTF-8;';
    }
    $message['subject'] = $token->replace($invite->get('field_invite_email_subject')->value, ['invite' => $invite], [], $bubbleable_metadata);
    $body = [
      '#theme' => 'invite_by_email',
      '#body' => $token->replace($invite->get('field_invite_email_body')->value, ['invite' => $invite], [], $bubbleable_metadata),
    ];
    $message['body'] = \Drupal::service('renderer')
      ->render($body)
      ->__toString();
    // Send.
    $system = $mail->getInstance([
      'module' => 'invite_by_email',
      'key' => $mail_key,
    ]);

    $result = $system->mail($message);

    if ($result) {

      $this->getMessenger()->addStatus($this->t('Invitation has been sent.'));

      $mail_user = $message['to'];

      \Drupal::logger('invite')->notice('Invitation has been sent for: @mail_user.', [
        '@mail_user' => $mail_user,
      ]);
    }
    else {

      $this->getMessenger()->addStatus($this->t('Failed to send a message.'), 'error');

      \Drupal::logger('invite')->error('Failed to send a message.');
    }

  }

}

