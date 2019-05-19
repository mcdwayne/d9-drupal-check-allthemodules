<?php

namespace Drupal\translation_views\Plugin\views\field;

use Drupal\content_translation\ContentTranslationManager;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\translation_views\EntityTranslationInfo;
use Drupal\translation_views\TranslationViewsTargetLanguage as TargetLanguage;
use Drupal\views\Plugin\views\field\EntityOperations;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Renders translation operations links.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("translation_views_operations")
 */
class TranslationOperationsField extends EntityOperations {
  use TargetLanguage;

  /**
   * Current user account object.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;
  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    array $plugin_definition,
    EntityManagerInterface $entity_manager,
    EntityTypeManager $entity_type_manager,
    LanguageManagerInterface $language_manager,
    AccountProxyInterface $account
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_manager, $language_manager);
    $this->currentUser       = $account;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('entity.manager'),
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   *
   * Build operation links.
   */
  public function render(ResultRow $values) {
    /* @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity          = $this->getEntity($values);
    $langcode_key    = $this->buildSourceEntityLangcodeKey($entity);
    $source_langcode = $values->{$langcode_key};
    $operations      = $this->getTranslationOperations($entity, $source_langcode);

    if ($this->options['destination']) {
      foreach ($operations as &$operation) {
        if (!isset($operation['query'])) {
          $operation['query'] = [];
        }
        $operation['query'] += $this->getDestinationArray();
      }
    }
    $build = [
      '#type'  => 'operations',
      '#links' => $operations,
    ];
    $build['#cache']['contexts'][] = 'url.query_args:target_language';

    return $build;
  }

  /**
   * Build value key.
   *
   * Value key based on base table,
   * and system name of langcode key (it might be differ then just 'langcode'),
   * usually table alias is [entity_type]_field_data_[langcode_key].
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Used to extract entity type info from entity.
   *
   * @return string
   *   The value key.
   */
  protected function buildSourceEntityLangcodeKey(ContentEntityInterface $entity) {
    return implode('_', [
      $this->view->storage->get('base_table'),
      $entity->getEntityType()->getKey('langcode'),
    ]);
  }

  /**
   * Operation links manager.
   *
   * Decide which links we should generate:
   * based on user permissions,
   * and entity state (has translation, is default, etc.).
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The source entity to get context for decision.
   * @param string $source_langcode
   *   The langcode of the row.
   *
   * @return array
   *   Operation links' render array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getTranslationOperations(ContentEntityInterface $entity, $source_langcode) {
    $links           = [];
    $target_langcode = $this->getTargetLangcode()
      ? $this->getTargetLangcode()
      : $source_langcode;

    /* @var \Drupal\content_translation\ContentTranslationHandlerInterface $handler */
    $handler = $this->getEntityManager()
      ->getHandler($entity->getEntityTypeId(), 'translation');

    // Construct special object to store common properties,
    // it will be used by all builder functions,
    // just as "trait" but for methods.
    $translation_info = new EntityTranslationInfo(
      $entity,
      $handler,
      $this->languageManager->getLanguage($target_langcode),
      $this->languageManager->getLanguage($source_langcode)
    );

    $is_default = static::isDefaultTranslation($translation_info);

    // Build edit & delete link.
    if (array_key_exists($target_langcode, $entity->getTranslationLanguages())) {
      // If the user is allowed to edit the entity we point the edit link to
      // the entity form, otherwise if we are not dealing with the original
      // language we point the link to the translation form.
      if ($entity->access('update', NULL)
        && $translation_info->entityType->hasLinkTemplate('edit-form')
      ) {
        $links += $this->buildEditLink($translation_info, 'entity');
      }
      elseif (!$is_default
        && $translation_info->getTranslationAccess('update')
        || $this->checkForOperationTranslationPermission('update')
      ) {
        $links += $this->buildEditLink($translation_info, 'translation');
      }

      // Build delete link.
      if ($entity->access('delete')
        && $translation_info->entityType->hasLinkTemplate('delete-form')
      ) {
        $links += $this->buildDeleteLink($translation_info, 'entity');
      }
      elseif (!$is_default
        && $translation_info->getTranslationAccess('delete')
        || $this->checkForOperationTranslationPermission('delete')
      ) {
        $links += $this->buildDeleteLink($translation_info, 'translation');
      }
    }
    // Check if there are pending revisions.
    elseif ($this->pendingRevisionExist($translation_info)) {
      // If the user is allowed to edit the entity we point the edit link to
      // the entity form, otherwise if we are not dealing with the original
      // language we point the link to the translation form.
      if ($entity->access('update', NULL)
        && $translation_info->entityType->hasLinkTemplate('edit-form')
      ) {
        $links += $this->buildEditLink($translation_info, 'entity');
      }
      elseif (!$is_default
        && $translation_info->getTranslationAccess('update')
        || $this->checkForOperationTranslationPermission('update')
      ) {
        $links += $this->buildEditLink($translation_info, 'translation');
      }
    }
    // Build add link.
    elseif (!empty($target_langcode)
      && $translation_info->entity->isTranslatable()
      && $translation_info->getTranslationAccess('create')
    ) {
      // No such translation.
      $links += $this->buildAddLink($translation_info);
    }

    return $links;
  }

