<?php

/**
 * @file
 * Contains \Drupal\rng_conflict\Form\ConflictForm.
 */

namespace Drupal\rng_conflict\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormBase;
use Drupal\rng\EventManagerInterface;
use Drupal\rng_conflict\RngConflictProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Configure event conflict settings.
 */
class ConflictForm extends FormBase {

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * The RNG conflict provider.
   *
   * @var \Drupal\rng_conflict\RngConflictProviderInterface
   */
  protected $rngConflictProvider;

  /**
   * Constructs a new ConflictForm object.
   *
   * @param \Drupal\rng\EventManagerInterface $event_manager
   *   The RNG event manager.
   * @param \Drupal\rng_conflict\RngConflictProviderInterface $conflict_provider
   *   The RNG conflict provider.
   */
  public function __construct(EventManagerInterface $event_manager, RngConflictProviderInterface $conflict_provider) {
    $this->eventManager = $event_manager;
    $this->rngConflictProvider = $conflict_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('rng.event_manager'),
      $container->get('rng_conflict.conflict_provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rng_event_conflict';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, EntityInterface $rng_event = NULL) {
    $form['help']['#markup'] = $this->t('A registrant will not be able to register for %label if they are also registered for any one of the following:', [
      '%label' => $rng_event->label(),
    ]);

    $form['events'] = [
      '#type' => 'table',
      '#header' => [$this->t('Label')],
      '#empty' => $this->t('No conflicting events found.'),
    ];

    foreach ($this->rngConflictProvider->getSimilarEvents($rng_event) as $event) {
      $row = [];
      $row['entity']['#markup'] = $event->toLink()->toString();
      $form['events'][] = $row;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
