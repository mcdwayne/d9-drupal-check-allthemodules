<?php

namespace Drupal\paragraphs_viewmode\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;
use Drupal\paragraphs_viewmode\ParagraphsViewmodeBehaviorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ParagraphsViewmodeBehavior.
 *
 * @ParagraphsBehavior(
 *   id="paragraphs_viewmode_behavior",
 *   label=@Translation("Paragraphs View Mode"),
 *   description=@Translation("A Plugin to allow overriding a paragraph view mode while on default")
 * )
 */
class ParagraphsViewmodeBehavior extends ParagraphsBehaviorBase implements ParagraphsViewmodeBehaviorInterface {

  /**
   * The entity Display Repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a ParagraphsViewmodeBehavior object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityFieldManager $entity_field_manager, EntityDisplayRepositoryInterface $entity_display_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_field_manager);

    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
    $container->get('entity_field.manager'),
    $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $view_modes = $this->entityDisplayRepository->getViewModeOptions('paragraph');
    $form['override_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Select which view mode to override'),
      '#options' => $view_modes,
      '#default_value' => $this->configuration['override_mode'],
    ];
    $form['override_available'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select which view modes are allowable'),
      '#multiple' => TRUE,
      '#options' => $view_modes,
      '#default_value' => $this->configuration['override_available'],
    ];
    $form['override_default'] = [
      '#type' => 'select',
      '#title' => $this->t('Select default view mode for content to use'),
      '#options' => $view_modes,
      '#default_value' => $this->configuration['override_default'],
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    $default = $form_state->getValue('override_default');
    $allowed = array_filter($form_state->getValue('override_available'));

    if (!in_array($default, $allowed)) {
      $form_state->setError($form['override_default'], 'Default view mode must also be selected in allowed view modes');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $override_mode = $form_state->getValue('override_mode');
    $override_available = $form_state->getValue('override_available');
    $override_default = $form_state->getValue('override_default');

    $this->configuration['override_mode'] = $override_mode;
    $this->configuration['override_available'] = $override_available;
    $this->configuration['override_default'] = $override_default;

    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'override_mode' => 'default',
      'override_available' => ['default' => 'Default'],
      'override_default' => 'default',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $all_modes = $this->entityDisplayRepository->getViewModeOptions('paragraph');
    $available_modes = array_filter($this->configuration['override_available']);
    $mode = $paragraph->getBehaviorSetting($this->pluginId, 'view_mode', $this->configuration['override_default']);
    $mode_options = array_intersect_key($all_modes, $available_modes);
    $form['view_mode'] = [
      '#type' => 'select',
      '#title' => 'Select which view mode to use for this paragraph',
      '#options' => $mode_options,
      '#default_value' => $mode,
    ];
    return parent::buildBehaviorForm($paragraph, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $paragraph->setBehaviorSettings($this->pluginId, ['view_mode', $form_state->getValue('view_mode')]);
    parent::submitBehaviorForm($paragraph, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function entityViewModeAlter(&$view_mode, ParagraphInterface $paragraph, array $context) {
    $override_mode = $this->configuration['override_mode'];
    $new_view_mode = $paragraph->getBehaviorSetting($this->pluginId, 'view_mode', $this->configuration['override_default']);

    if ($view_mode != $override_mode || $override_mode == $new_view_mode) {
      return;
    }
    $view_mode = $new_view_mode;
  }

}
