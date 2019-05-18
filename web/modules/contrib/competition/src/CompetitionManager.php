<?php

namespace Drupal\competition;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\Core\Url;
use Drupal\Core\Path\PathValidator;
use Drupal\Core\Session\AccountProxy;

/**
 * The Competition manager class.
 */
class CompetitionManager {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The competition entity storage.
   *
   * @var \Drupal\competition\CompetitionEntryStorage
   */
  protected $competitionStorage;

  /**
   * The competition_entry entity storage.
   *
   * @var \Drupal\competition\CompetitionEntryStorage
   */
  protected $competitionEntryStorage;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $translationManager;

  /**
   * Core path validator.
   *
   * @var \Drupal\Core\Path\PathValidator
   */
  protected $pathValidator;

  /**
   * Constructor method.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   * @param \Drupal\Core\StringTranslation\TranslationManager $translation_manager
   *   The translation manager.
   * @param \Drupal\Core\Path\PathValidator $path_validator
   *   The path validator service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository, ConfigFactory $config_factory, TranslationManager $translation_manager, PathValidator $path_validator) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
    $this->competitionStorage = $this->entityTypeManager->getStorage('competition');
    $this->competitionEntryStorage = $this->entityTypeManager->getStorage('competition_entry');
    $this->configFactory = $config_factory;
    $this->translationManager = $translation_manager;
    $this->pathValidator = $path_validator;

    return $this;
  }

  /**
   * Return competition.
   *
   * @param string $type
   *   Competition bundle type.
   */
  public function getCompetition($type) {
    return $this->entityRepository
      ->loadEntityByConfigTarget(
        'competition',
        $type
      );
  }

  /**
   * Load a competition from a string or Url.
   *
   * @param string|\Drupal\Core\Url $path_or_url
   *   Either a path as a string, or a Url object.
   *
   * @return \Drupal\competition\Entity\Competition|null
   *   The competition entity, or NULL if none could be loaded.
   */
  public function getCompetitionFromUrl($path_or_url) {
    $url = NULL;
    if (is_string($path_or_url)) {
      $url = $this->pathValidator->getUrlIfValid($path_or_url);
    }
    elseif ($url instanceof Url) {
      $url = $path_or_url;
    }

    $competition = NULL;

    if (!empty($url)) {
      $route_parameters = $url->getRouteParameters();
      if (isset($route_parameters['competition'])) {
        $competition = $this->getCompetition($route_parameters['competition']);
      }
    }

    return $competition;
  }

  /**
   * Get all competitions entities or IDs, optionally filtered by status.
   *
   * @param int|null $status
   *   Either CompetitionInterface::STATUS_OPEN or
   *   CompetitionInterface::STATUS_CLOSED, to filter results, or leave NULL
   *   to load all competitions.
   * @param bool $load_entities
   *   Whether to return competition entities (TRUE) or just IDs (FALSE).
   *
   * @return array|\Drupal\competition\CompetitionInterface[]
   *   Array of competition entity IDs or entities keyed by their ID
   *
   * @throws \InvalidArgumentException
   *   If $status is non-NULL, but not one of the valid competition statuses.
   */
  public function getCompetitions($status = NULL, $load_entities = FALSE) {
    if ($status !== NULL && !in_array($status, [CompetitionInterface::STATUS_OPEN, CompetitionInterface::STATUS_CLOSED])) {
      throw new \InvalidArgumentException("If non-empty, argument \$status must be one of the defined competition statuses: CompetitionInterface::STATUS_OPEN or CompetitionInterface::STATUS_CLOSED.");
    }

    $query = $this->entityTypeManager
      ->getStorage('competition')
      ->getQuery('AND');

    if (isset($status)) {
      $query->condition('status', $status);
    }

    $entity_ids = $query->execute();

    if ($load_entities) {
      return $this->competitionStorage->loadMultiple($entity_ids);
    }
    else {
      return $entity_ids;
    }

  }

  /**
   * Creates new competition entry.
   *
   * @param string $type
   *   Competition bundle type.
   */
  public function createCompetitionEntry($type, AccountProxy $user) {
    $competition = $this->getCompetition($type);

    $this->competitionEntryStorage
      ->create(array(
        'type' => $competition->id(),
        'cycle' => $competition->getCycle(),
        'uid' => $user->id(),
      ))
      ->save();
  }

