<?php

namespace Drupal\paragraphs_collection_bootstrap\Plugin\paragraphs\Behavior;

use Drupal\Component\Utility\Html;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a way to use Bootstrap carousel.
 *
 * @ParagraphsBehavior(
 *   id = "pcb_carousel",
 *   label = @Translation("Bootstrap carousel"),
 *   description = @Translation("Displays paragraphs in bootstrap carousel."),
 *   weight = 100
 * )
 */
class ParagraphsBootstrapCarouselPlugin extends ParagraphsBehaviorBase {

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
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $libraryDiscovery
   *   Library discovery service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityFieldManager $entity_field_manager, EntityTypeManagerInterface $entity_type_manager, LibraryDiscoveryInterface $libraryDiscovery) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_field_manager);

    $this->entityTypeManager = $entity_type_manager;
    $this->libraryDiscovery = $libraryDiscovery;
  }

  /**
   * +   * {@inheritdoc}
   * +   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('library.discovery')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'container_field' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['container_field'] = $form_state->getValue('container_field');
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $paragraphs_type = $form_state->getFormObject()->getEntity();

    if ($paragraphs_type->isNew()) {
      return [];
    }

    $field_options = $this->getFieldNameOptions($paragraphs_type, 'entity_reference_revisions');

    if ($field_options) {
      $form['container_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Carousel field'),
        '#description' => $this->t('Choose the field to be used as carousel items.'),
        '#options' => $field_options,
        '#default_value' => $this->configuration['container_field'],
      ];
    }
    else {
      $form['message'] = [
        '#type' => 'container',
        '#markup' => $this->t('There are no entity reference revisions fields available. Please add at least one in the <a href=":link">Manage fields</a> page.', [
          ':link' => Url::fromRoute("entity.{$paragraphs_type->getEntityType()->getBundleOf()}.field_ui_fields", [$paragraphs_type->getEntityTypeId() => $paragraphs_type->id()])
            ->toString(),
        ]),
        '#attributes' => [
          'class' => ['messages messages--error'],
        ],
      ];

    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getValue('container_field')) {
      $form_state->setErrorByName('message', $this->t('The Bootstrap carousel plugin cannot be enabled if there is no field to be mapped.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $form['controls'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use controls'),
      '#description' => $this->t('Check to use controls on the left and right side.'),
      '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), 'controls'),
    ];

    $form['indicator'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use indicator'),
      '#description' => $this->t('Check to use indicator at the bottom of the slide.'),
      '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), 'indicator'),
    ];

    $form['caption'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show caption'),
      '#description' => $this->t('Check to add captions to your slides.'),
      '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), 'caption', TRUE),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    foreach (Element::children($build) as $container_field_name) {
      if ($container_field_name == $this->configuration['container_field']) {
        $content = [];
        foreach (Element::children($build[$container_field_name]) as $slide) {
          $content['items'][]['slide'] = $build[$container_field_name][$slide];
        }
        $build[$container_field_name] = [
          '#theme' => 'pcb_carousel',
          '#content' => $content,
          '#behavior' => [
            'controls' => $paragraph->getBehaviorSetting($this->getPluginId(), 'controls'),
            'indicator' => $paragraph->getBehaviorSetting($this->getPluginId(), 'indicator'),
            'caption' => $paragraph->getBehaviorSetting($this->getPluginId(), 'caption'),
          ],
          '#attributes' => [
            'id' => [
              Html::getUniqueId('bootstrap-carousel'),
            ],
          ],
          '#attached' => [
            'library' => [
              'bs_lib/carousel',
            ],
          ],
        ];
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(Paragraph $paragraph) {
    $summary = [];

    if ($paragraph->getBehaviorSetting($this->getPluginId(), 'controls')) {
      $summary[] = $this->t('Carousel controls: enabled');
    }
    else {
      $summary[] = $this->t('Carousel controls: disabled');
    }

    if ($paragraph->getBehaviorSetting($this->getPluginId(), 'indicator')) {
      $summary[] = $this->t('Carousel indicator: enabled');
    }
    else {
      $summary[] = $this->t('Carousel indicator: disabled');
    }

    return $summary;
  }

}
