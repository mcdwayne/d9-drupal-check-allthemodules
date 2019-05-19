<?php

namespace Drupal\translation_form\Service;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Theme\ThemeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Class TranslationFormFieldsPreviews.
 *
 * @package Drupal\translation_form\Service
 */
class TranslationFormFieldsPreviews implements ContainerInjectionInterface {
  use StringTranslationTrait;

  /**
   * Array of translatable fields.
   *
   * @var array
   */
  protected $fields = [];
  /**
   * Fields translations data.
   *
   * @var array
   */
  protected $translations = [];
  /**
   * Content entity to operate on.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface|null
   */
  protected $entity = NULL;
  /**
   * Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;
  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  /**
   * Renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;
  /**
   * Theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;
  /**
   * Current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;
  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The content translation manager.
   *
   * @var \Drupal\content_translation\ContentTranslationManagerInterface
   */
  protected $manager;

  /**
   * TranslationFormFieldsPreviews constructor.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer service.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   Theme manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $match
   *   Current route match.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\content_translation\ContentTranslationManagerInterface $manager
   *   The content translation manager service.
   */
  public function __construct(
    LanguageManagerInterface $language_manager,
    RendererInterface $renderer,
    ThemeManagerInterface $theme_manager,
    RouteMatchInterface $match,
    EntityTypeManagerInterface $entity_type_manager,
    MessengerInterface $messenger,
    ContentTranslationManagerInterface $manager
  ) {
    $this->languageManager   = $language_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer          = $renderer;
    $this->themeManager      = $theme_manager;
    $this->routeMatch        = $match;
    $this->messenger         = $messenger;
    $this->manager           = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('language_manager'),
      $container->get('renderer'),
      $container->get('theme.manager'),
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
      $container->get('messenger'),
      $container->get('content_translation.manager')
    );
  }

  /**
   * Set content entity to operate on.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Content entity object.
   *
   * @return \Drupal\translation_form\Service\TranslationFormFieldsPreviews
   *   Fluent method.
   */
  public function setEntity(ContentEntityInterface $entity) {
    $this->entity = $entity;
    return $this;
  }

  /**
   * Get languages for preview.
   *
   * @return \Drupal\Core\Language\LanguageInterface[]
   *   Language objects available for preview.
   */
  public function getPreviewLanguages() {

    $languages = [];

    // Add source language to the list.
    $source_language = $this->getSourceLanguage();
    $languages = [$source_language->getId() => $source_language] + $languages;

    // Add original language to the list if configured to always be included.
    $original = $this->entity->getUntranslated()->language();
    $config = \Drupal::config('translation_form.settings');
    if (!empty($config->get('always_display_original_language_translation'))) {
      $languages = [$original->getId() => $original] + $languages;
    }

    return $languages;
  }

  /**
   * Check if specified language is a translation target language.
   *
   * @param string $langcode
   *   Langcode to be checked.
   *
   * @return bool
   *   Checking result.
   */
  protected function isTargetLanguage($langcode) {
    $target_language_id = $this->getTargetLanguage()->getId();
    return $langcode === $target_language_id;
  }

  /**
   * Get target language object.
   *
   * @return \Drupal\Core\Language\LanguageInterface|mixed|null
   *   Target language object.
   */
  protected function getTargetLanguage() {
    if ($target = $this->routeMatch->getParameter('target')) {
      return $target;
    }
    return $this->languageManager->getCurrentLanguage();
  }

  /**
   * Get source language object.
   *
   * @return \Drupal\Core\Language\LanguageInterface|mixed|null
   *   Source language object.
   */
  public function getSourceLanguage() {
    if ($source = $this->routeMatch->getParameter('source')) {
      return $source;
    }

    if ($source_langcode = \Drupal::request()->query->get('language_source')) {
      return $this->languageManager->getLanguage($source_langcode);
    }
    $manager = \Drupal::service('content_translation.manager');
    $source_langcode = $manager->getTranslationMetadata($this->entity)->getSource();
    return $this->languageManager->getLanguage($source_langcode);
  }

  /**
   * Get current language name.
   *
   * @return string
   *   Current language name.
   */
  public function getCurrentLanguageName() {
    return $this->getTargetLanguage()->getName();
  }

  /**
   * Get node's translation.
   *
   * @param string $langcode
   *   Language ID.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   Translation object or NULL if translation doesn't exist.
   */
  public function getTranslation($langcode) {
    return $this->entity->hasTranslation($langcode)
      ? $this->entity->getTranslation($langcode)
      : NULL;
  }

  /**
   * Get field names of the field widgets we need to alter.
   *
   * @param \Drupal\Core\Entity\Entity\EntityFormDisplay $display
   *   Entity display.
   *
   * @return array
   *   Array of field names.
   */
  public function getFieldNames(EntityFormDisplay $display) {
    $components = $display->getComponents();
    return !empty($components) ? array_keys($components) : [];
  }

  /**
   * Get fields' translations.
   *
   * @param array $fields
   *   Array of field names.
   * @param \Drupal\Core\Entity\ContentEntityInterface $translation
   *   Translation version of the node.
   * @param bool $rendered
   *   Optional. Render fields or not. Defaults to TRUE.
   *
   * @return array
   *   Fields' translations array.
   *
   * @throws \Exception
   */
  public function getFieldsTranslations(array $fields, ContentEntityInterface $translation, $rendered = TRUE) {
    $translations = [];
    if (empty($fields)) {
      return $translations;
    }
    foreach ($fields as $field_name) {
      if (!$translation->hasField($field_name)) {
        continue;
      }
      $field = $translation->get($field_name);

      // Always display translatable fields/elements in translation table.
      if ($field->getFieldDefinition()->isTranslatable() && $field->isEmpty()) {
        $translations[$field_name] = NULL;
        if ($field->getFieldDefinition()->getType() === 'text_with_summary') {
          $translations[$field_name . '_summary'] = NULL;
        }
        continue;
      }

      if ($field->isEmpty() || !$field->getFieldDefinition()->isTranslatable()) {
        continue;
      }
      $translations[$field_name] = $field->view(['type' => 'default_formatter']);
      if (empty($translations[$field_name])) {
        unset($translations[$field_name]);
        continue;
      }
      $translations[$field_name]['#label_display'] = 'hidden';
      if ($field->getFieldDefinition()->getType() === 'boolean') {
        $items = $translations[$field_name]['#items']->getValue();
        foreach (Element::children($translations[$field_name]) as $child) {
          $translations[$field_name][$child] = [
            '#type'       => 'checkbox',
            '#checked'    => $items[$child]['value'] === '1',
            '#attributes' => ['disabled' => 'disabled'],
          ];
        }
      }
      elseif ($field->getFieldDefinition()->getType() === 'text_with_summary') {
        if ($summary = $field->getValue()[0]['summary']) {
          $body = $translations[$field_name];
          $translations[$field_name] = [
            'body'    => $body,
            'summary' => [
              '#type'   => 'markup',
              '#markup' => '<p>' . $summary . '</p>',
            ],
          ];
        }
      }
      elseif ($field->getFieldDefinition()->getType() === 'image') {
        $settings = $field->getFieldDefinition()
          ->getThirdPartySetting('content_translation', 'translation_sync');

        if (!empty($settings)) {
          $file = $alt = $title = FALSE;
          if ($settings['file'] != '0') {
            $file = $alt = $title = TRUE;
          }
          else {
            $alt   = (bool) $settings['alt'];
            $title = (bool) $settings['title'];
          }

          // Prevent processing alt/title fields if they are not enabled.
          if (!$field->getSetting('alt_field')) {
            $alt = FALSE;
          }
          if (!$field->getSetting('title_field')) {
            $title = FALSE;
          }

          $items = $translations[$field_name]['#items']->getValue();
          foreach (Element::children($translations[$field_name]) as $child) {
            if (!$file) {
              // Add class.
              $translations[$field_name]['#attributes']['class'][] = 'untranslatable-image-file';
            }
            $translations[$field_name][$child]['#prefix'] = '';
            if ($alt) {
              $translations[$field_name][$child]['#prefix'] .= $this->themeManager
                ->render(
                  'translation_form_content_alt_text_preview',
                  ['alt' => $items[$child]['alt']]
                );
            }
            if ($title) {
              $translations[$field_name][$child]['#prefix'] .= $this->themeManager
                ->render(
                  'translation_form_content_title_text_preview',
                  ['title' => $items[$child]['title']]
                );
            }
          }
        }
      }
      if ($rendered) {
        if (isset($translations[$field_name]) && isset($translations[$field_name]['summary'])) {
          $translations[$field_name . '_summary'] = $this->renderer
            ->renderRoot($translations[$field_name]['summary']);
          unset($translations[$field_name]['summary']);
        }
        $translations[$field_name] = $this->renderer
          ->renderRoot($translations[$field_name]);
      }
    }
    return $translations;
  }

  /**
   * Prepare tables for field previews.
   *
   * @param array &$previews
   *   Previews data array.
   */
  public function prepareTables(array &$previews) {
    static $table = ['#type' => 'table'];
    $translations_previews = [];
    foreach ($previews as $language => $field_values) {
      foreach ($field_values as $field_name => $field_value) {
        if (!isset($translations_previews[$field_name])) {
          $translations_previews[$field_name] = $table;
        }
        if ($this->entity->getUntranslated()->language()->getName() === $language) {
          $language = $this->t(
            '<strong>@language_name (Original language)</strong>',
            ['@language_name' => $language]
          );
        }
        // Always display translatable fields/elements in translation table.
        $config = \Drupal::config('translation_form.settings');
        if (is_null($field_value) && !empty($config->get('hide_languages_without_translation'))) {
          $translations_previews[$field_name]['#rows'] = [[]];
          continue;
        }
        $translations_previews[$field_name]['#rows'][] = [$field_value, $language];
      }
    }

    foreach ($translations_previews as &$table) {
      $table = $this->renderer->renderRoot($table);
    }
    $previews['tables'] = $translations_previews;
    $language_toggle = $this->toggleLanguageLink();
    $previews['language_toggle'] = $this->renderer->renderRoot($language_toggle);
  }

  /**
   * Form submission handler for ContentTranslationHandler::entityFormAlter().
   *
   * Takes care of the source language change.
   */
  public function entityFormSourceChange($form, FormStateInterface $form_state) {
    $form_object = $form_state->getFormObject();
    $entity = $form_object->getEntity();
    $source = $form_state->getValue(['source_langcode', 'source']);

    $entity_type_id = $entity->getEntityTypeId();

    // Get edit_url.
    $language = $entity->language();
    $options = ['language' => $language->getId()];
    $edit_translate_url = $entity->toUrl('drupal:content-translation-edit', $options)
      ->setRouteParameter('language', $language->getId());

    // We have different urls for translations.
    if ($this->routeMatch->getRouteName() == $entity->urlInfo('edit-form')->getRouteName()) {
      $form_state->setRedirect($entity->urlInfo('edit-form')->getRouteName(), [
        $entity_type_id => $entity->id(),
        'language_source' => $source,
      ]);
    }
    elseif ($this->routeMatch->getRouteName() == $edit_translate_url->getRouteName()) {
      $form_state->setRedirect($edit_translate_url->getRouteName(), [
        $entity_type_id => $entity->id(),
        'language' => $language->getId(),
        'language_source' => $source,
      ]);
    }

    // Show message about language change.
    $old_language = $this->getSourceLanguage();
    $languages = $this->languageManager->getLanguages();
    $this->messenger->addWarning(
      t('This translation was using %old_language as source language, but when saving you will change the source language of this translation to %new_language.',
        [
          '%old_language' => $old_language->getName(),
          '%new_language' => $languages[$source]->getName(),
        ]
      ));
  }

  /**
   * Entity builder method.
   *
   * @param string $entity_type
   *   The type of the entity.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity whose form is being built.
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   FormState.
   *
   * @see \Drupal\content_translation\ContentTranslationHandler::entityFormAlter()
   */
  public function entityFormEntityBuild($entity_type, EntityInterface $entity, array $form, FormStateInterface $form_state) {
    $metadata = $this->manager->getTranslationMetadata($entity);

    $source_langcode = $this->getSourceLangcode($form_state);
    if ($source_langcode) {
      $metadata->setSource($source_langcode);
    }
  }

  /**
   * Retrieves the source language for the translation being created.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return string
   *   The source language code.
   */
  public function getSourceLangcode(FormStateInterface $form_state) {
    if ($source = $form_state->getValue(['source_langcode', 'source'])) {
      return $source;
    }
    return FALSE;
  }

  /**
   * Returns the languages the data is translated to.
   *
   * @return \Drupal\Core\Language\LanguageInterface[]
   *   An associative array of language objects, keyed by language codes.
   */
  public function getTranslationLanguages() {
    $translations = $this->entity->getTranslationLanguages();
    unset($translations[$this->entity->language()->getId()]);
    $entity_langcode = $this->entity->language()->getId();

    // Remove language from translations if its source is equal to entity
    // language.
    $this->removeRecursiveTranslations($translations, $entity_langcode);

    return $translations;
  }

  /**
   * Get languages sources accordance for entity.
   *
   * @param array $translations
   *   Available translations.
   *
   * @return array
   *   List of language sources which will be used for recursive remove.
   */
  private function getLanguagesSources(array $translations) {
    $manager = \Drupal::service('content_translation.manager');
    $languages_sources = [];
    foreach ($translations as $key => $value) {
      $translation = $this->entity->getTranslation($key);
      $languages_sources[$translation->language()->getId()] = [
        // We use this flag in recursive check.
        'checked' => FALSE,
        'source' => $manager->getTranslationMetadata($translation)->getSource(),
        'available' => TRUE,
      ];
    }

    return $languages_sources;
  }

  /**
   * Remove recursion translations.
   *
   * @param array $translations
   *   Array of translations which are available for entity.
   * @param string $entity_langcode
   *   Entity's langcode.
   */
  private function removeRecursiveTranslations(array &$translations, $entity_langcode) {
    $languages_sources = $stack_languages = $this->getLanguagesSources($translations);
    foreach ($languages_sources as $key => $languages_source) {
      $this->removeRecursiveTranslation($key, $languages_sources, $entity_langcode);
    }

    // Remove unavailable translations.
    foreach ($translations as $key => $value) {
      if ($languages_sources[$key]['available'] === FALSE) {
        unset($translations[$key]);
      }
    }
  }

  /**
   * Set availability of translation for specific source.
   *
   * @param string $lang_code
   *   Language code which is chedked now.
   * @param array $languages_sources
   *   List of available language sources.
   * @param string $entity_langcode
   *   Entity langcode.
   */
  private function removeRecursiveTranslation($lang_code, array &$languages_sources, $entity_langcode) {
    if ($lang_code == LanguageInterface::LANGCODE_NOT_SPECIFIED || $languages_sources[$lang_code]['checked']) {
      return;
    }

    // Check if source language is the same as entity language. If so language
    // shouldn't be available.
    if ($languages_sources[$lang_code]['source'] == $entity_langcode) {
      $languages_sources[$lang_code]['available'] = FALSE;
      $languages_sources[$lang_code]['checked'] = TRUE;
      return;
    }

    $languages_sources[$lang_code]['checked'] = TRUE;
    $this->removeRecursiveTranslation($languages_sources[$lang_code]['source'], $languages_sources, $entity_langcode);

    // Check if source language is available. If no, current language isn't
    // available too.
    if ($languages_sources[$lang_code]['source'] !== LanguageInterface::LANGCODE_NOT_SPECIFIED && !$languages_sources[$languages_sources[$lang_code]['source']]['available']) {
      $languages_sources[$lang_code]['available'] = FALSE;
    }
  }

  /**
   * Get toggle link for table.
   *
   * @return array
   *   Render array with toggle link.
   */
  private function toggleLanguageLink() {
    return [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => '',
      '#attributes' => ['class' => ['translation-form-language-toggle']],
    ];
  }

}
