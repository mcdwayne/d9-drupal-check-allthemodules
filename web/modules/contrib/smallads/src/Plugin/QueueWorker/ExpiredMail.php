<?php

namespace Drupal\smallads\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\smallads\Entity\Smallad;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Logger\LoggerChannel;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Updates a feed's items.
 *
 * @QueueWorker(
 *   id = "smallads_expired_mail",
 *   title = @Translation("Smallad expiry notification"),
 *   cron = {"time" = 60}
 * )
 */
class ExpiredMail extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  protected $logger;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannel $logger_channel) {
    $this->configuration = $configuration;
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;
    $this->logger = $logger_channel;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.channel.smallads')
    );
  }



  /**
   * {@inheritdoc}
   *
   * @todo make a settings form and variables for the text of the notifcation mail.
   */
  public function processItem($ad_id) {
    $ad = Smallad::load($ad_id)->expire();
    $ad->save();
    $owner = $ad->getOwner();

    \Drupal::service('plugin.manager.mail')
      ->mail(
        'smallads',
        'expired',
        $owner->getEmail(),
        $owner->getPreferredLangcode(),
        [
          'smallad' => $ad,
          'user' => $owner
        ]
      );

    $this->logger->info(
      'cron expired ad @id and notified !user (@uid) at @mail',
      [
        '@id' => Link::createFromRoute(
          $ad->label(),
          Url::fromRoute('entity.smallad.canonical', ['smallad' => $ad->id()])
        ),
        '%user' => $ad->getOwner()->getDisplayName(),
        '@uid' => $ad->getOwner()->id(),
        '@mail' => $ad->getowner()->getEmail(),
      ]
    );
  }

}
