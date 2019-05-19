<?php

namespace Drupal\xero_contact_sync\Plugin\AdvancedQueue\JobType;

use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;
use Drupal\advancedqueue\Plugin\AdvancedQueue\JobType\JobTypeBase;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\xero_contact_sync\XeroContactSyncRemoteUserMatcher;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @AdvancedQueueJobType(
 *   id = "xero_contact_sync",
 *   label = @Translation("Xero Contact Sync"),
 *   max_retries = 7,
 *   retry_delay = 86400,
 * )
 */
class XeroContactSyncJobType extends JobTypeBase implements ContainerFactoryPluginInterface {

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A user matcher service.
   *
   * @var \Drupal\xero_contact_sync\XeroContactSyncRemoteUserMatcher
   */
  protected $userMatcher;

  /**
   * {@inheritdoc}
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\xero_contact_sync\XeroContactSyncRemoteUserMatcher $user_matcher
   *   The Xero user matcher service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, EntityTypeManagerInterface $entity_type_manager, XeroContactSyncRemoteUserMatcher $user_matcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger;
    $this->entityTypeManager = $entity_type_manager;
    $this->userMatcher = $user_matcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.channel.xero_contact_sync'),
      $container->get('entity_type.manager'),
      $container->get('xero_contact_sync.remote_user_matcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process(Job $job) {
    $data = $job->getPayload();
    $user_id = $data['user_id'];

    /** @var \Drupal\user\UserInterface $user */
    $user = $this->entityTypeManager->getStorage('user')->load($user_id);
    if ($user !== NULL) {
      if ($user->get('xero_contact_id')->value !== NULL) {
        return JobResult::success('Remote id already matched, nothing to do here.');
      }
      try {
        $result = $this->userMatcher->matchRemoteUser($user);
        if ($result) {
          return JobResult::success((string) new FormattableMarkup('Remote user matched for @user.', [
            '@user' => $user->getDisplayName(),
          ]));
        }
        else {
          return JobResult::failure((string) new FormattableMarkup('Remote user matching failed for @user.', [
            '@user' => $user->getDisplayName(),
          ]));
        }
      }
      catch (\Exception $exc) {
        return JobResult::failure((string) new FormattableMarkup('Remote user matching failed for @user. Exception: @exception.', [
          '@user' => $user->getDisplayName(),
          '@exception' => $exc->getMessage(),
        ]));
      }
    }
    else {
      $message = (string) new FormattableMarkup('There was no user with user id @user_id for creating remotely.', [
        '@user_id' => $user_id,
      ]);
      $this->logger->log(LogLevel::WARNING, $message);
      return JobResult::failure($message);
    }
  }

}
