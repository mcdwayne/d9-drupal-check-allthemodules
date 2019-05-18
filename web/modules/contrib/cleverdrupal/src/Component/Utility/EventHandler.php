<?php

namespace Drupal\cleverreach\Component\Utility;

use CleverReach\BusinessLogic\Entity\Tag;
use CleverReach\BusinessLogic\Entity\TagCollection;
use CleverReach\BusinessLogic\Sync\FilterSyncTask;
use CleverReach\BusinessLogic\Sync\RecipientSyncTask;
use CleverReach\Infrastructure\Logger\Logger;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\Queue;
use Drupal\cleverreach\Component\BusinessLogic\RecipientService;
use Drupal\cleverreach\Component\Repository\UserRepository;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Session\AccountInterface;

/**
 * Hook and event handler. All entities of interest are observed in this class.
 */
class EventHandler {

  /**
   * Event handler for cleverreach_user_insert event.
   *
   * @see cleverreach.module
   *
   * @param \Drupal\Core\Entity\EntityInterface|\Drupal\user\Entity\User $entity
   *   Event entity.
   */
  public static function userCreated(EntityInterface $entity) {
    if (!self::isEventAllowed()) {
      return;
    }

    Logger::logInfo(
        "User created ({$entity->id()}) event detected.",
        'Integration'
    );

    $hasEmailField = FALSE;
    // Check if created user has an email address.
    if ($fields = $entity->getFields(FALSE)) {
      /** @var \Drupal\Core\Field\FieldItemList $field */
      foreach ($fields as $code => $field) {
        if ($entity->hasField($code) && self::isEmailField($field)) {
          if (count($entity->get($code)->getValue())) {
            $hasEmailField = TRUE;
            break;
          }
        }
      }
    }

    if ($hasEmailField) {
      TaskQueue::enqueue(
        new RecipientSyncTask([$entity->id()], [], FALSE)
      );
    }
  }