  /**
   * Check if the current user has appropriate translation permission.
   *
   * @param string $operation
   *   Operation name.
   *
   * @return bool
   *   TRUE - if user permitted to perform specified operation, FALSE otherwise.
   */
  protected function checkForOperationTranslationPermission($operation) {
    if (!in_array($operation, ['create', 'update', 'delete'], TRUE)) {
      return FALSE;
    }
    return $this->currentUser
      ->hasPermission("$operation content translations");
  }

  /**
   * Check if target translation is the default translation.
   *
   * @param \Drupal\translation_views\EntityTranslationInfo $translation_info
   *   Entity info object.
   *
   * @return bool
   *   Checking result.
   */
  protected static function isDefaultTranslation(EntityTranslationInfo $translation_info) {
    $default_langcode = $translation_info->entity
      ->getUntranslated()
      ->language()
      ->getId();
    $target_langcode = $translation_info->targetLanguage->getId();
    return $default_langcode === $target_langcode;
  }

  /**
   * Check if pending revision exist for this translation.
   *
   * @param \Drupal\translation_views\EntityTranslationInfo $translation_info
   *   Entity info object.
   *
   * @return bool
   *   TRUE - if pending revision exist, FALSE otherwise.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function pendingRevisionExist(EntityTranslationInfo $translation_info) {
    $pending_revision_enabled = ContentTranslationManager::isPendingRevisionSupportEnabled($translation_info->entityTypeId);
    if ($this->moduleHandler->moduleExists('content_moderation') && $pending_revision_enabled) {
      /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
      $storage = $this->entityTypeManager->getStorage($translation_info->entityTypeId);
      $entity  = $storage->load($translation_info->entity->id());
      $translation_has_revision = $storage->getLatestTranslationAffectedRevisionId(
        $entity->id(),
        $translation_info->targetLanguage->getId()
      );
      if ($translation_has_revision) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Add link builder.
   */
  protected function buildAddLink(EntityTranslationInfo $translation_info) {
    $links = [];

    $add_url = new Url(
      "entity.{$translation_info->entityTypeId}.content_translation_add",
      [
        'source' => $translation_info->sourceLanguage->getId(),
        'target' => $translation_info->targetLanguage->getId(),
        $translation_info->entityTypeId => $translation_info->entity->id(),
      ],
      [
        'language' => $translation_info->targetLanguage,
      ]
    );

    $links['add'] = [
      'title' => $this->t('Add'),
      'url'   => $add_url,
    ];
    return $links;
  }

  /**
   * Delete link builder.
   */
  protected function buildDeleteLink(EntityTranslationInfo $translation_info, $type = FALSE) {
    $links = [];

    if (!$type) {
      return $links;
    }

    if ($type == 'entity') {
      $links['delete'] = [
        'title'    => $this->t('Delete'),
        'url'      => $translation_info->entity->toUrl('delete-form'),
        'language' => $translation_info->targetLanguage,
      ];
    }
    elseif ($type == 'translation') {
      $links['delete'] = [
        'title' => $this->t('Delete'),
        'url'   => new Url(
          "entity.{$translation_info->entityTypeId}.content_translation_delete",
          [
            'language' => $translation_info->targetLanguage->getId(),
            $translation_info->entityTypeId => $translation_info->entity->id(),
          ],
          [
            'language' => $translation_info->targetLanguage,
          ]
        ),
      ];
    }
    return $links;
  }

  /**
   * Edit link builder.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  protected function buildEditLink(EntityTranslationInfo $translation_info, $type = FALSE) {
    $links = [];

    if ($type == 'entity') {
      $links['edit']['url'] = $translation_info->entity->toUrl('edit-form');
      $links['edit']['language'] = $translation_info->targetLanguage;
    }
    elseif ($type == 'translation') {
      $links['edit']['url'] = new Url(
        "entity.{$translation_info->entityTypeId}.content_translation_edit",
        [
          'language' => $translation_info->targetLanguage->getId(),
          $translation_info->entityTypeId => $translation_info->entity->id(),
        ],
        [
          'language' => $translation_info->targetLanguage,
        ]
      );
      ;
    }

    if (isset($links['edit'])) {
      $links['edit']['title'] = $this->t('Edit');
    }

    return $links;
  }

}
