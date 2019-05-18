<?php

declare(strict_types = 1);

namespace Drupal\scheduled_transitions\Form\Entity;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Xss;
use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Entity\TranslatableRevisionableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\Tableselect;
use Drupal\scheduled_transitions\Entity\ScheduledTransition;
use Drupal\workflows\TransitionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Scheduled transitions add form.
 */
class ScheduledTransitionAddForm extends ContentEntityForm {

  /**
   * Various date related functionality.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * General service for moderation-related questions about Entity API.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new ScheduledTransitionAddForm.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   Various date related functionality.
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderationInformation
   *   General service for moderation-related questions about Entity API.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, DateFormatterInterface $dateFormatter, ModerationInformationInterface $moderationInformation, LanguageManagerInterface $languageManager) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->dateFormatter = $dateFormatter;
    $this->moderationInformation = $moderationInformation;
    $this->languageManager = $languageManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('date.formatter'),
      $container->get('content_moderation.moderation_information'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form['#theme'] = 'scheduled_transitions_form_add';

    $entity = $this->getEntity();

    $header = [];
    $header['revision_id'] = $this->t('Revision');
    $header['state'] = $this->t('State');
    if ($entity instanceof RevisionLogInterface) {
      $header['revision_time'] = $this->t('Saved on');
      $header['revision_author'] = $this->t('Saved by');
      $header['revision_log'] = $this->t('Log');
    }

    $newMetaWrapperId = 'new-meta-wrapper';
    $toOptionsWrapperId = 'to-options-wrapper';

    $form['revision'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#caption' => $this->t('Select which revision you wish to move to a new state.'),
      '#options' => $this->getRevisionOptions($entity),
      '#multiple' => FALSE,
      '#footer' => [
        [
          [
            'colspan' => count($header) + 1,
            'data' => ['#plain_text' => $this->t('Revisions are ordered from newest to oldest.')],
          ],
        ],
      ],
      '#process' => [
        [Tableselect::class, 'processTableselect'],
        '::revisionProcess',
      ],
      '#new_meta_wrapper_id' => $newMetaWrapperId,
    ];

    $form['new_meta'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['container-inline']],
      '#prefix' => '<div id="' . $newMetaWrapperId . '">',
      '#suffix' => '</div>',
    ];

    $workflow = $this->moderationInformation->getWorkflowForEntity($entity);
    $workflowPlugin = $workflow->getTypePlugin();

    // Populate options with nothing.
    $stateOptions = [];
    $input = $form_state->getUserInput();
    $revision = $input['revision'] ?? 0;
    if ($revision > 0) {
      $entityStorage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());

      $entityRevision = $entityStorage->loadRevision($revision);
      $state = $entityRevision->moderation_state->value;

      /** @var \Drupal\workflows\TransitionInterface[] $toTransitions */
      $toTransitions = $workflowPlugin->getTransitionsForState($state, TransitionInterface::DIRECTION_FROM);
      foreach ($toTransitions as $toTransition) {
        $stateOptions[$toTransition->to()->id()] = $toTransition->label();
      }
    }

    if ($revision > 0) {
      $form['new_meta']['state_help']['#markup'] = $this->t('<strong>Execute transition</strong>');
      $form['new_meta']['state'] = [
        '#type' => 'select',
        '#options' => $stateOptions,
        '#empty_option' => $this->t('- Select -'),
        '#required' => TRUE,
        '#ajax' => [
          'callback' => '::ajaxCallbackToOptions',
          'wrapper' => $toOptionsWrapperId,
          'effect' => 'fade',
        ],
      ];

      $form['new_meta']['on_help']['#markup'] = $this->t('<strong>on date</strong>');
      $form['new_meta']['on'] = [
        '#type' => 'datetime',
        '#default_value' => new \DateTime(),
        '#required' => TRUE,
      ];
    }
    else {
      $form['new_meta']['state_help']['#markup'] = $this->t('Select a revision above');
    }

    /** @var \Drupal\content_moderation\ContentModerationState|null $to */
    $to = !empty($input['state']) ? $workflowPlugin->getState($input['state']) : NULL;
    $form['to_options'] = [
      '#type' => 'container',
      '#prefix' => '<div id="' . $toOptionsWrapperId . '">',
      '#suffix' => '</div>',
    ];
    if ($to && $to->isDefaultRevisionState()) {
      $form['to_options']['recreate_non_default_head'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Recreate pending revision'),
        '#description' => $this->t('Before creating this revision, check if there is any pending work. If so then recreate it. Regardless of choice, revisions are safely retained in history, and can be reverted manually.'),
        '#default_value' => TRUE,
      ];
    }

    return $form;
  }

  /**
   * Add AJAX functionality to revision radios.
   *
   * @param array $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param array $complete_form
   *   Complete form.
   *
   * @return array
   *   The modified element.
   */
  public function revisionProcess(array &$element, FormStateInterface $form_state, array &$complete_form): array {
    // Add AJAX to tableselect.
    $newMetaWrapperId = $element['#new_meta_wrapper_id'];
    foreach (Element::children($element) as $key) {
      $element[$key]['#ajax'] = [
        'event' => 'change',
        'callback' => '::ajaxCallbackNewMeta',
        'wrapper' => $newMetaWrapperId,
        'progress' => [
          'type' => 'fullscreen',
        ],
        'effect' => 'fade',
      ];
    }
    return $element;
  }

  /**
   * Ajax handler for new meta container.
   */
  public function ajaxCallbackNewMeta($form, FormStateInterface $form_state): array {
    return $form['new_meta'];
  }

  /**
   * Ajax handler for to options container.
   */
  public function ajaxCallbackToOptions($form, FormStateInterface $form_state): array {
    return $form['to_options'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    if (empty($form_state->getValue('revision'))) {
      $form_state->setError($form['revision'], $this->t('Revision must be selected.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $entity = $this->getEntity();
    $entityRevisionId = $form_state->getValue('revision');
    $workflow = $this->moderationInformation->getWorkflowForEntity($entity);
    $newState = $form_state->getValue(['state']);
    /** @var \Drupal\Core\Datetime\DrupalDateTime $onDate */
    $onDate = $form_state->getValue(['on']);

    $options = [];
    if ($form_state->getValue('recreate_non_default_head')) {
      $options[ScheduledTransition::OPTION_RECREATE_NON_DEFAULT_HEAD] = TRUE;
    }

    $scheduledTransitionStorage = $this->entityTypeManager->getStorage('scheduled_transition');
    /** @var \Drupal\scheduled_transitions\Entity\ScheduledTransitionInterface $scheduledTransition */
    $scheduledTransition = $scheduledTransitionStorage->create([
      'entity' => [$entity],
      'entity_revision_id' => $entityRevisionId,
      'entity_revision_langcode' => $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId(),
      'author' => [$this->currentUser()->id()],
      'workflow' => $workflow->id(),
      'moderation_state' => $newState,
      'transition_on' => $onDate->getTimestamp(),
      'options' => [
        $options,
      ],
    ]);
    $scheduledTransition->save();

    $this->messenger()->addMessage($this->t('Scheduled a transition for @date', [
      '@date' => $this->dateFormatter->format($onDate->getTimestamp()),
    ]));
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state): array {
    $actions['submit']['#attached']['library'][] = 'core/drupal.dialog.ajax';

    $actions['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Schedule transition'),
      '#submit' => ['::submitForm'],
    ];
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): void {
    // Not saving.
  }

  /**
   * Get revisions for an entity as options for a tableselect.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Get revisions for this entity.
   *
   * @return array
   *   An array of options suitable for a tableselect element.
   */
  protected function getRevisionOptions(EntityInterface $entity): array {
    $entityTypeId = $entity->getEntityTypeId();
    $entityDefinition = $this->entityTypeManager->getDefinition($entityTypeId);
    $entityStorage = $this->entityTypeManager->getStorage($entityTypeId);

    $workflow = $this->moderationInformation->getWorkflowForEntity($entity);
    $workflowPlugin = $workflow->getTypePlugin();
    $workflowStates = $workflowPlugin ? $workflowPlugin->getStates() : [];

    /** @var int[] $ids */
    $ids = $entityStorage->getQuery()
      ->allRevisions()
      ->condition($entityDefinition->getKey('id'), $entity->id())
      ->condition($entityDefinition->getKey('langcode'), $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId())
      ->sort($entityDefinition->getKey('revision'), 'DESC')
      ->execute();

    $revisionIds = array_keys($ids);
    $entityRevisions = array_map(function (string $revisionId) use ($entityStorage): EntityInterface {
      $revision = $entityStorage->loadRevision($revisionId);
      // When the entity is translatable, load the translation for the current
      // language.
      if ($revision instanceof TranslatableInterface) {
        $revision = $revision->getTranslation($this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId());
      }
      return $revision;
    }, array_combine($revisionIds, $revisionIds));

    // When the entity is translatable, every revision contains a copy for every
    // translation. We only want to show the revisions that affected the
    // translation for the current language.
    $entityRevisions = array_filter($entityRevisions, function (EntityInterface $revision) {
      return $revision instanceof TranslatableRevisionableInterface ? $revision->isRevisionTranslationAffected() : TRUE;
    });

    return array_map(
      function (EntityInterface $entityRevision) use ($workflowStates): array {
        /** @var \Drupal\Core\Entity\EntityInterface|\Drupal\Core\Entity\RevisionableInterface $entityRevision */
        $option = [];
        $revisionTArgs = ['@revision_id' => $entityRevision->getRevisionId()];

        // Dont add the arg to toLink in case this particular entity has
        // overwritten the default value of the param.
        $toLinkArgs = [$this->t('#@revision_id', $revisionTArgs)];
        if ($entityRevision->hasLinkTemplate('revision')) {
          $toLinkArgs[] = 'revision';
        }
        $revisionLink = $entityRevision->toLink(...$toLinkArgs);
        $revisionCell = $revisionLink->toRenderable();
        $revisionCell['#attributes'] = [
          'target' => '_blank',
        ];

        $option['revision_id']['data'] = $revisionCell;
        $moderationState = $workflowStates[$entityRevision->moderation_state->value] ?? NULL;
        $option['state']['data'] = $moderationState ? $moderationState->label() : $this->t('- Unknown state -');
        if ($entityRevision instanceof RevisionLogInterface) {
          $option['revision_time']['data']['#plain_text'] = $this->dateFormatter
            ->format($entityRevision->getRevisionCreationTime());
          $revisionUser = $entityRevision->getRevisionUser();
          $option['revision_author']['data'] = $revisionUser ? $revisionUser->toLink() : $this->t('- Missing user -');
          if ($revisionLog = $entityRevision->getRevisionLogMessage()) {
            $option['revision_log']['data'] = [
              '#markup' => $revisionLog,
              '#allowed_tags' => Xss::getHtmlTagList(),
            ];
          }
          else {
            $option['revision_log']['data'] = $this->t('<em>- None -</em>');
          }
        }

        return $option;
      },
      $entityRevisions
    );
  }

}
