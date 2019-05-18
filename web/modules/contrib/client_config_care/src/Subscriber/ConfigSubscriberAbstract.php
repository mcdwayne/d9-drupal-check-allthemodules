<?php

namespace Drupal\client_config_care\Subscriber;

use Drupal\client_config_care\Deactivator;
use Drupal\client_config_care\Entity\ConfigBlockerEntity;
use Drupal\client_config_care\Validator\ArrayDiffer;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Logger\LoggerChannelInterface;


abstract class ConfigSubscriberAbstract {

  /**
   * @var LoggerChannelInterface
   */
  protected $logger;

  /**
   * @var ArrayDiffer
   */
  protected $arrayDiffer;
  /**
   * @var string
   */
  private $userOperation;

  /**
   * @var EntityTypeManagerInterface
   */
  private $entityTypeManager;
  /**
   * @var Deactivator
   */
  private $deactivator;

  public function __construct(ArrayDiffer $arrayDiffer, LoggerChannelInterface $logger, string $userOperation, EntityTypeManagerInterface $entityTypeManager, Deactivator $deactivator) {
    $this->arrayDiffer = $arrayDiffer;
    $this->logger = $logger;
    $this->userOperation = $userOperation;
    $this->entityTypeManager = $entityTypeManager;
    $this->deactivator = $deactivator;
  }

  public function onConfigHandle(ConfigCrudEvent $event): void {
    try {
      if (!$this->isRelevantConfig($event) || $this->deactivator->isDeactivated()) {
        return;
      }

      $storage = $this->entityTypeManager->getStorage('config_blocker_entity');

      /**
       * @var ConfigBlockerEntity[] $entity
       */
      $entity = $storage->loadByProperties([
        'name' => $event->getConfig()->getName()
      ]);

      if (\is_array($entity)) {
        $entity = reset($entity);
      }

      if (empty($entity)) {
        ConfigBlockerEntity::create([
          'name'           => $event->getConfig()->getName(),
          'user_id'        => \Drupal::currentUser()->id(),
          'langcode'       => LanguageInterface::LANGCODE_DEFAULT,
          'user_operation' => $this->userOperation,
        ])->save();
        $message = 'New config blocker entity: Config named @name has been saved by user <a href="/user/@uid">@username</a>';
        $this->logConfigSave($event, $message);
      }

      if ($entity instanceof ConfigBlockerEntity) {
        $entity->set('user_id', \Drupal::currentUser()->id());
        $entity->set('user_operation', $this->userOperation);
        $entity->setNewRevision(true);
        $entity->save();

        $message = 'Updated config blocker entity: Config named @name has been saved by user <a href="/user/@uid">@username</a>';
        $this->logConfigSave($event, $message);
      }

    } catch (EntityStorageException $exception) {
      $message = 'Could not save ConfigBlockerEntity with name @name for uid @uid';
      $this->logger->alert($message, [
        '@name' => $event->getConfig()->getName(),
        '@uid'  => \Drupal::currentUser()->id(),
      ]);
    }
  }

  protected function isRelevantConfig(ConfigCrudEvent $event): bool {
    if (\Drupal::routeMatch()->getCurrentRouteMatch()->getRouteName() === 'system.modules_list') {
      return FALSE;
    }

    if (!$event->getConfig()->getName()) {
      return FALSE;
    }

    if (!Database::getConnection()->schema()->tableExists('config_blocker_entity')) {
      return FALSE;
    }

    if (!empty($event->getConfig()->getOriginal()) && !$this->arrayDiffer->hasDifference($event->getConfig()->getOriginal(), $event->getConfig()->getRawData())) {
      return FALSE;
    }

    if (PHP_SAPI === 'cli' && getenv('PHPUNIT') !== '1') {
      return FALSE;
    }

    return TRUE;
  }

  protected function logConfigSave(ConfigCrudEvent $event, string $message): void
  {
    $this->logger->notice($message, [
      '@name'     => $event->getConfig()->getName(),
      '@username' => \Drupal::currentUser()->getDisplayName(),
      '@uid'      => \Drupal::currentUser()->getAccount()->id(),
    ]);
  }

}
