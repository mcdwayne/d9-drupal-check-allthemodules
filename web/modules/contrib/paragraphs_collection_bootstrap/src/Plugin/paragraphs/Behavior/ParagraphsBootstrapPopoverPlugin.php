<?php

namespace Drupal\paragraphs_collection_bootstrap\Plugin\paragraphs\Behavior;

use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a way to use Bootstrap popover.
 *
 * @ParagraphsBehavior(
 *   id = "pcb_popover",
 *   label = @Translation("Bootstrap popover"),
 *   description = @Translation("Displays paragraphs in bootstrap popover."),
 *   weight = 100
 * )
 */
class ParagraphsBootstrapPopoverPlugin extends ParagraphsBehaviorBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * ParagraphsBootstrapCarouselPlugin constructor.
   *
   * @param array $configuration
   *   The configuration array.
   * @param string $plugin_id
   *   This plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   Entity field manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery
   *   Library discovery service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityFieldManager $entity_field_manager, EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer, LibraryDiscoveryInterface $library_discovery) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_field_manager);

    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
    $this->libraryDiscovery = $library_discovery;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $container->get('library.discovery')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'animation' => TRUE,
      'container' => '',
      'popover_content' => '',
      'delay' => 0,
      'placement' => 'right',
      'trigger' => ['click'],
      'offset' => '0 0',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $form['animation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Animation'),
      '#description' => $this->t('Apples a CSS fade transition to the popover.'),
      '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), 'animation', $this->configuration['animation']),
    ];

    $form['container'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Container'),
      '#description' => $this->t("Appends the popover to a specific element. Example: container: 'body'. This option is particularly useful in that it allows you to position the popover in the flow of the document near the triggering element - which will prevent the popover from floating away from the triggering element during a window resize."),
      '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), 'container', $this->configuration['container']),
      '#size' => 32,
    ];

    $paragraphs = $this->getParagraphsOnEntity($form_state->getFormObject()
      ->getEntity(), [$paragraph->id()], TRUE);
    $options = [];
    foreach ($paragraphs as $id => $paragraph_option) {
      // @TODO improve how we display paragraphs.
      $options[$id] = $paragraph_option->getParagraphType()
          ->label() . ' (' . $id . ')';
    }

    $form['popover_content'] = [
      '#type' => 'select',
      '#title' => $this->t('Popover content'),
      '#description' => $this->t("A paragraph to be used as popover content. If you don't see the paragraph you want to reference try saving first."),
      '#options' => $options,
      '#empty_value' => '',
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), 'popover_content', $this->configuration['popover_content']),
    ];

    $form['delay'] = [
      '#type' => 'number',
      '#title' => $this->t('Delay'),
      '#description' => $this->t('Delay showing and hiding the popover (ms) - does not apply to manual trigger type.'),
      '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), 'delay', $this->configuration['delay']),
      '#min' => 0,
    ];

    $form['placement'] = [
      '#type' => 'select',
      '#title' => $this->t('Placement'),
      '#description' => $this->t('The placement of the popup.'),
      '#options' => [
        'top' => $this->t('Top'),
        'left' => $this->t('Left'),
        'bottom' => $this->t('Bottom'),
        'right' => $this->t('Right'),
      ],
      '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), 'placement', $this->configuration['placement']),
    ];

    $form['trigger'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => $this->t('Focus trigger'),
      '#description' => $this->t('Chose what trigger to use for the popup to appear. You may choose multiple triggers, "manual" cannot be combined with any other trigger.'),
      '#options' => [
        'click' => $this->t('Click'),
        'hover' => $this->t('Hover'),
        'focus' => $this->t('Focus'),
        'manual' => $this->t('Manual'),
      ],
      '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), 'trigger', $this->configuration['trigger']),
    ];
    // @TODO: There is an option for constraints, but is currently not available.

    $form['offset'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Offset'),
      '#description' => $this->t("Offset of the popover relative to its target. For more information refer to Tether's @offset_docs.", [
        '@offset_docs' => Link::fromTextAndUrl('constraint docs', Url::fromUri('http://tether.io/#offset'))
          ->toString(),
      ]),
      '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), 'offset', $this->configuration['offset']),
      '#size' => 32,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    if (in_array('manual', $form_state->getValue('trigger')) && count($form_state->getValue('trigger')) > 1) {
      $form_state->setError($form['trigger'], '"Manual" cannot be combined with any other trigger in behavior settings.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    $popup_content_paragraph_id = $paragraph->getBehaviorSetting($this->getPluginId(), 'popover_content');
    if (!$popup_content_paragraph_id) {
      return;
    }

    $popup_content_paragraph = $this->entityTypeManager->getStorage('paragraph')
      ->load($popup_content_paragraph_id);
    if (!$popup_content_paragraph) {
      return;
    }

    $view_builder = $this->entityTypeManager->getViewBuilder($popup_content_paragraph->getEntityTypeId());
    $paragraph_render_array = $view_builder->view($popup_content_paragraph);
    $paragraph_build = $view_builder->build($paragraph_render_array);

    $build['#attributes'] = [
      'data-toggle' => 'popover',
      'data-animation' => $paragraph->getBehaviorSetting($this->getPluginId(), 'animation') ? 'true' : 'false',
      'data-container' => $paragraph->getBehaviorSetting($this->getPluginId(), 'container') ?: 'false',
      'data-content' => $this->renderer->render($paragraph_build)->__toString(),
      'data-delay' => $paragraph->getBehaviorSetting($this->getPluginId(), 'delay'),
      'data-html' => 'true',
      'data-placement' => $paragraph->getBehaviorSetting($this->getPluginId(), 'placement'),
      // @TODO discuss how to handle title.
      'title' => '',
      'data-trigger' => implode(' ', $paragraph->getBehaviorSetting($this->getPluginId(), 'trigger')),
      // @TODO There is an option for constraints, but is currently not available.
      'data-offset' => $paragraph->getBehaviorSetting($this->getPluginId(), 'offset'),
    ];

    $build['#attached']['library'][] = 'bs_lib/popover';
    $build['#attached']['library'][] = 'paragraphs_collection_bootstrap/popover';
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(Paragraph $paragraph) {
    $summary = [];

    if ($paragraph->getBehaviorSetting($this->getPluginId(), 'animation')) {
      $summary[] = $this->t('Animation: enabled');
    }
    else {
      $summary[] = $this->t('Animation: disabled');
    }

    if ($container = $paragraph->getBehaviorSetting($this->getPluginId(), 'container')) {
      $summary[] = $this->t('Container: @container', [
        '@container' => $container,
      ]);
    }
    else {
      $summary[] = $this->t('Container: none');
    }

    if ($popup_content_paragraph_id = $paragraph->getBehaviorSetting($this->getPluginId(), 'popover_content')) {
      $popup_content_paragraph = $this->entityTypeManager->getStorage('paragraph')
        ->load($popup_content_paragraph_id);
      $summary[] = $this->t('Popup content: @paragraph_type (@id)', [
        '@paragraph_type' => $popup_content_paragraph->getParagraphType()
          ->label(),
        '@id' => $popup_content_paragraph->id(),
      ]);
    }

    if ($delay = $paragraph->getBehaviorSetting($this->getPluginId(), 'delay')) {
      $summary[] = $this->t('Delay: @delay ms', [
        '@delay' => $delay,
      ]);
    }

    if ($placement = $paragraph->getBehaviorSetting($this->getPluginId(), 'placement')) {
      $summary[] = $this->t('Placement: @placement', [
        '@placement' => ucfirst($placement),
      ]);
    }

    if ($trigger = $paragraph->getBehaviorSetting($this->getPluginId(), 'trigger')) {
      $summary[] = $this->t('Trigger: @trigger', [
        '@trigger' => ucfirst($trigger),
      ]);
    }

    if ($offset = $paragraph->getBehaviorSetting($this->getPluginId(), 'offset')) {
      $summary[] = $this->t('Offset: @offset', [
        '@offset' => $offset,
      ]);
    }

    return $summary;
  }

  /**
   * Returns all referenced paragraphs from an entity if available.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to get paragraphs from.
   * @param array $excluded_paragraph_ids
   *   An array of paragraph ids to be excluded.
   * @param bool $exclude_parent
   *   Sets if we should also exclude parents from the excluded paragraphs.
   *
   * @return ParagraphInterface[]
   *   An array of paragraphs keyed by ID.
   */
  protected function getParagraphsOnEntity(ContentEntityInterface $entity, array $excluded_paragraph_ids = [], $exclude_parent = FALSE) {
    $definitions = $this->entityFieldManager
      ->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle());

    if ($exclude_parent) {
      foreach ($excluded_paragraph_ids as $excluded_paragraph_id) {
        $excluded_paragraph_ids = array_merge($excluded_paragraph_ids, $this->getParents($excluded_paragraph_id));
      }
    }

    $paragraphs = [];
    foreach ($definitions as $definition) {
      if ($definition instanceof FieldConfigInterface && $definition->getType() == 'entity_reference_revisions' && $definition->getSetting('target_type') == 'paragraph') {
        foreach ($entity->{$definition->getName()}->referencedEntities() as $referencedEntity) {
          if (!in_array($referencedEntity->id(), $excluded_paragraph_ids)) {
            $paragraphs[$referencedEntity->id()] = $referencedEntity;
            $paragraphs = array_replace($paragraphs, $this->getParagraphsOnEntity($referencedEntity, $excluded_paragraph_ids, $exclude_parent));
          }
        }
      }
    }

    return $paragraphs;
  }

  /**
   * Returns parent IDs.
   *
   * @param mixed $paragraph_id
   *   The paragraph id to get the container parents.
   *
   * @return array
   *   Returns an array of parent IDs.
   */
  protected function getParents($paragraph_id) {
    $excluded_paragraph = $this->entityTypeManager->getStorage('paragraph')
      ->load($paragraph_id);
    $parents_to_exclude = [];
    if ($excluded_paragraph->parent_type->value == 'paragraph') {
      $parents_to_exclude[$excluded_paragraph->parent_id->value] = $excluded_paragraph->parent_id->value;
      $parents_to_exclude = array_replace($parents_to_exclude, $this->getParents($excluded_paragraph->parent_id->value));
    }

    return $parents_to_exclude;
  }

}
