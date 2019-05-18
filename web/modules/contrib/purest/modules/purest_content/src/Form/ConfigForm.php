<?php

namespace Drupal\purest_content\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Url;

/**
 * Class ConfigForm.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * Drupal\Core\State\StateInterface definition.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * EntityTypeManagerInterface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityTypeBundle;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * Constructs a new ConfigForm object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, StateInterface $state, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfo $entity_type_bundle, EntityFieldManager $entity_field_manager) {
    parent::__construct($config_factory, $state);
    $this->configFactory = $config_factory;
    $this->state = $state;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeBundle = $entity_type_bundle;

    $this->entity_types = [
      'node' => $this->entityTypeBundle->getBundleInfo('node'),
      'taxonomy_term' => $this->entityTypeBundle->getBundleInfo('taxonomy_term'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('state'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'purest_content.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'purest_content_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('purest_content.settings');

    $form['heading'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('Content Field Settings'),
    ];

    $form['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Select a node type or taxonomy vocabulary to customize the rest responses of their fields.'),
    ];

    $form['entity_list'] = [
      '#title' => $this->t('Entity Fields'),
      '#type' => 'table',
      '#sticky' => TRUE,
      '#header' => [
        $this->t('Bundle / Type'),
        $this->t('Entity Type'),
        $this->t('Operations'),
      ],
    ];

    foreach ($this->entity_types as $entity_type => $bundles) {
      foreach ($bundles as $bundle_id => $bundle) {
        $form['entity_list'][$bundle_id]['name'] = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => $bundle_id,
        ];

        $form['entity_list'][$bundle_id]['type'] = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => $entity_type,
        ];

        $form['entity_list'][$bundle_id]['actions'] = [
          '#type' => 'dropbutton',
          '#links' => [
            'edit' => [
              'title' => $this
                ->t('Edit'),
              'url' => Url::fromRoute('purest_content.normalizer', [
                'type' => $entity_type,
                'bundle' => $bundle_id,
              ]),
            ],
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
    parent::submitForm($form, $form_state);
    $config = $this->configFactory->getEditable('purest_content.settings');

    if (\Drupal::moduleHandler()->moduleExists('metatag')) {
      $config->set('metatag_simplify', $form_state->getValue('metatag_simplify'));
    }

    foreach ($this->entity_types as $entity_type => $bundles) {
      foreach ($bundles as $bundle_id => $bundle) {
        $config->set($bundle_id . '_rewrite_fields', $form_state->getValue($bundle_id . '_rewrite_fields'));
        $config->set($bundle_id . '_normalize', $form_state->getValue($bundle_id . '_normalize'));
        $config->set($bundle_id . '_fields', $form_state->getValue($bundle_id . '_fields'));
      }
    }

    $config->save();
  }

}
