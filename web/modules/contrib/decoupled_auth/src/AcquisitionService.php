<?php

namespace Drupal\decoupled_auth;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides an acquisition service for finding and acquiring users.
 */
class AcquisitionService implements AcquisitionServiceInterface {

  /**
   * The context for the acquisition process.
   *
   * Additional behaviors are added in AcquisitionService::__construct() from
   * current site configuration.
   *
   * @var array
   */
  protected $context = [
    'name' => NULL,
    'conjunction' => 'AND',
    'behavior' => self::BEHAVIOR_CREATE | self::BEHAVIOR_PREFER_COUPLED,
  ];

  /**
   * The failure code, if any.
   *
   * This is set as part of AcquisitionService::acquire(). One of the
   * \Drupal\decoupled_auth\AcquisitionServiceInterface::FAIL_* constants, or
   * NULL if there is no failure code.
   *
   * @var int|null
   */
  protected $failCode;

  /**
   * The user storage class.
   *
   * @var \Drupal\decoupled_auth\DecoupledAuthUserStorageSchema
   */
  protected $userStorage;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The decoupled auth settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $settings;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher) {
    $this->userStorage = $entity_type_manager->getStorage('user');
    $this->eventDispatcher = $event_dispatcher;
    $this->settings = $config_factory->get('decoupled_auth.settings');

    if ($this->settings->get('acquisitions.behavior_first')) {
      $this->context['behavior'] = $this->context['behavior'] | self::BEHAVIOR_FIRST;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function acquire(array $values, array $context = [], &$method = NULL) {
    // Ensure method and fail code are NULL before we start.
    $method = NULL;
    $this->failCode = NULL;

    // Merge in our default contexts.
    $this->context = $context + $this->context;

    // Allow modules to make adjustments to our acquisition attempt.
    $this->eventDispatcher->dispatch(AcquisitionEvent::PRE, new AcquisitionEvent($values, $this->context));

    // Look for a match.
    if (!empty($values)) {
      $user = $this->findMatch($values, $this->context);
    }
    // Otherwise record a failure.
    else {
      $this->failCode = self::FAIL_NO_VALUES;
      $user = NULL;
    }

    // If there's no match and we are preferring coupled users, run again
    // without that behavior.
    if (!$user && $this->context['behavior'] & self::BEHAVIOR_PREFER_COUPLED) {
      // Build a new context so we can remove the prefer coupled behavior. We
      // don't exclude coupled from the query as it may have failed due to
      // multiple matches.
      $new_context = $this->context;
      $new_context['behavior'] -= self::BEHAVIOR_PREFER_COUPLED;

      // Re-run the find match with our new context.
      $user = $this->findMatch($values, $new_context);
    }

    // If we have a match, we are acquiring.
    if ($user) {
      $method = 'acquire';
    }
    // Otherwise see if we should create.
    elseif ($this->context['behavior'] & self::BEHAVIOR_CREATE) {
      $method = 'create';
      $user = $this->userStorage->create();
    }

    // Allow modules to respond to our acquisition attempt.
    $this->eventDispatcher->dispatch(AcquisitionEvent::POST, new AcquisitionEvent($values, $this->context, $user));

    return $user;
  }

  /**
   * Find a match for the given parameters.
   *
   * @param array $values
   *   An array of party fields to match on. Keys are the field and values are
   *   the expected values.
   * @param array $context
   *   The context we are using to find a match.
   *
   * @return \Drupal\decoupled_auth\DecoupledAuthUserInterface|null
   *   Return the matched user or NULL if no valid match could be found.
   */
  protected function findMatch(array $values, array &$context) {
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $this->userStorage->getQuery($this->context['conjunction'])
      ->addTag('decoupled_auth_acquisition')
      ->addMetaData('values', $values)
      ->addMetaData('context', $this->context);
    if ($context['name']) {
      $query->addTag('decoupled_auth_acquisition__' . $context['name']);
    }

    // Add our conditions to the query.
    foreach ($values as $key => $value) {
      // Don't do anything if the value is NULL.
      if ($value === NULL) {
        continue;
      }
      // 'decoupled' is a special column.
      // @todo: Switch this to a field so it can be queried generally.
      elseif ($key == 'decoupled') {
        if ($value) {
          $query->notExists('name');
        }
        else {
          $query->exists('name');
        }
      }
      else {
        $query->condition($key, $value);
      }
    }

    // If we have the prefer coupled behaviour, ensure only coupled users are
    // included.
    if ($context['behavior'] & self::BEHAVIOR_PREFER_COUPLED) {
      $query->exists('name');
    }

    // If we have the don't have the include protected roles behaviour, ensure
    // protected roles are excluded.
    if (!($context['behavior'] & self::BEHAVIOR_INCLUDE_PROTECTED_ROLES)) {
      $protected_roles = $this->settings->get('acquisitions.protected_roles');
      // Must have at least one protected role.
      if (!empty($protected_roles)) {
        $or = $query->orConditionGroup();
        $or->condition('roles', $protected_roles, 'NOT IN');
        $or->notExists('roles');
        $query->condition($or);
      }
    }

    // If we are set to take the first, we don't need to return more than one.
    // Otherwise return 2 matches so we can ignore multiple matches.
    $limit = $context['behavior'] & self::BEHAVIOR_FIRST ? 1 : 2;
    $query->range(0, $limit);

    // Get the resulting IDs.
    $uids = $query->execute();

    // If we got a single match we can return a party..
    if (count($uids) == 1) {
      return $this->userStorage->load(reset($uids));
    }

    // Store something helpful in $this->context.
    $this->failCode = count($uids) ? self::FAIL_MULTIPLE_MATCHES : self::FAIL_NO_MATCHES;

    // Otherwise we have nothing to return.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getFailCode() {
    return $this->failCode;
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->context;
  }

}
