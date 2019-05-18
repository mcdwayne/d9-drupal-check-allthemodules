<?php

namespace Drupal\competition_voting;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\competition\CompetitionManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions of the competition voting module.
 */
class CompetitionVotingPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The competition manager service.
   *
   * @var \Drupal\competition\CompetitionManager
   */
  protected $competitionManager;

  /**
   * Constructs a new CompetitionVotingPermissions instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\competition\CompetitionManager $competition_manager
   *   Competition manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CompetitionManager $competition_manager) {

    $this->entityTypeManager = $entity_type_manager;
    $this->competitionManager = $competition_manager;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('competition.manager')
    );
  }

  /**
   * Permission.
   */
  public function permissions() {

    $permissions = [];

    $cids = $this->competitionManager
      ->getCompetitions();

    $competitions = $this->entityTypeManager
      ->getStorage('competition')
      ->loadMultiple($cids);

    foreach ($competitions as $competition) {

      $permissions['vote for judged contest ' . $competition->id() . ' entry'] = [
        'title' => $this->t('%title %cycle: Vote for judged contest entries', [
          '%title' => $competition->getLabel(),
          '%cycle' => $competition->getCycle(),
        ]),
      ];

    }

    return $permissions;

  }

}
