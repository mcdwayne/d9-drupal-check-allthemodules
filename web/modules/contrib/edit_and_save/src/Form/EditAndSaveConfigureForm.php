<?php

namespace Drupal\edit_and_save\Form;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\edit_and_save\Repository\EditAndSaveRepository;

/**
 * Settings form for Edit and Save.
 */
class EditAndSaveConfigureForm extends ConfigFormBase {

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityTypeRepository;

  /**
   * Class constructor.
   */
  public function __construct(EntityTypeRepositoryInterface $entityTypeRepository, EntityTypeBundleInfoInterface $entityTypeBundleInfo) {
    $this->entityTypeRepository = $entityTypeRepository;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.repository'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'edit_and_save.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'edit_and_save_configure_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('edit_and_save.settings');

    $form['edit_and_save_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Save & Edit General Features'),
      '#description' => $this->t('General settings that will change the usage and/or appearance of the Edit and Save module.'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['edit_and_save_settings']['edit_and_save_button_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text to use for Save & Edit button'),
      '#description' => $this->t('This is the default text that will be used for the button at the bottom of the node form.<br />It would be best to use familiar terms like "<strong>Save & Edit</strong>" or "<strong>Apply</strong>" so that users can easily understand the feature/function related to this option.'),
      '#default_value' => $config->get('edit_and_save_button_value'),
      '#required' => TRUE,
    ];
    $form['edit_and_save_settings']['edit_and_save_button_weight'] = [
      '#type' => 'weight',
      '#delta' => 100,
      '#description' => $this->t('You may adjust the positioning left to right on the button sections using the weight fields for each button type.'),
      '#title' => $this->t('Save & Edit Button Weight'),
      '#default_value' => $config->get('edit_and_save_button_weight'),
    ];
    $form['edit_and_save_settings']['edit_and_save_default_save_button_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text to use for default Save button'),
      '#description' => $this->t('This will override the default "Save" button text to something more in line with adding the "Save & Edit" and "Save & Publish" options.'),
      '#default_value' => $config->get('edit_and_save_default_save_button_value'),
      '#required' => TRUE,
    ];
    $form['edit_and_save_settings']['edit_and_save_default_save_button_weight'] = [
      '#type' => 'weight',
      '#delta' => 10,
      '#description' => $this->t('You may adjust the positioning left to right on the button sections using the weight fields for each button type.'),
      '#title' => $this->t('Default Save Button Weight'),
      '#default_value' => $config->get('edit_and_save_default_save_button_weight'),
    ];
    $form['edit_and_save_settings']['edit_and_save_hide_default_save'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide default Save button'),
      '#default_value' => $config->get('edit_and_save_hide_default_save'),
      '#description' => $this->t('This will hide the Save button.'),
    ];
    $form['edit_and_save_settings']['edit_and_save_hide_default_preview'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide default Preview button'),
      '#default_value' => $config->get('edit_and_save_hide_default_preview'),
      '#description' => $this->t('This will hide the Preview button.'),
    ];
    $form['edit_and_save_settings']['edit_and_save_hide_default_delete'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide default Delete button'),
      '#default_value' => $config->get('edit_and_save_hide_default_delete'),
      '#description' => $this->t('This will hide the Delete button.'),
    ];
    $form['edit_and_save_entities'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
    ];
    $contentEntityTypes = $this->getContentEntityTypes();
    $editAndSaveEntities = $config->get('edit_and_save_entities') ?: [];
    $options = [];
    foreach ($contentEntityTypes as $type => $entities) {

      $form['edit_and_save_entities'][$type] = [
        '#type' => 'fieldset',
        '#title' => $type . ' ' . $this->t('Entities'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
        '#tree' => TRUE,
      ];

      foreach ($entities as $key => $value) {

        $bundles[$key] = $this->entityTypeBundleInfo->getBundleInfo($key);

        foreach ($bundles[$key] as $bundleKey => $bundleLabel) {
          $options[$key][$bundleKey] = $bundleLabel['label'];
        }

        $form['edit_and_save_entities'][$type][$key] = [
          '#type' => 'checkboxes',
          '#title' => $contentEntityTypes[$type][$key],
          '#default_value' => isset($editAndSaveEntities[$type][$key]) ? $editAndSaveEntities[$type][$key] : [],
          '#options' => $options[$key],
        ];
      }
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $this->config('edit_and_save.settings')
      ->setData($form_state->getValues())
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Helper function that returns Content Entities in a form of key=>value.
   *
   * @return array
   *   Content Entities in a form of key=>value.
   */
  public function getContentEntityTypes() {

    $entityTypeLabels = $this->entityTypeRepository->getEntityTypeLabels(TRUE);
    $unsupportedEntityTypes = EditAndSaveRepository::getUnsupportedEntityTypes();

    foreach ($entityTypeLabels as $type => $entities) {
      foreach ($entities as $entity_key => $bundles) {
        if (in_array($entity_key, $unsupportedEntityTypes)) {
          unset($entityTypeLabels[$type][$entity_key]);
        }
      }
    }
    return $entityTypeLabels;
  }

}