  /**
   * Fetch entries.
   *
   * @param \Drupal\competition\CompetitionEntryInterface $entry
   *   A stub CompetitionEntry object that may or may not have populated values.
   * @param array $options
   *   Additional parameters to adjust results:
   *   'interval' - if present and TRUE, filter to entries created within the
   *   most recent interval (where interval is configured on competition).
   *
   * @return \Drupal\competition\CompetitionEntryInterface[]
   *   The matching CompetitionEntry results, sorted by `created`, oldest to
   *   newest.
   */
  public function getCompetitionEntries(CompetitionEntryInterface $entry, array $options = array()) {
    // Get competition and its entry limits.
    $competition = $this
      ->getCompetition($entry->bundle());

    $limits = $competition
      ->getEntryLimits();

    $intervals = $this->configFactory
      ->get('competition.settings')
      ->get('intervals');

    $intervals_keyed = array_flip($intervals);

    // Get competition_entry.type|cycle|uid entities.
    $query = $this->entityTypeManager
      ->getStorage($entry->getEntityType()->id())
      ->getQuery('AND');

    $query_conditions_1 = $query
      ->andConditionGroup()
      ->condition('type', $entry->bundle(), '=')
      ->condition('cycle', (!empty($entry->getCycle()) ? $entry->getCycle() : $competition->getCycle()), '=')
      ->condition('uid', $entry->getOwnerId(), '=');

    // Filter for competition.type.*.entry_limit.fields' value matches.
    if (!empty($limits->fields)) {

      // Validate competition.type.*.entry_limit.field_reentry.
      // We need to query only for this single field's value if we are
      // validating a new reentry. Also note the forceful inclusion of this
      // field in entry_limit.fields list.
      // @see CompetitionForm::save()
      if ($entry->isReentry && !empty($limits->field_reentry)) {
        $limits->fields = [
          $limits->field_reentry,
        ];
      }

      foreach ($limits->fields as $field_name) {
        $value = $entry->{$field_name}->getString();

        $query_conditions_1
          ->condition("{$field_name}.value", $value, '=');
      }
    }

    // Validate competition.type.*.entry_limit.interval.
    if (!empty($options['interval']) && $options['interval'] === TRUE) {
      switch ($limits->interval) {
        case 0:
          // Note: entry_limit.interval == 0 represents "per competition cycle";
          // don't include any additional clauses in the query for this case.
          break;

        case $intervals_keyed['calendar day']:
          // Filter for entries submitted on this calendar day.
          $now = new DrupalDateTime();
          $date = $now->format('Y-m-d');
          $date_start = new DrupalDateTime($date . ' 00:00:00');
          $time_start = intval($date_start->format('U'));
          $date_end = new DrupalDateTime($date . ' 23:59:59');
          $time_end = intval($date_end->format('U'));

          $query_conditions_1
            ->condition('created', $time_start, '>=')
            ->condition('created', $time_end, '<=');
          break;

        default:
          // Filter for entries submitted within most recent interval.
          $query_conditions_1
            ->condition('created', (REQUEST_TIME - $limits->interval), '>=');
          break;
      }
    }

    // Fetch filtered entities.
    $results = $query
      ->condition($query_conditions_1)
      ->sort('created', 'ASC')
      ->execute();

    if (!empty($results)) {
      $results = $entry->loadMultiple($results);
    }

    return $results;
  }

  /**
   * Retrieve entries belonging to given user.
   *
   * @param \Drupal\competition\CompetitionInterface $competition
   *   The competition entity within which to check for entries.
   * @param int $uid
   *   Retrieve entries belonging to this user.
   * @param string $cycle
   *   The cycle to which to limit entries. If NULL, defaults to active cycle.
   * @param array $options
   *   Additional parameters as accepted by getCompetitionEntries().
   *
   * @return \Drupal\competition\CompetitionEntryInterface[]
   *   The matching CompetitionEntry results, sorted by `created`, oldest to
   *   newest.
   *
   * @see CompetitionManager::getCompetitionEntries()
   */
  public function getUserEntries(CompetitionInterface $competition, $uid, $cycle = NULL, array $options = array()) {
    $entry_stub = $this->competitionEntryStorage->create(array(
      'type' => $competition->id(),
      'cycle' => (!empty($cycle) ? $cycle : $competition->getCycle()),
      'uid' => $uid,
    ));

    return $this->getCompetitionEntries($entry_stub, $options);
  }

  /**
   * Sets the confirmation message for finalized entries.
   *
   * @param \Drupal\competition\CompetitionEntryInterface $entry
   *   The competition entry.
   */
  public function setConfirmationMessage(CompetitionEntryInterface $entry) {
    // Get competition and longtext vars.
    $competition = $this
      ->getCompetition($entry->bundle());

    $longtext = $competition->getLongtext();

    if (!empty($longtext->confirmation)) {
      drupal_set_message($this->translationManager->translate('@confirmation', [
        '@confirmation' => $this->translationManager->translate($longtext->confirmation),
      ]));
    }
    else {
      drupal_set_message($this->translationManager->translate('Your entry to the %cycle %label is complete!', [
        '%cycle' => $competition->getCycleLabel(),
        '%label' => $competition->getLabel(),
      ]));
    }
  }

}
