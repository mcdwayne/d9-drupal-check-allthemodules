<?php

namespace Drupal\views_dynamic_entity_row\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views_dynamic_entity_row\DynamicEntityRowManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ViewsDynamicEntityRowSettingsForm.
 *
 * @package Drupal\views_dynamic_entity_row\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Discovery and retrieval of entity type bundles manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * The Views dynamic entity row plugin settings manager.
   *
   * @var \Drupal\views_dynamic_entity_row\DynamicEntityRowManagerInterface
   */
  protected $dynamicRowManager;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   Discovery and retrieval of entity type bundles manager.
   * @param \Drupal\views_dynamic_entity_row\DynamicEntityRowManagerInterface $dynamic_row_manager
   *   The Views dynamic entity row plugin settings manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $bundle_info, DynamicEntityRowManagerInterface $dynamic_row_manager) {
    parent::__construct($config_factory);

    $this->entityTypeManager = $entity_type_manager;
    $this->bundleInfo = $bundle_info;
    $this->dynamicRowManager = $dynamic_row_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('views_dynamic_entity_row.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'views_dynamic_entity_row.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'views_dynamic_entity_row_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['entity_types'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Supported entity types'),
    ];

    $entity_types = $this->entityTypeManager->getDefinitions();
    foreach ($entity_types as $entity_type_id => $entity_type) {
      if ($entity_type->hasViewBuilderClass()) {
        $form['entity_types'][$entity_type_id] = [
          '#type' => 'container',
          '#tree' => TRUE,
        ];
        $form['entity_types'][$entity_type_id]['enable'] = [
          '#type' => 'checkbox',
          '#title' => $entity_type->getLabel(),
          '#default_value' => $this->dynamicRowManager->isSupported($entity_type_id),
        ];

        $bundles = $this->bundleInfo->getBundleInfo($entity_type_id);
        if (count($bundles) > 1) {
          $form['entity_types'][$entity_type_id]['settings'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('@label settings', ['@label' => $entity_type->getLabel()]),
            '#states' => [
              'visible' => [
                ':input[name="' . $entity_type_id . '[enable]"]' => [
                  'checked' => TRUE,
                ],
              ],
            ],
          ];
          $form['entity_types'][$entity_type_id]['settings']['mode'] = [
            '#type' => 'radios',
            '#title' => $this->t('Control mode'),
            '#options' => [
              DynamicEntityRowManagerInterface::ALL_BUNDLES => $this->t('all bundles'),
              DynamicEntityRowManagerInterface::PER_BUNDLE => $this->t('per bundle'),
            ],
            '#default_value' => $this->dynamicRowManager->getSupportMode($entity_type_id),
          ];

          $options = [];
          foreach ($bundles as $bundle_id => $bundle) {
            $options[$bundle_id] = $bundle['label'];
          }
          $form['entity_types'][$entity_type_id]['settings']['bundles'] = [
            '#type' => 'checkboxes',
            '#title' => $this->t('Bundles'),
            '#options' => $options,
            '#multiple' => TRUE,
            '#states' => [
              'visible' => [
                ':input[name="' . $entity_type_id . '[settings][mode]"]' => [
                  'value' => DynamicEntityRowManagerInterface::PER_BUNDLE,
                ],
              ],
            ],
            '#default_value' => $this->dynamicRowManager->getSupportedBundles($entity_type_id),
          ];
        }
        else {
          $form['entity_types'][$entity_type_id]['settings']['mode'] = [
            '#type' => 'value',
            '#value' => DynamicEntityRowManagerInterface::ALL_BUNDLES,
          ];
        }
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('views_dynamic_entity_row.settings');
    $settings = $config->get('entity_types');
    if (empty($settings)) {
      $settings = [];
    }

    foreach ($form_state->getValues() as $key => $value) {
      if (is_array($value)) {
        if (!empty($value['enable'])) {
          $settings[$key]['all'] = $value['settings']['mode'];
          if ($value['settings']['mode'] == DynamicEntityRowManagerInterface::PER_BUNDLE) {
            $bundles = array_filter($value['settings']['bundles']);
            $settings[$key]['bundles'] = array_keys($bundles);
          }
          else {
            $settings[$key]['bundles'] = array_keys($value['settings']['bundles']);
          }
        }
        else {
          unset($settings[$key]);
        }
      }
    }

    $config->set('entity_types', $settings)->save();
  }

}
