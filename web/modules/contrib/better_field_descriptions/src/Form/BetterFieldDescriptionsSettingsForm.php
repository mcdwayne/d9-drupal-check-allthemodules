<?php

namespace Drupal\better_field_descriptions\Form;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityFieldManager;

/**
 * Displays the better_field_descriptions settings form.
 */
class BetterFieldDescriptionsSettingsForm extends ConfigFormBase {

  /**
   * EntityFieldManager services object.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  private $entityFieldManager;

  /**
   * The bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfoService;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityFieldManager $entityFieldManager, EntityTypeBundleInfoInterface $bundle_info_service) {
    parent::__construct($config_factory);
    $this->entityFieldManager = $entityFieldManager;
    $this->bundleInfoService = $bundle_info_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['better_field_descriptions.settings'];
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormId() {
    return 'better_field_descriptions_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get info on bundles.
    $all_bundles = $this->bundleInfoService->getAllBundleInfo();
    // Get list of fields selected for better descriptions.
    $bfds = $this->config('better_field_descriptions.settings')->get('better_field_descriptions_settings');

    $form['descriptions'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Select fields that should have better descriptions.'),
    ];

    $form['bundles'] = [
      '#type' => 'item',
      '#prefix' => '<div id="better-descriptions-form-id-wrapper">',
      '#suffix' => '</div>',
      '#tree' => TRUE,
    ];

    foreach ($all_bundles as $entity_type => $bundles) {
      if (in_array($entity_type, ['node', 'paragraph'])) {
        foreach ($bundles as $bundle => $label) {
          // Array to hold fields in the node.
          $fields_instances = [];
          // Get info on pseudo fields, like title.
          $extra_fields = $this->entityFieldManager->getExtraFields($entity_type, $bundle, 'form');
          if (isset($extra_fields['title'])) {
            $fields_instances['title'] = $extra_fields['title']['label'];
          }

          // Get info on regular fields to the bundle.
          $fields = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);
          foreach ($fields as $field_machine_name => $field) {
            if ($field->getFieldStorageDefinition()->isBaseField() == FALSE) {
              $fields_instances[$field_machine_name] = $field->getLabel() . ' (' . $field_machine_name . ')';
            }
          }

          foreach ($fields_instances as $field => $label) {
            $enabled = isset($bfds[$entity_type][$bundle][$field]);
            $form['bundles'][$entity_type][$bundle][$field] = [
              '#type' => 'checkbox',
              '#title' => $label,
              '#default_value' => $enabled,
            ];
            $form['bundles'][$entity_type][$bundle][$field]['#parents'] = [
              'bundles',
              $entity_type,
              $bundle,
              $field,
            ];
          }
        }
      }
    }
    foreach (Element::children($form['bundles']) as $entity_type) {
      $form['bundles'][$entity_type] += [
        '#type' => 'details',
        '#title' => $entity_type,
      ];
      foreach (Element::children($form['bundles'][$entity_type]) as $bundle) {
        $form['bundles'][$entity_type][$bundle] += [
          '#type' => 'details',
          '#title' => $bundle,
        ];
      }
      uasort($form['bundles'][$entity_type], ['\Drupal\Component\Utility\SortArray', 'sortByTitleProperty']);
    }

    // Lastly, sort all packages by title.
    uasort($form['bundles'], ['\Drupal\Component\Utility\SortArray', 'sortByTitleProperty']);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // We don't want our settings to contain 0-values, only selected values.
    $bfds = [];

    // Default fields values.
    $bfd = [];
    foreach ($form_state->getValue('bundles') as $entity_type => $bundles) {
      foreach ($bundles as $bundle_machine_name => $bundle) {
        foreach ($bundle as $field_machine_name => $value) {
          // $value is (int) 0 if the field was not selected in the form.
          if (is_string($value)) {
            $bfds[$entity_type][$bundle_machine_name][$field_machine_name] = $field_machine_name;
            $bfd[$entity_type][$bundle_machine_name][$field_machine_name]['description'] = 'Sample Description';
            $bfd[$entity_type][$bundle_machine_name][$field_machine_name]['label'] = 'Label';
          }
        }
      }
    }

    $config = $this->config('better_field_descriptions.settings')->set('better_field_descriptions_settings', $bfds);
    $config = $this->config('better_field_descriptions.settings')->set('better_field_descriptions', $bfd);
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
