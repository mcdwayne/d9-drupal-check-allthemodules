<?php

namespace Drupal\paragraphs_collection_bootstrap\Plugin\paragraphs\Behavior;

use Drupal\Component\Utility\Html;
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
 * Provides a way to use Bootstrap tabs.
 *
 * @ParagraphsBehavior(
 *   id = "pcb_tabs",
 *   label = @Translation("Bootstrap tabs"),
 *   description = @Translation("Displays paragraphs in bootstrap tabs."),
 *   weight = 100
 * )
 */
class ParagraphsBootstrapTabsPlugin extends ParagraphsBehaviorBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * ParagraphsTabsCarouselPlugin constructor.
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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityFieldManager $entity_field_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_field_manager);

    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('entity_type.manager')
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
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $paragraphs_type = $form_state->getFormObject()->getEntity();

    if ($paragraphs_type->isNew()) {
      return [];
    }

    $field_options = $this->getFieldNameOptions($paragraphs_type, 'entity_reference_revisions');

    if ($field_options) {
      $form['container_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Container field'),
        '#description' => $this->t('Choose the field to be used as container for tab items.'),
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
      $form_state->setErrorByName('message', $this->t('The Bootstrap tabs plugin cannot be enabled if there is no field to be mapped.'));
    }
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
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $form['fade'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Fade effect'),
      '#description' => $this->t('Check to enable fade effect for tabs.'),
      '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), 'fade'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
    if ($paragraph->getBehaviorSetting('style', 'style') == '') {
      $form_state->setError($form, 'Tabs styles are required.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    foreach (Element::children($build) as $container_field_name) {
      if ($container_field_name == $this->configuration['container_field']) {
        $content = [];
        foreach ($paragraph->$container_field_name->referencedEntities() as $key => $referenced_paragraph) {
          $view_builder = $this->entityTypeManager->getViewBuilder($referenced_paragraph->getEntityTypeId());
          $referenced_paragraph_render_array = $view_builder->view($referenced_paragraph);
          $referenced_paragraph_build = $view_builder->build($referenced_paragraph_render_array);

          $fields_build = [];
          foreach (Element::children($referenced_paragraph_build, TRUE) as $field_name) {
            $fields_build[] = $referenced_paragraph_build[$field_name];
          }

          // Use first 2 fields for item and caption.
          foreach (['title', 'content'] as $index => $value) {
            if (isset($fields_build[$index])) {
              $content[$key][$value] = $fields_build[$index];
            }
          }
        }

        list($style, $layout) = explode('-', $paragraph->getBehaviorSetting('style', 'style'));

        $build[$container_field_name] = [
          '#theme' => 'pcb_tabs',
          '#content' => $content,
          '#settings' => [
            'style' => $style,
            'fade' => $paragraph->getBehaviorSetting($this->getPluginId(), 'fade'),
            'layout' => $layout == 'default' ? NULL : $layout,
          ],
          '#attributes' => [
            'id' => [Html::getUniqueId('bootstrap-tabs')],
          ],
          '#attached' => [
            'library' => [
              'bs_lib/tab',
              'bs_lib/bootstrap_css',
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
    if ($paragraph->getBehaviorSetting($this->getPluginId(), 'fade')) {
      return [$this->t('Tabs fade: enabled')];
    }

    return [$this->t('Tabs fade: disabled')];
  }

}
