<?php

namespace Drupal\personal_digest\Plugin\QueueWorker;

use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\user\Entity\User;
use Drupal\user\UserDataInterface;
use Drupal\Component\Datetime\TimeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Updates a feed's items.
 *
 * @QueueWorker(
 *   id = "personal_digest_mail",
 *   title = @Translation("Generate personalised digest mail"),
 *   cron = {"time" = 60}
 * )
 */
class DigestMail extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The Mail Manager service.
   *
   * @var MailManagerInterface
   */
  protected $mailManager;

  /**
   * The user data service.
   *
   * @var UserDataInterface
   */
  protected $userDataStore;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param UserDataInterface $user_data
   *   UserData service.
   * @param MailManagerInterface $mail_manager
   *   Mail manager service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, UserDataInterface $user_data, MailManagerInterface $mail_manager, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->userDataStore = $user_data;
    $this->mailManager = $mail_manager;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('user.data'),
      $container->get('plugin.manager.mail'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $recipient = User::load($data);
    $now = $this->time->getRequestTime();
    $settings = $this->userDataStore->get('personal_digest', $data, 'digest');
    if ($settings['weeks_interval'] != -1) {
      $since = strtotime('-' . $settings['weeks_interval'] . ' weeks', $now);
    } else {
      // If the user wants to digests in dialy basis, we will sent the last 24
      // hours of data.
      $since = strtotime('-24 hours', $now); // One day of data.
    }

    $this->mailManager
      ->mail(
        'personal_digest',
        'digest',
        $recipient->getEmail(),
        $recipient->getPreferredLangcode(),
        [
          'user' => $recipient,
          'since' => $since,
          'settings' => $settings
        ]
      );
    // The mail is sent, so save the last sent time.
    $settings['last'] = $now;
    $this->userDataStore->set('personal_digest', $data, 'digest', $settings);
  }

}