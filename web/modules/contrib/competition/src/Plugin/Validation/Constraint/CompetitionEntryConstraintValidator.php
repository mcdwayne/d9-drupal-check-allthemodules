<?php

namespace Drupal\competition\Plugin\Validation\Constraint;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\competition\CompetitionInterface;
use Drupal\competition\CompetitionManager;

/**
 * Validates the CompetitionEntry constraint.
 */
class CompetitionEntryConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {
  /**
   * Validator 2.5 and upwards compatible execution context.
   *
   * @var \Symfony\Component\Validator\Context\ExecutionContextInterface
   */
  protected $context;

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
   * The translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $translationManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The competition manager.
   *
   * @var \Drupal\competition\CompetitionManager
   */
  protected $competitionManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Constructs a new CompetitionEntryConstraintValidator.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository, TranslationManager $translation_manager, ConfigFactory $config_factory, CompetitionManager $competition_manager, AccountProxy $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
    $this->translationManager = $translation_manager;
    $this->configFactory = $config_factory;
    $this->competitionManager = $competition_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity.repository'),
      $container->get('string_translation'),
      $container->get('config.factory'),
      $container->get('competition.manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entry, Constraint $constraint) {
    // Get the competition the entry belongs to, its entry limits and global
    // interval settings.
    $competition = $this->entityRepository
      ->loadEntityByConfigTarget(
        $entry->getEntityType()->getBundleEntityType(),
        $entry->bundle()
      );

    $limits = $competition
      ->getEntryLimits();

    $intervals = $this->configFactory
      ->get('competition.settings')
      ->get('intervals');

    $intervals_keyed = array_flip($intervals);

    // Validate competition.type.*.status.
    if ($competition->getStatus() != CompetitionInterface::STATUS_OPEN) {
      if (!$this->currentUser->hasPermission('administer competition entries')) {
        $this->context
          ->buildViolation($constraint->messageCompetitionClosed)
          ->setParameter('%cycle%', $competition->getCycleLabel())
          ->setParameter('%label%', $competition->label())
          ->atPath('type')
          ->addViolation();
      }
    }

    // Validate competition.type.*.entry_limit.require_user.
    if ($entry->getOwner()->isAnonymous() && !empty($limits->require_user)) {
      $this->context
        ->buildViolation($constraint->messageLimitRequireUser)
        ->atPath('uid')
        ->addViolation();
    }

    // Do no further validation for existing entries.
    if (!$entry->isNew()) {
      return;
    }

    // Pre-validation complete, now fetch any existing entries based on
    // current stub. Do not filter to entries within interval limit, so we can
    // split those out manually below for further validations.
    $results = $this->competitionManager
      ->getCompetitionEntries($entry);

    if (!empty($results)) {
      // Validate competition.type.*.entry_limit.count, unless unlimited entries
      // are accepted.
      // Note: entry_limit.count == 0 represents "unlimited".
      if ($limits->count > 0) {
        // Filter for entries submitted within most recent interval.
        $results_interval = [];

        switch ($limits->interval) {
          case 0:
            // Interval of 0 means within the cycle - results were already
            // filtered by cycle.
            $results_interval = $results;
            break;

          case $intervals_keyed['calendar day']:
            // Within current calendar day.
            $now = new DrupalDateTime();
            $date = $now->format('Y-m-d');
            $date_start = new DrupalDateTime($date . ' 00:00:00');
            $time_start = intval($date_start->format('U'));
            $date_end = new DrupalDateTime($date . ' 23:59:59');
            $time_end = intval($date_end->format('U'));

            foreach ($results as $id => $result) {
              if ($result->getCreatedTime() >= $time_start && $result->getCreatedTime() <= $time_end) {
                $results_interval[] = $id;
              }
            }
            break;

          default:
            // Within last $interval.
            foreach ($results as $id => $result) {
              if ($result->getCreatedTime() >= (REQUEST_TIME - $limits->interval)) {
                $results_interval[] = $id;
              }
            }
            break;
        }

        if (count($results_interval) >= $limits->count) {
          // User has already met their entry quota for the competition's
          // entry interval.
          $interval_label = ' in the last ' . $intervals[$limits->interval];
          switch ($limits->interval) {
            case 0:
              $interval_label = '';
              break;

            case $intervals_keyed['calendar day']:
              $interval_label = ' today';
              break;
          }

          $this->context
            ->buildViolation($constraint->messageLimitReached)
            ->setParameter('%cycle%', $competition->getCycleLabel())
            ->setParameter('%label%', $competition->label())
            ->setParameter('@count@', $limits->count . ' ' . $this->translationManager->formatPlural($limits->count, 'time', 'times'))
            ->setParameter('@interval@', $interval_label)
            ->atPath('cycle')
            ->addViolation();
        }
      }
    }
    else {
      // Validate competition.type.*.entry_limit.field_reentry.
      // If no entries were found when validating the single reentry field, user
      // is attempting to reenter when they've never actually entered before.
      // Note: the reentry field condition is incorporated into the query -.
      // @see CompetitionManager::getCompetitionEntries()
      if ($entry->isReentry) {
        $this->context
          ->buildViolation($constraint->messageReentryInvalidField)
          ->setParameter('%cycle%', $competition->getCycleLabel())
          ->setParameter('%label%', $competition->label())
          ->setParameter('@field@', $entry->{$limits->field_reentry}->getFieldDefinition()->getLabel())
          ->setParameter('%value%', $entry->{$limits->field_reentry}->getString())
          ->setCause('field_reentry')
          ->atPath('cycle')
          ->addViolation();
      }
    }

  }

}
