<?php

namespace Drupal\competition;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountProxy;

/**
 * Defines a class to build displays of Competition entries.
 *
 * @ingroup competition
 */
class CompetitionEntryViewBuilder extends EntityViewBuilder {

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
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager, CompetitionManager $competition_manager, AccountProxy $current_user) {
    parent::__construct($entity_type, $entity_manager, $language_manager);

    $this->competitionManager = $competition_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('competition.manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $build = parent::view($entity, $view_mode, $langcode);

    // Always print the confirmation message for entrant user.
    if ($view_mode == 'full') {
      $competition = $this->competitionManager
        ->getCompetition($entity->bundle());

      $build['#title'] = $this->t('@cycle @label entry', [
        '@cycle' => $entity->getCycle(),
        '@label' => $competition->label(),
      ]
      );

      if ($entity->getOwnerId() == $this->currentUser->id()) {
        if ($entity->getStatus() == CompetitionEntryInterface::STATUS_FINALIZED) {
          $this->competitionManager
            ->setConfirmationMessage($entity);
        }
      }
    }

    return $build;
  }

}
