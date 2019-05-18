<?php

namespace Drupal\entity_quicklook\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Plugin implementation of the 'entity quicklook' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_quicklook_formatter",
 *   label = @Translation("Entity Quicklook"),
 *   description = @Translation("Format entity reference fields as a specialized link."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityQuicklookFormatter extends EntityReferenceFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  protected $renderer;
  protected $entityTypeManager;
  protected $languageManager;
  /**
   * Constructs a EntityReferenceEntityFormatter instance.
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
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityDisplayRepositoryInterface $entity_display_repository, Renderer $renderer, EntityTypeManagerInterface $entityTypeManager, LanguageManager $languageManager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->entityDisplayRepository = $entity_display_repository;
    $this->renderer = $renderer;
    $this->entityTypeManager = $entityTypeManager;
    $this->languageManager = $languageManager;
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
      $container->get('entity_display.repository'),
      $container->get('renderer'),
      $container->get('entity_type.manager'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'view_mode' => 'default',
      'link_text' => 'Quicklook',
      'link_view_mode' => '',
      'modal_title' => 'Entity Quicklook Popup',
      'custom_modal_title' => FALSE,
      'custom_link_text' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['view_mode'] = [
      '#type' => 'select',
      '#options' => $this->entityDisplayRepository->getViewModeOptions($this->getFieldSetting('target_type')),
      '#title' => $this->t('View mode'),
      '#default_value' => $this->getSetting('view_mode'),
      '#required' => TRUE,
    ];

    // Remove the current view mode from the options, otherwise we'll get
    // infinite recursion.
    $link_view_mode_options = $this->entityDisplayRepository->getViewModeOptions($this->fieldDefinition->getTargetEntityTypeId());
    unset($link_view_mode_options[$this->settings['view_mode']]);
    $elements['link_view_mode'] = [
      '#title' => $this->t('Link view mode'),
      '#description' => $this->t('Optionally render an entity view mode as the link'),
      '#type' => 'select',
      '#empty_value' => '',
      '#options' => $link_view_mode_options,
      '#default_value' => $this->getSetting('link_view_mode'),
    ];

    $elements['custom_link_text'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Custom link text'),
      '#description' => $this->t('When left unchecked will use the entity name.'),
      '#default_value' => $this->getSetting('custom_link_text'),
    ];
    $elements['link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link text'),
      '#default_value' => $this->getSetting('link_text'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];
    $elements['custom_modal_title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Custom modal title'),
      '#description' => $this->t('When left unchecked will use the entity title.'),
      '#default_value' => $this->getSetting('custom_modal_title'),
    ];
    $elements['modal_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title for Quicklook Popup'),
      '#default_value' => $this->getSetting('modal_title'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $view_modes = $this->entityDisplayRepository->getViewModeOptions($this->getFieldSetting('target_type'));
    $view_mode = $this->getSetting('view_mode');
    $summary[] = $this->t('Rendered as @mode', ['@mode' => isset($view_modes[$view_mode]) ? $view_modes[$view_mode] : $view_mode]);

    if ($this->getSetting('link_view_mode')) {
      $summary[] = $this->t('Link rendered as @mode', ['@mode' => $this->getSetting('link_view_mode')]);
    }

    $summary[] = $this->t('Custom link text: @custom', ['@custom' => $this->getSetting('custom_link_text') ? 'Yes' : 'No']);

    if ($this->getSetting('custom_link_text')) {
      $summary[] = $this->t('Quicklook link text: %text', [
        '%text' => $this->getSetting('link_text'),
      ]);
    }

    $summary[] = $this->t('Custom modal title: @custom', ['@custom' => $this->getSetting('custom_modal_title') ? 'Yes' : 'No']);

    if ($this->getSetting('custom_modal_title')) {
      $summary[] = $this->t('Quicklook popup title: %popup_title', [
        '%popup_title' => $this->getSetting('modal_title'),
      ]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Need to determine what view mode to use when rendering the referenced
    // entity in the Quicklook modal.
    $link_text = FALSE;
    $view_mode = $this->getSetting('view_mode');
    $link_view_mode = $this->getSetting('link_view_mode');
    if ($this->getSetting('link_text') && $this->getSetting('custom_link_text')) {
      $link_text = $this->getSetting('link_text');
    }
    $elements = [];

    // Get the entity to which the Quicklook entity reference field belongs so
    // that it can be used as a parameter in the Quicklook link's route.
    $parent_entity = $items->getEntity();

    // There are two possibilities: if the entity reference field is empty then
    // the entity rendered in the Quicklook modal will be the entity to which
    // the reference field belongs, otherwise the entity rendered will be the
    // entity that is being referenced.
    $reference_self = $this->getEntitiesToView($items, $langcode) != NULL ? FALSE : TRUE;
    if ($reference_self) {
      // In this case the entity to be rendered in the Quicklook modal is the
      // entity to which the reference field belongs.
      $entity = $parent_entity;
      if (!$link_text) {
        $link_text = $entity->label();
      }
      $elements[0] = $this->buildQuicklookPopup($entity, $parent_entity, $link_text, $view_mode, $link_view_mode);
    }
    else {
      // In this case the entity to be rendered in the Quicklook modal is the
      // entity being referenced in the entity reference field.
      foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
        if (!$link_text) {
          $link_text = $entity->label();
        }
        $elements[0] = $this->buildQuicklookPopup($entity, $parent_entity, $link_text, $view_mode, $link_view_mode);
      }
    }

    return $elements;

  }

  /**
   * Build the quicklook link and popup.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity_referenced
   *   The entity to be rendered in the quicklook popup.
   * @param \Drupal\Core\Entity\EntityInterface $parent_entity
   *   The entity to be rendered in the quicklook popup.
   * @param string $link_text
   *   The text for the Quicklook link.
   * @param string $view_mode
   *   The view mode to be used for rendering the entity in the quicklook popup.
   * @param string $link_view_mode
   *   View mode for the link, to optionally render the entity in place.
   *
   * @return array
   *   The quicklook modal render array.
   */
  public function buildQuicklookPopup(EntityInterface $entity_referenced, EntityInterface $parent_entity, $link_text, $view_mode = 'default', $link_view_mode = '') {
    $entity_type = $entity_referenced->getEntityTypeId();
    $id = $entity_referenced->id();

    $classes = ['entity-quicklook-popup', $entity_type . '-' . $id . '-quicklook-popup'];
    $title = $this->getSetting('custom_modal_title') ? $this->getSetting('modal_title') : $entity_referenced->label();
    $dialog_widget_options = [
      'dialogClass' => implode(' ', $classes),
      'title' => $title,
    ];
    // Set the route for the Quicklook modal to our controller which will handle
    // rendering the entity.
    $route = 'entity_quicklook_formatter.render_popup';
    $parameters = [
      'parent_entity_type' => $parent_entity->getEntityTypeId(),
      'parent_entity' => $parent_entity->id(),
      'from_view' => $this->viewMode,
      'entity_type' => $entity_type,
      'entity' => $id,
      'view_mode' => $view_mode,
    ];
    // HTML attributes that will be added to the anchor tag.
    $options = [
      'attributes' => [
        // Set class to "use-ajax" in order to trigger an Ajax response.
        'class' => 'use-ajax quicklook',
        'data-dialog-type' => 'modal',
        'data-dialog-options' => json_encode($dialog_widget_options),
      ],
    ];

    if (!$link_view_mode) {
      $link = Link::createFromRoute($link_text, $route, $parameters, $options);
      $element = $link->toRenderable();
      $element['#attached'] = [
        'library' => [
          'core/drupal.ajax',
          'core/jquery.ui.dialog',
          'entity_quicklook/entity-quicklook-link',
        ],
      ];
    }

    else {
      $link = new Url($route, $parameters, $options);
      $viewBuilder = $this->entityTypeManager->getViewBuilder($parent_entity->getEntityTypeId());
      $langcode = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
      $markup = $viewBuilder->view($parent_entity, $link_view_mode, $langcode);
      $html = $this->renderer->renderPlain($markup);
      // Sanitize out nested anchors.
      $html = str_replace(['<a', '</a'], ['<span', '</span'], $html);
      $element = [
        '#attached' => [
          'library' => [
            'core/drupal.ajax',
            'core/jquery.ui.dialog',
            'entity_quicklook/entity-quicklook-link',
          ],
        ],
      ];
      $element['link'] = [
        '#prefix' => '<a href="' . $link->toString() . '" class="use-ajax quicklook" data-dialog-type="modal" data-dialog-options="' . json_encode($dialog_widget_options) . '">',
        '#markup' => $html,
        '#suffix' => '</a>',
      ];
    }

    return $element;
  }

}
