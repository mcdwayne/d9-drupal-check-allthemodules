<?php

namespace Drupal\mfd\Plugin\Field\FieldFormatter;

use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\content_translation\ContentTranslationManagerInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'multilingual_form_display_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "multilingual_form_display_formatter",
 *   label = @Translation("Multilingual Form Display Formatter"),
 *   field_types = {
 *     "multilingual_form_display"
 *   }
 * )
 */
class MultilingualFormDisplayFormatter extends FormatterBase implements ContainerFactoryPluginInterface {


  /**
   * State indicating all collapsible fields are removed.
   */
  const COLLAPSIBLE_STATE_NONE = -1;

  /**
   * State indicating all collapsible fields are closed.
   */
  const COLLAPSIBLE_STATE_ALL_CLOSED = 0;

  /**
   * State indicating all collapsible fields are closed except the first one.
   */
  const COLLAPSIBLE_STATE_FIRST = 1;

  /**
   * State indicating all collapsible fields are open.
   */
  const COLLAPSIBLE_STATE_ALL_OPEN = 2;

  /**
   * The content translation manager.
   *
   * @var \Drupal\content_translation\ContentTranslationManagerInterface
   */
  protected $manager;

  /**
   * The content translation manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The content translation manager.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a StringFormatter instance.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager, ContentTranslationManagerInterface $manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->account = $this->container()->get('current_user');
    $this->entityManager = $entity_manager;
    $this->languageManager = $language_manager;
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('content_translation.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    if ($this->getSetting('display_translate_table') && $this->account->hasPermission('show multilingual translate table')) {
      $entity = $items->getEntity();
      $entity_type = $entity->getEntityTypeId();

//      TODO: It might be nice to have this as a BLOCK as opposed to a field.

      // Create a container for the entity's fields.
      $collapsible_state = $this->getSetting('collapsible_state');
      if ($collapsible_state == self::COLLAPSIBLE_STATE_NONE) {
        $elements = [
          '#type' => 'item',
        ];
      } else {

        $elements = [
          '#type' => 'details',
          '#open' => ($collapsible_state == self::COLLAPSIBLE_STATE_FIRST) || ($collapsible_state == self::COLLAPSIBLE_STATE_ALL_OPEN) ? TRUE : FALSE,
        ];
      }

      $elements[0]= $this->overviewTranslationTable($entity, $entity_type);
    }

    return $elements;
  }

  public static function defaultSettings() {
    return [
        'display_label' => TRUE,
        'display_description' => TRUE,
        'collapsible_state' => self::COLLAPSIBLE_STATE_FIRST,
        'display_translate_table' => FALSE,
      ] + parent::defaultSettings();

  }
  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $field_definition = $this->fieldDefinition;
    $values = $form_state->getValues();
    $fields = \Drupal::service('entity_field.manager')->getFieldMapByFieldType('multilingual_form_display');

    $collapsible_state_options = [
      self::COLLAPSIBLE_STATE_NONE => $this->t('Not collapsible -- all visible'),
      self::COLLAPSIBLE_STATE_ALL_CLOSED => $this->t('Collapsible and all closed'),
      self::COLLAPSIBLE_STATE_FIRST => $this->t('Collapsible with first language open'),
      self::COLLAPSIBLE_STATE_ALL_OPEN => $this->t('Collapsible and all open'),
    ];

    $form['collapsible_state'] = [
      '#title' => $this->t('Choose whether the languages will be displayed in a collapsible field or not.'),
      '#type' => 'select',
      '#options' => $collapsible_state_options,
      '#default_value' => $this->getSetting('collapsible_state'),
    ];

    $form['display_translate_table'] = [
      '#title' => $this->t('Display the translate table'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('display_translate_table'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $collapsible_state = $this->getSetting('collapsible_state');

    if ($this->getSetting('display_translate_table')) {
      $summary[] = $this->t('Displaying the translation table');
    }

    switch ($collapsible_state) {
      case self::COLLAPSIBLE_STATE_NONE:
        $summary[] = $this->t('This field will be open and non-collapsible.');
        break;

      case self::COLLAPSIBLE_STATE_ALL_CLOSED:
        $summary[] = $this->t('This field will be collapsed by default.');
        break;

      case self::COLLAPSIBLE_STATE_FIRST:
        $summary[] = $this->t('This field will have the first language open and the others collapsed.');
        break;

      case self::COLLAPSIBLE_STATE_ALL_OPEN:
        $summary[] = $this->t('This field will have all languages open and collapsible.');
        break;

    }

    return $summary;
  }

  /**
   * We've lifted most of this code from the ContentTranslationController class.
   * We've done a few modifications to remove some cruft and add some rendering.
   * *
   *
   * Builds the translations overview page.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   En entity to work with
   * @param string $entity_type_id
   *   (optional) The entity type ID.
   * @return array
   *   Array of page elements to render.
   */
  public function overviewTranslationTable(ContentEntityInterface $entity, $entity_type_id = NULL) {

    $manager = $this->manager;
    $handler = $this->entityManager->getHandler($entity_type_id, 'translation');
    $entity_type = $entity->getEntityType();

    // Start collecting the cacheability metadata, starting with the entity and
    // later merge in the access result cacheability metadata.
    $cacheability = CacheableMetadata::createFromObject($entity);

    $languages = $this->languageManager->getLanguages();
    $original = $entity->getUntranslated()->language()->getId();
    $translations = $entity->getTranslationLanguages();

    $field_ui = \Drupal\Core\Extension\ModuleHandler::moduleExists('field_ui') && $this->account->hasPermission('administer ' . $entity_type_id . ' fields');

    $rows = [];
    $show_source_column = FALSE;

    if ($this->languageManager->isMultilingual()) {
      // Determine whether the current entity is translatable.
      $translatable = FALSE;
      foreach ($this->entityManager->getFieldDefinitions($entity_type_id, $entity->bundle()) as $instance) {
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

        $add_url = new Url(
          "entity.$entity_type_id.content_translation_add",
          [
            'source' => $original,
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

          $is_original = $langcode == $original;
          $label = $entity->getTranslation($langcode)->label();
          $link = isset($links->links[$langcode]['url']) ? $links->links[$langcode] : ['url' => $entity->urlInfo()];
          if (!empty($link['url'])) {
            $link['url']->setOption('language', $language);
            $row_title = \Drupal::l($label, $link['url']);
          }

          if (empty($link['url'])) {
            $row_title = $is_original ? $label : $this->t('n/a');
          }

          // If the user is allowed to edit the entity we point the edit link to
          // the entity form, otherwise if we are not dealing with the original
          // language we point the link to the translation form.
          $update_access = $entity->access('update', NULL, TRUE);
          $translation_access = $handler->getTranslationAccess($entity, 'update');
          $cacheability = $cacheability
            ->merge(CacheableMetadata::createFromObject($update_access))
            ->merge(CacheableMetadata::createFromObject($translation_access));
          if ($update_access->isAllowed() && $entity_type->hasLinkTemplate('edit-form')) {
            $links['edit']['url'] = $entity->urlInfo('edit-form');
            $links['edit']['language'] = $language;
          }
          elseif (!$is_original && $translation_access->isAllowed()) {
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

    $build['#caption'] = $this->t('Translations of %label', ['%label' => $entity->label()]);

    $cacheability
      ->addCacheTags($entity->getCacheTags())
      ->applyTo($build);

    $build += [
      '#label_display' => FALSE,
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];

    return $build;
  }

  /**
   * Returns the service container.
   *
   * This method is marked private to prevent sub-classes from retrieving
   * services from the container through it. Instead,
   * \Drupal\Core\DependencyInjection\ContainerInjectionInterface should be used
   * for injecting services.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerInterface
   *   The service container.
   */
  private function container() {
    return \Drupal::getContainer();
  }

}
