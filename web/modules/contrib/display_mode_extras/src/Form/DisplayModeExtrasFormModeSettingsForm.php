<?php

namespace Drupal\display_mode_extras\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Display Mode Extras settings.
 */
class DisplayModeExtrasFormModeSettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The settings object.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'display_mode_extras_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'display_mode_extras.settings',
    ];
  }

  /**
   * DisplayModeExtrasFormModeSettingsForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, EntityDisplayRepositoryInterface $entity_display_repository, ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);

    $this->entityTypeManager = $entity_manager;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->settings = $this->config('display_mode_extras.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $all_form_modes = $this->entityDisplayRepository->getAllFormModes();

    $form['vertical_tabs'] = [
      '#type' => 'vertical_tabs',
    ];

    $settings_form_modes = $this->settings->get('form_modes');

    foreach ($all_form_modes as $entity_type_id => $form_modes) {

      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

      $entity_type_label = $entity_type->getLabel();

      $form[$entity_type_id] = [
        '#type' => 'details',
        '#title' => $entity_type_label,
        '#group' => 'vertical_tabs',
      ];

      // See https://www.drupal.org/node/1876710.
      $form[$entity_type_id]['settings'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Mode'),
          $this->t('Enabled'),
          $this->t('Weight'),
          '',
        ],
        '#tabledrag' => [
          [
            'action' => 'order',
            'relationship' => 'sibling',
            'group' => 'table-sort-weight',
          ],
        ],
      ];

      foreach ($form_modes as $form_mode_name => $form_mode) {

        // Format: [entity_type_id.form_mode_name].
        $form_mode_id = $form_mode['id'];
        $form_mode_label = $form_mode['label'];


        $weight = 0;

        if (isset($settings_form_modes[$entity_type_id][$form_mode_name]['weight'])) {
          $weight = $settings_form_modes[$entity_type_id][$form_mode_name]['weight'];
        }

        $enabled = 0;

        if (isset($settings_form_modes[$entity_type_id][$form_mode_name]['enabled'])) {
          $enabled = $settings_form_modes[$entity_type_id][$form_mode_name]['enabled'];
        }

        $form[$entity_type_id]['settings'][$form_mode_id] = [
          '#attributes' => ['class' => 'draggable'],
          '#weight' => $weight,
          'mode' => [
            '#plain_text' => $form_mode_label,
          ],
          'enabled' => [
            '#type' => 'checkbox',
            '#default_value' => $enabled,
          ],
          'weight' => [
            '#type' => 'weight',
            '#title' => t('Weight for @title', ['@title' => $form_mode_label]),
            '#title_display' => 'invisible',
            '#default_value' => $weight,
            '#attributes' => ['class' => ['table-sort-weight']],
          ],
          'label' => [
            '#type' => 'hidden',
            '#value' => "$entity_type_label : $form_mode_label",
          ],
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();

    foreach ($values['settings'] as $form_mode_id => $settings_form_mode) {
      $this->settings->set("form_modes.$form_mode_id", $settings_form_mode);
    }
    $this->settings->save();

    parent::submitForm($form, $form_state);
  }

}