  /**
   * Event handler for cleverreach_user_update event.
   *
   * @see cleverreach.module
   *
   * @param \Drupal\Core\Entity\EntityInterface|\Drupal\user\Entity\User $entity
   *   Event entity.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public static function userUpdated(EntityInterface $entity) {
    if (!self::isEventAllowed()) {
      return;
    }

    Logger::logInfo(
        "User updated ({$entity->id()}) event detected.",
        'Integration'
    );

    $changed = [$entity->id()];
    if ($fields = $entity->getFields(FALSE)) {
      /** @var \Drupal\Core\Field\FieldItemList $field */
      foreach ($fields as $code => $field) {
        if (self::isEmailField($field) &&
            self::hasChanges($entity, $code)) {
          $current = array_column(
          $entity->get($code)->getValue(),
            'value'
          );
          $original = array_column(
          $entity->original->get($code)->getValue(),
            'value'
          );

          $changed = array_merge(
            $changed,
            array_diff($original, $current)
          );
        }
      }
    }

    TaskQueue::enqueue(new RecipientSyncTask($changed, [], FALSE));
  }

  /**
   * Event handler for cleverreach_user_cancel event.
   *
   * @see cleverreach.module
   *
   * @param \Drupal\Core\Entity\EntityInterface|\Drupal\user\Entity\User $entity
   *   Event entity.
   */
  public static function userDeleted(EntityInterface $entity) {
    if (!self::isEventAllowed()) {
      return;
    }

    Logger::logInfo(
        "User account cancelled ({$entity->id()}) event detected.",
        'Integration'
    );

    $emails = [];
    if ($fields = $entity->getFields(FALSE)) {
      /** @var \Drupal\Core\Field\FieldItemList $field */
      foreach ($fields as $code => $field) {
        if ($entity->hasField($code) && self::isEmailField($field)) {
          foreach ($entity->get($code)->getValue() as $value) {
            $emails[] = $value['value'];
          }
        }
      }
    }

    if (!empty($emails)) {
      TaskQueue::enqueue(new RecipientSyncTask($emails, [], FALSE));
    }
  }

  /**
   * Event handler for cleverreach_taxonomy_term_insert event.
   *
   * @see cleverreach.module
   *
   * @param \Drupal\Core\Entity\EntityInterface|\Drupal\taxonomy\Entity\Term $entity
   *   Role entity object.
   */
  public static function termCreated(EntityInterface $entity) {
    if (!self::isEventAllowed() ||
        !self::isUserTaxonomyTerm($entity->getVocabularyId())
    ) {
      return;
    }

    Logger::logInfo(
        'New newsletter taxonomy tag create event detected. ' .
        'Tag ID: ' . $entity->id(),
        'Integration'
    );

    TaskQueue::enqueue(new FilterSyncTask());
  }

  /**
   * Event handler for cleverreach_role_update event.
   *
   * @see cleverreach.module
   *
   * @param \Drupal\Core\Entity\EntityInterface|\Drupal\taxonomy\Entity\Term $entity
   *   Term entity object.
   * @param string $label
   *   Term name tag that should be deleted on CleverReach side.
   */
  public static function termUpdate(EntityInterface $entity, $label) {
    if (!self::isEventAllowed() ||
          !self::isUserTaxonomyTerm($entity->getVocabularyId())
      ) {
      return;
    }

    Logger::logInfo(
        'Newsletter taxonomy tag update event detected. ' .
        'Tag ID: ' . $entity->id(),
        'Integration'
    );

    $userRepository = new UserRepository();
    $recipientIds = $userRepository->getAllIds();

    if (!empty($recipientIds)) {
      $tagCollection = new TagCollection(
        [new Tag($label, RecipientService::TAG_TYPE_TAXONOMY)]
      );

      TaskQueue::enqueue(
        new RecipientSyncTask($recipientIds, $tagCollection, FALSE)
      );
    }

    TaskQueue::enqueue(new FilterSyncTask());
  }

  /**
   * Event handler for cleverreach_role_insert event.
   *
   * @see cleverreach.module
   *
   * @param \Drupal\Core\Entity\EntityInterface|\Drupal\user\Entity\Role $entity
   *   Role entity object.
   */
  public static function roleCreated(EntityInterface $entity) {
    if (!self::isEventAllowed()) {
      return;
    }

    Logger::logInfo(
        "New newsletter tag create event detected. Tag ID: {$entity->id()}",
        'Integration'
    );

    TaskQueue::enqueue(new FilterSyncTask());
  }

  /**
   * Event handler for cleverreach_role_update event.
   *
   * @see cleverreach.module
   *
   * @param \Drupal\Core\Entity\EntityInterface|\Drupal\user\Entity\Role $entity
   *   Role entity object.
   * @param string $label
   *   Role name tag that should be deleted on CleverReach side.
   */
  public static function roleUpdate(EntityInterface $entity, $label) {
    if (!self::isEventAllowed()) {
      return;
    }

    Logger::logInfo(
        "Newsletter tag update event detected. Tag ID: {$entity->id()}",
        'Integration'
    );

    if (!in_array(
        $entity->id(),
        [
          AccountInterface::ANONYMOUS_ROLE,
          AccountInterface::AUTHENTICATED_ROLE,
        ],
        TRUE
    )) {
      $userRepository = new UserRepository();
      $recipientIds = $userRepository->getIdsByRoleId($entity->id());

      if (!empty($recipientIds)) {
        $tagCollection = new TagCollection();
        $tagCollection->addTag(
            new Tag($label, RecipientService::TAG_TYPE_ROLE)
        );
        TaskQueue::enqueue(
            new RecipientSyncTask($recipientIds, $tagCollection, FALSE)
        );
      }

      TaskQueue::enqueue(new FilterSyncTask());
    }
  }

  /**
   * Event handler for configuration update event.
   *
   * @see \Drupal\cleverreach\EventSubscriber\ConfigUpdateSubscriber
   *
   * @param string $siteName
   *   Current site name.
   * @param string $originalSiteName
   *   Original site name.
   */
  public static function siteNameUpdate($siteName, $originalSiteName) {
    if (!self::isEventAllowed()) {
      return;
    }

    Logger::logInfo(
        "Site name tag update event detected. Tag ID: {$siteName}",
        'Integration'
    );

    $tagCollection = new TagCollection();
    $userRepository = new UserRepository();

    $tagCollection->addTag(
        new Tag($originalSiteName, RecipientService::TAG_TYPE_SITE)
    );

    TaskQueue::enqueue(
        new RecipientSyncTask(
            $userRepository->getAllIds(),
            $tagCollection,
            FALSE
        )
    );
    TaskQueue::enqueue(new FilterSyncTask());
  }

  /**
   * Checks whether there are changes in field by provided code.
   *
   * @param \Drupal\Core\Entity\EntityInterface|\Drupal\user\Entity\User $entity
   *   Entity that needs to be checked.
   * @param string $code
   *   Field code.
   *
   * @return bool
   *   Returns true if has changes, otherwise false.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  private static function hasChanges(EntityInterface $entity, $code) {
    return $entity->hasField($code) && $entity->original->hasField($code) &&
            $entity->original->get($code)->getValue() !== $entity->get($code)
              ->getValue();
  }

  /**
   * Checks whether field is email or not.
   *
   * @param \Drupal\Core\Field\FieldItemList $field
   *   Field to be checked.
   *
   * @return bool
   *   Return true if field type is email, otherwise false.
   */
  private static function isEmailField(FieldItemList $field) {
    return $field->getFieldDefinition()->getType() === 'email';
  }

  /**
   * Checks if execution of event is allowed. Event execution should be
   * allowed only if initial sync is already done.
   *
   * @return bool
   *   Returns true if allowed, otherwise false.
   */
  private static function isEventAllowed() {
    $queueService = ServiceRegister::getService(Queue::CLASS_NAME);

    if (!$queueService->findLatestByType('InitialSyncTask')) {
      return FALSE;
    }

    TaskQueue::wakeup();

    return TRUE;
  }

  /**
   * Checks if taxonomy term belongs to the user.
   *
   * @param string $vocabulary
   *   Taxonomy term.
   *
   * @return bool
   *   Returns true if term belongs to the user, otherwise false.
   */
  private static function isUserTaxonomyTerm($vocabulary) {
    return in_array(
        $vocabulary,
        cleverreach_get_taxonomy_vocabularies(),
        TRUE
    );
  }

}
