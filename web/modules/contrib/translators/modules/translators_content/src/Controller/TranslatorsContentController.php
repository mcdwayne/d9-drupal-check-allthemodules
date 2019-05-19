<?php

namespace Drupal\translators_content\Controller;

use Drupal\content_translation\ContentTranslationManager;
use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\content_translation\Controller\ContentTranslationController;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\translators\Services\TranslatorSkills;
use Drupal\translators_content\Access\TranslatorsContentManageAccess;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for entity translation controllers.
 */
class TranslatorsContentController extends ContentTranslationController {

  /**
   * User skills service.
   *
   * @var \Drupal\translators\Services\TranslatorSkills
   */
  protected $translatorSkills;
  /**
   * Translator access manager.
   *
   * @var \Drupal\translators_content\Access\TranslatorsContentManageAccess
   */
  protected $accessManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('content_translation.manager'),
      $container->get('content_translation.manage_access'),
      $container->get('translators.skills')
    );
  }

  /**
   * TranslatorsContentController constructor.
   *
   * @param \Drupal\content_translation\ContentTranslationManagerInterface $manager
   *   Content translation manager.
   * @param \Drupal\translators_content\Access\TranslatorsContentManageAccess $access_manager
   *   Translator access manager.
   * @param \Drupal\translators\Services\TranslatorSkills $translatorSkills
   *   User skills service.
   */
  public function __construct(
    ContentTranslationManagerInterface $manager,
    TranslatorsContentManageAccess $access_manager,
    TranslatorSkills $translatorSkills
  ) {
    parent::__construct($manager);
    $this->translatorSkills = $translatorSkills;
    $this->accessManager    = $access_manager;
  }

  /**
   * Builds the translations overview page.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param string $entity_type_id
   *   (optional) The entity type ID.
   * @param bool $filter
   *   The filter option to filter content translation links or no.
   *
   * @return array
   *   Array of page elements to render.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function overview(RouteMatchInterface $route_match, $entity_type_id = NULL, $filter = TRUE) {
    $build = $this->parentBuildOverridden($route_match, $entity_type_id);
    if (!$filter || empty($this->config('translators.settings')->get('enable_filter_translation_overview_to_skills'))) {
      return $build;
    }

    $user_languages = $this->translatorSkills->getSkills();

    if (FALSE === $user_languages) {
      return $build;
    }

    if (empty($user_languages)) {
      $this->translatorSkills->showEmptyMessage();
    }

    $rows =& $build['content_translation_overview']['#rows'];

    $user_langs_rows = $other_langs_rows = [];
    $extracted = $this->extractLanguagesWithGroups($rows, $user_languages);
    if (isset($extracted[0]) && !empty($extracted[0])) {
      $user_langs_rows = $extracted[0];
    }
    if (isset($extracted[1]) && !empty($extracted[1])) {
      $other_langs_rows = $extracted[1];
    }

    if (!empty($user_langs_rows)) {
      foreach ($user_langs_rows as $key => $row) {
        $user_langs_rows[$key] = $rows[$row];
      }
      // Post processing translation operations links
      // for user registered translations.
      $this->postProcessTranslationsOperations(
        $user_langs_rows,
        $route_match->getParameter($entity_type_id)
      );
    }
    $rows = $user_langs_rows;

    if (!empty($other_langs_rows)) {
      $entity_type_id = $route_match->getParameter('entity_type_id');

      $build['more_link'] = [
        '#title' => $this->t('Show all languages'),
        '#type' => 'link',
        '#attributes' => [
          'class' => [
            'use-ajax',
            'button', 'button--small',
            'more-link',
            'more-link-translations',
          ],
          'id' => 'show-more-translations-link',
        ],
        '#url' => Url::fromRoute(
          $route_match->getRouteName() . '.more',
          [$entity_type_id => $route_match->getParameter($entity_type_id)->id(), 'method' => 'ajax']
        ),
      ];
      $build['content_translation_overview']['#attributes']['id'] = 'content-translations-list';
      $build['#attached']['library'][] = 'core/drupal.ajax';
    }

    return $build;
  }

  /**
   * Process source language argument.
   *
   * @param string $source
   *   Source language ID.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity object.
   *
   * @return string
   *   Processed language ID.
   */
  protected function processSourceLanguage($source, ContentEntityInterface $entity) {
    if (empty($this->config('translators.settings')
      ->get('enable_auto_preset_source_language_by_skills'))) {
      return $source;
    }
    foreach ($this->translatorSkills->getSourceSkills() as $langcode) {
      if (!$entity->hasTranslation($langcode)) {
        continue;
      }
      return $langcode;
    }
    return $source;
  }

  /**
   * Overridden version of parent::overview().
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   Route match.
   * @param string|null $entity_type_id
   *   Entity type ID.
   *
   * @return array
   *   Build form array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function parentBuildOverridden(RouteMatchInterface $route_match, $entity_type_id = NULL) {
    $row_title = $source_name = $this->t('n/a');
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $route_match->getParameter($entity_type_id);
    $account = $this->currentUser();
    $handler = $this->entityManager()->getHandler($entity_type_id, 'translation');
    $manager = $this->manager;
    $entity_type = $entity->getEntityType();
    $use_latest_revisions = $entity_type->isRevisionable() && ContentTranslationManager::isPendingRevisionSupportEnabled($entity_type_id, $entity->bundle());

    // Start collecting the cacheability metadata, starting with the entity and
    // later merge in the access result cacheability metadata.
    $cacheability = CacheableMetadata::createFromObject($entity);

    $languages = $this->languageManager()->getLanguages();
    $original = $entity->getUntranslated()->language()->getId();
    $translations = $entity->getTranslationLanguages();
    $field_ui = $this->moduleHandler()->moduleExists('field_ui') && $account->hasPermission('administer ' . $entity_type_id . ' fields');

    $rows = [];
    $show_source_column = FALSE;
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $storage = $this->entityTypeManager()->getStorage($entity_type_id);
    $default_revision = $storage->load($entity->id());

    if ($this->languageManager()->isMultilingual()) {
      // Determine whether the current entity is translatable.
      $translatable = FALSE;
      foreach ($this->entityManager()->getFieldDefinitions($entity_type_id, $entity->bundle()) as $instance) {
        if ($instance->isTranslatable()) {
          $translatable = TRUE;
          break;
        }
      }

      // Show source-language column if there are non-original source langcodes.
      $additional_source_langcodes = array_filter(array_keys($translations), function ($langcode) use ($entity, $original, $manager) {
        $source = $manager->getTranslationMetadata($entity->getTranslation($langcode))->getSource();
        return $source != $original && $source != LanguageInterface::LANGCODE_NOT_SPECIFIED;
      });
      $show_source_column = !empty($additional_source_langcodes);

      foreach ($languages as $language) {
        $language_name = $language->getName();
        $langcode = $language->getId();

        // If the entity type is revisionable, we may have pending revisions
        // with translations not available yet in the default revision. Thus we
        // need to load the latest translation-affecting revision for each
        // language to be sure we are listing all available translations.
        if ($use_latest_revisions) {
          $entity = $default_revision;
          $latest_revision_id = $storage->getLatestTranslationAffectedRevisionId($entity->id(), $langcode);
          if ($latest_revision_id) {
            /** @var \Drupal\Core\Entity\ContentEntityInterface $latest_revision */
            $latest_revision = $storage->loadRevision($latest_revision_id);
            // Make sure we do not list removed translations, i.e. translations
            // that have been part of a default revision but no longer are.
            if (!$latest_revision->wasDefaultRevision() || $default_revision->hasTranslation($langcode)) {
              $entity = $latest_revision;
            }
          }
          $translations = $entity->getTranslationLanguages();
        }

        $add_url = new Url(
          "entity.$entity_type_id.content_translation_add",
          [
            // Additionally process the original language
            // in order to try it be one of the translation skills.
            'source' => $this->processSourceLanguage($original, $entity),
            'target' => $language->getId(),
            $entity_type_id => $entity->id(),
          ],
          [
            'language' => $language,
          ]
        );
        $edit_url = new Url(
          "entity.$entity_type_id.content_translation_edit",
          [
            'language' => $language->getId(),
            $entity_type_id => $entity->id(),
          ],
          [
            'language' => $language,
          ]
        );
        $delete_url = new Url(
          "entity.$entity_type_id.content_translation_delete",
          [
            'language' => $language->getId(),
            $entity_type_id => $entity->id(),
          ],
          [
            'language' => $language,
          ]
        );
        $operations = [
          'data' => [
            '#type' => 'operations',
            '#links' => [],
          ],
        ];

        $links = &$operations['data']['#links'];
        if (array_key_exists($langcode, $translations)) {
          // Existing translation in the translation set: display status.
          $translation = $entity->getTranslation($langcode);
          $metadata = $manager->getTranslationMetadata($translation);
          $source = $metadata->getSource() ?: LanguageInterface::LANGCODE_NOT_SPECIFIED;
          $is_original = $langcode == $original;
          $label = $entity->getTranslation($langcode)->label();
          $link = isset($links->links[$langcode]['url']) ? $links->links[$langcode] : ['url' => $entity->toUrl()];
          if (!empty($link['url'])) {
            $link['url']->setOption('language', $language);
            $row_title = $this->l($label, $link['url']);
          }

          if (empty($link['url'])) {
            $row_title = $is_original ? $label : $this->t('n/a');
          }

          // If the user is allowed to edit the entity we point the edit link to
          // the entity form, otherwise if we are not dealing with the original
          // language we point the link to the translation form.
          $update_access = $entity->access('update', NULL, TRUE);
          $translation_access = $handler->getTranslationAccess($entity, 'update');
          $is_allowed = $this->accessManager->checkAccess($entity, $language, 'update')->isAllowed();
          $cacheability = $cacheability
            ->merge(CacheableMetadata::createFromObject($update_access))
            ->merge(CacheableMetadata::createFromObject($translation_access));
          if ($is_allowed && $update_access->isAllowed() && $entity_type->hasLinkTemplate('edit-form')) {
            $links['edit']['url'] = $entity->toUrl('edit-form');
            $links['edit']['language'] = $language;
          }
          elseif (!$is_original && $translation_access->isAllowed()) {
            $links['edit']['url'] = $edit_url;
          }
          elseif ($is_original && $is_allowed) {
            $links['edit']['url'] = $edit_url;
          }

          if (isset($links['edit'])) {
            $links['edit']['title'] = $this->t('Edit');
          }
          $status = [
            'data' => [
              '#type' => 'inline_template',
              '#template' => '<span class="status">{% if status %}{{ "Published"|t }}{% else %}{{ "Not published"|t }}{% endif %}</span>{% if outdated %} <span class="marker">{{ "outdated"|t }}</span>{% endif %}',
              '#context' => [
                'status' => $metadata->isPublished(),
                'outdated' => $metadata->isOutdated(),
              ],
            ],
          ];

          if ($is_original) {
            $language_name = $this->t('<strong>@language_name (Original language)</strong>', ['@language_name' => $language_name]);
            $source_name = $this->t('n/a');
          }
          else {
            /** @var \Drupal\Core\Access\AccessResultInterface $delete_route_access */
            $delete_route_access = $this->accessManager->checkAccess($translation, $language, 'delete');
            $cacheability->addCacheableDependency($delete_route_access);

            if ($delete_route_access->isAllowed()) {
              $source_name = isset($languages[$source]) ? $languages[$source]->getName() : $this->t('n/a');
              $delete_access = $entity->access('delete', NULL, TRUE);
              $translation_access = $handler->getTranslationAccess($entity, 'delete');
              $cacheability
                ->addCacheableDependency($delete_access)
                ->addCacheableDependency($translation_access);

              if ($delete_access->isAllowed() && $entity_type->hasLinkTemplate('delete-form')) {
                $links['delete'] = [
                  'title' => $this->t('Delete'),
                  'url' => $entity->toUrl('delete-form'),
                  'language' => $language,
                ];
              }
              elseif ($translation_access->isAllowed()) {
                $links['delete'] = [
                  'title' => $this->t('Delete'),
                  'url' => $delete_url,
                ];
              }
            }
            elseif (!$entity->hasTranslation($langcode)
              || (method_exists($entity, 'isPublished') && !$entity->isPublished())
            ) {
              $this->messenger()->addWarning($this->t('The "Delete translation" action is only available for published translations.'), FALSE);
            }
          }
        }
        else {
          // No such translation in the set yet: help user to create it.
          $row_title = $source_name = $this->t('n/a');
          $source = $entity->language()->getId();

          $create_translation_access = $handler->getTranslationAccess($entity, 'create');
          $cacheability = $cacheability
            ->merge(CacheableMetadata::createFromObject($create_translation_access));
          if ($source != $langcode && $create_translation_access->isAllowed()) {
            if ($translatable) {
              $links['add'] = [
                'title' => $this->t('Add'),
                'url' => $add_url,
              ];
            }
            elseif ($field_ui) {
              $url = new Url('language.content_settings_page');

              // Link directly to the fields tab to make it easier to find the
              // setting to enable translation on fields.
              $links['nofields'] = [
                'title' => $this->t('No translatable fields'),
                'url' => $url,
              ];
            }
          }

          $status = $this->t('Not translated');
        }
        if ($show_source_column) {
          $rows[] = [
            $language_name,
            $row_title,
            $source_name,
            $status,
            $operations,
          ];
        }
        else {
          $rows[] = [$language_name, $row_title, $status, $operations];
        }
      }
    }
    if ($show_source_column) {
      $header = [
        $this->t('Language'),
        $this->t('Translation'),
        $this->t('Source language'),
        $this->t('Status'),
        $this->t('Operations'),
      ];
    }
    else {
      $header = [
        $this->t('Language'),
        $this->t('Translation'),
        $this->t('Status'),
        $this->t('Operations'),
      ];
    }

    $build['#title'] = $this->t('Translations of %label', ['%label' => $entity->label()]);

    // Add metadata to the build render array to let other modules know about
    // which entity this is.
    $build['#entity'] = $entity;
    $cacheability
      ->addCacheTags($entity->getCacheTags())
      ->applyTo($build);

    $build['content_translation_overview'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
    return $build;
  }

  /**
   * Get more languages.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   Route match interface.
   * @param string|null $entity_type_id
   *   Entity type ID.
   * @param string $method
   *   Method name. Values allowed - "noajax" and "ajax". Defaults to "ajax".
   *
   * @return array|\Drupal\Core\Ajax\AjaxResponse
   *   Array of languages or AJAX response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function getMoreLanguages(RouteMatchInterface $route_match, $entity_type_id = NULL, $method = 'ajax') {
    $build = self::overview($route_match, $entity_type_id, FALSE);

    $rows =& $build['content_translation_overview']['#rows'];
    $user_languages = $this->translatorSkills->getSkills();

    $user_langs_rows = $other_langs_rows = [];
    $extracted = $this->extractLanguagesWithGroups($rows, $user_languages);
    if (isset($extracted[0]) && !empty($extracted[0])) {
      $user_langs_rows = $extracted[0];
    }
    if (isset($extracted[1]) && !empty($extracted[1])) {
      $other_langs_rows = $extracted[1];
    }

    $other_langs_rows = array_intersect_key($rows, array_flip($other_langs_rows));

    if ($method == 'noajax') {
      $rows = $other_langs_rows;
      return $build;
    }
    elseif ($method == 'ajax') {
      $response = new AjaxResponse();
      foreach ($user_langs_rows as $key => $row) {
        $user_langs_rows[$key] = $rows[$row];
      }
      // Post processing translation operations links
      // for user registered translations.
      $this->postProcessTranslationsOperations(
        $user_langs_rows,
        $route_match->getParameter($entity_type_id)
      );

      $rows = array_merge($user_langs_rows, $other_langs_rows);

      $replace = new ReplaceCommand('#content-translations-list', $build['content_translation_overview']);
      $remove  = new RemoveCommand('#show-more-translations-link');

      $response->addCommand($replace);
      $response->addCommand($remove);
      return $response;
    }
    return [];
  }

  /**
   * Extract language from row.
   *
   * @param array &$row
   *   Row array.
   *
   * @return mixed
   *   Extracted language from row.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function extractLanguageFromRow(array &$row) {
    $label = reset($row);
    self::extractDefaultLanguageName($label);
    return !is_string($label)
      ? $this->languageManager->getDefaultLanguage()
      : $this->getLanguageByLabel($label);
  }

  /**
   * Extract languages with groups.
   *
   * @param array &$rows
   *   Rows array.
   * @param array $user_languages
   *   User languages array.
   *
   * @return array
   *   Languages array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function extractLanguagesWithGroups(array &$rows, array $user_languages) {
    $groups = []; $original_key = NULL;
    foreach ($rows as $key => $row) {
      $language = $this->extractLanguageFromRow($row);
      $is_original = stripos((string) reset($row), 'original') !== FALSE;

      $delta = $language instanceof LanguageInterface
        && (in_array($language->getId(), $user_languages) || ($is_original && !empty($this->config('translators.settings')->get('always_display_original_language_translation_overview'))))
        ? 0 : 1;

      if ($is_original) {
        $original_key = $key;
      }
      $groups[$delta][] = $key;
    }
    // Move original language to the top of the array if visable.
    if (!is_null($original_key) && !empty($groups[0]) && in_array($original_key, $groups[0])) {
      $original_key = array_search($original_key, $groups[0]);
      $original = $groups[0][$original_key];
      unset($groups[0][$original_key]);
      array_unshift($groups[0], $original);
    }

    return $groups;
  }

  /**
   * Get language config entity.
   *
   * @param string|null $label
   *   Language label.
   *
   * @return \Drupal\Core\Language\LanguageInterface|mixed|null
   *   Language object if exists.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getLanguageByLabel($label = NULL) {
    if (empty($label)) {
      return NULL;
    }
    $languages = $this->entityTypeManager
      ->getStorage('configurable_language')
      ->loadByProperties(['label' => $label]);
    return !empty($languages) ? reset($languages) : NULL;
  }

  /**
   * Additional post processing function.
   *
   * Post processing translation operations links
   * for user registered translations.
   *
   * @param array $user_langs_rows
   *   Rows array.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Processed entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  protected function postProcessTranslationsOperations(array &$user_langs_rows, ContentEntityInterface $entity) {
    foreach ($user_langs_rows as &$langs_row) {
      if (!empty($langs_row) && is_array($langs_row)) {
        $label = reset($langs_row);
        self::extractDefaultLanguageName($label);
        if (!empty($label) && is_string($label)) {
          $key = self::getLastArrayKey($langs_row);
          $operations =& $langs_row[$key]['data']['#links'];
          /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $language */
          $language = static::getLanguageByLabel($label);
          $language_object = $this->languageManager
            ->getLanguage($language->id());
          if ($entity->hasTranslation($language->id())) {
            if ($this->isOperationAllowed($entity, $language_object, 'update')) {
              $operations['edit'] = [
                'url'      => $entity->toUrl('edit-form'),
                'language' => $language,
                'title'    => $this->t('Edit'),
              ];
            }
            if ($this->isOperationAllowed($entity, $language_object, 'delete')
              && $entity->getEntityType()->hasLinkTemplate('delete-form')) {
              $operations['delete'] = [
                'url'      => $entity->toUrl('delete-form'),
                'language' => $language,
                'title'    => $this->t('Delete'),
              ];
            }
          }
          elseif ($this->isOperationAllowed($entity, $language_object, 'create')) {
            $operations['add'] = [
              'url'      => $this->buildTranslationCreateUrl($entity, $language),
              'language' => $language,
              'title'    => $this->t('Add'),
            ];
          }
          else {
            unset($operations['add']);
          }
        }
      }
    }
  }

  /**
   * Small helper method for extracting the default language label.
   *
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup &$name
   *   Language name/label.
   */
  private static function extractDefaultLanguageName(&$name) {
    if ($name instanceof TranslatableMarkup) {
      $name = $name->getArguments()['@language_name'];
    }
  }

  /**
   * Simple wrapper for checking entity operation access.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Content entity.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   Language object.
   * @param string $op
   *   Operation name. Defaults to "delete".
   *
   * @return bool
   *   Access checking result.
   */
  protected function isOperationAllowed(ContentEntityInterface $entity, LanguageInterface $language = NULL, $op = 'delete') {
    if (!$language instanceof LanguageInterface) {
      $language = $this->languageManager
        ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT);
    }
    return $this->accessManager
      ->checkAccess($entity, $language, $op)
      ->isAllowed();
  }

  /**
   * Helper method to build "Add" url for translation.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity object.
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $language
   *   Translation language entity.
   *
   * @return \Drupal\Core\Url
   *   URL object.
   */
  private function buildTranslationCreateUrl(ContentEntityInterface $entity, ConfigEntityInterface $language) {
    $entity_type_id = $entity->getEntityTypeId();
    $route_name     = "entity.$entity_type_id.content_translation_add";
    return Url::fromRoute($route_name, [
      'source'        => $this->processSourceLanguage($entity->getUntranslated()->language()->getId(), $entity),
      'target'        => $language->id(),
      $entity_type_id => $entity->id(),
    ]);
  }

  /**
   * Get last array key.
   *
   * @param array $array
   *   Array to be processed.
   *
   * @return mixed
   *   Last array key.
   */
  private static function getLastArrayKey(array $array) {
    $keys = array_keys($array);
    return end($keys);
  }

}
