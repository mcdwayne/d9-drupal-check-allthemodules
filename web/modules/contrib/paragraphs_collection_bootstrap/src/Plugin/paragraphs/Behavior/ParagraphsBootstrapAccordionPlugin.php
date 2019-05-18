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
use Drupal\paragraphs\ParagraphsBehaviorBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a way to use Bootstrap accordion.
 *
 * @ParagraphsBehavior(
 *   id = "pcb_accordion",
 *   label = @Translation("Bootstrap accordion"),
 *   description = @Translation("Displays paragraphs in bootstrap accordion."),
 *   weight = 100
 * )
 */
class ParagraphsBootstrapAccordionPlugin extends ParagraphsBehaviorBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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

    $options = $this->getFieldNameOptions($paragraphs_type, 'entity_reference_revisions');

    if ($options) {
      $form['container_field'] = [
        '#type' => 'select',
        '#title' => 'Accordion field',
        '#options' => $options,
        '#description' => $this->t('Choose the field to be used as accordion items.'),
        '#default_value' => $this->configuration['container_field'],
      ];
    }
    else {
      $form['message'] = [
        '#type' => 'container',
        '#markup' => $this->t('There are no entity reference revisions fields available. Please add at least one in the <a href=":link">Manage fields page.</a>', [
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
      $form_state->setErrorByName('message', $this->t('The Bootstrap accordion plugin cannot be enabled if there is no field to be mapped.'));
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
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    foreach (Element::children($build) as $container_field_name) {
      if ($container_field_name == $this->configuration['container_field']) {
        $content = [];
        foreach ($paragraph->$container_field_name->referencedEntities() as $key => $referenced_paragraph) {
          $view_builder = $this->entityTypeManager->getViewBuilder($referenced_paragraph->getEntityTypeId());
          $referenced_paragraph_render_array = $view_builder->view($referenced_paragraph);
          $referenced_paragraph_build = $view_builder->build($referenced_paragraph_render_array);

          $field_build = [];
          foreach (Element::children($referenced_paragraph_build, TRUE) as $field_name) {
            $field_build[] = $referenced_paragraph_build[$field_name];
          }

          // Use first 2 fields for item and caption.
          foreach (['title', 'content'] as $index => $value) {
            if (isset($field_build[$index])) {
              $content[$key][$value] = $field_build[$index];
            }
          }
        }

        $build[$container_field_name] = [
          '#theme' => 'pcb_accordion',
          '#content' => $content,
          '#attributes' => [
            'id' => [
              Html::getUniqueId('bootstrap-accordion'),
            ],
          ],
          '#attached' => [
            'library' => [
              'bs_lib/collapse',
              'bs_lib/bootstrap_css',
            ],
          ],
        ];
      }
    }

  }

}
