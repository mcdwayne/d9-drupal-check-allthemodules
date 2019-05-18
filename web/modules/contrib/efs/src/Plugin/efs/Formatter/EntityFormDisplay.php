<?php

namespace Drupal\efs\Plugin\efs\Formatter;

use Drupal\Core\Entity\EntityDisplayBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\efs\Entity\ExtraFieldInterface;
use Drupal\efs\ExtraFieldFormatterPluginBase;
use Drupal\field_ui\Form\EntityDisplayFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Details element.
 *
 * @ExtraFieldFormatter(
 *   id = "entity_form_display",
 *   label = @Translation("Entity form display"),
 *   description = @Translation("Entity form display"),
 *   supported_contexts = {
 *     "display"
 *   }
 * )
 */
class EntityFormDisplay extends ExtraFieldFormatterPluginBase {

  /**
   * The entity form builder service.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityFormBuilderInterface $entity_form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityFormBuilder = $entity_form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('efs.entity.form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultContextSettings(string $context) {
    $defaults = [
      'form_display' => 'default',
      'form_display_class' => '',
    ] + parent::defaultSettings();

    return $defaults;

  }

  /**
   * {@inheritdoc}
   */
  public function view(array $build, EntityInterface $entity, EntityDisplayBase $display, string $view_mode, ExtraFieldInterface $extra_field) {
    $form_state_additions = [
      'efs' => [
        'entity_type_id' => $entity->getEntityTypeId(),
        'entity_id' => $entity->id(),
        'embed_view_mode' => $view_mode,
      ],
    ];
    $form_display = $this->settings['form_display'];
    $form_display_class = $this->settings['form_display_class'];

    if (empty($form_display_class)) {
      $element = $this->entityFormBuilder->getForm($entity, $form_display, $form_state_additions);
    }
    else {
      $element = $this->entityFormBuilder->getForm($entity, $form_display, $form_state_additions, $form_display_class);
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(EntityDisplayFormBase $view_display, array $form, FormStateInterface $form_state, ExtraFieldInterface $extra_field, string $field) {
    $display = $view_display->getEntity();

    $form = parent::settingsForm($view_display, $form, $form_state, $extra_field, $field);

    $form['form_display'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Form display'),
      '#default_value' => $this->getSetting('form_display'),
    ];

    $form['form_display_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Form display class'),
      '#default_value' => $this->getSetting('form_display_class'),
      '#description' => $this->t('Fill it if you want to use other class than default. ie: \Drupal\Core\Entity\ContentEntityForm'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(string $context) {
    $summary = parent::settingsSummary($context);
    $summary[] = $this->getSetting('form_display');
    $summary[] = $this->getSetting('form_display_class');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable(string $entity_type_id, string $bundle) {
    return TRUE;
  }

}
