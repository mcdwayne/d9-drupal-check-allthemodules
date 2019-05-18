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
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class EntityResourceFieldForm.
 */
class EntityResourceFieldForm extends ConfigFormBase {

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
  public function __construct(ConfigFactoryInterface $config_factory, StateInterface $state, EntityTypeManagerInterface $type_manager, EntityTypeBundleInfo $type_bundle, EntityFieldManager $entity_field_manager) {
    parent::__construct($config_factory, $state);
    $this->configFactory = $config_factory;
    $this->state = $state;
    $this->entityTypeManager = $type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeBundle = $type_bundle;

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
   * Returns the title of the current page.
   *
   * @return string
   *   The page title.
   */
  public function getTitle() {
    $type = \Drupal::routeMatch()->getParameter('type');
    $bundle = \Drupal::routeMatch()->getParameter('bundle');
    $field = \Drupal::routeMatch()->getParameter('field');
    $bundle_fields = $this->entityFieldManager->getFieldDefinitions($type, $bundle);

    if (isset($bundle_fields[$field])) {
      return $bundle_fields[$field]->getLabel() . ' [' . $bundle_fields[$field]->getName() . '] Field Settings';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type = NULL, $bundle = NULL, $field = NULL) {
    $config = $this->configFactory->get('purest_content.settings');
    $this->type = $type;
    $this->bundle = $bundle;
    $this->field = $field;

    if (!$type || !$bundle) {
      return new RedirectResponse(\Drupal::url('purest_content.config'));
    }

    $values = $config->get($bundle . '_fields');

    if (NULL === $values) {
      $values = [
        $field = [
          'custom_label' => '',
          'exclude' => 0,
        ],
      ];
    }
    elseif (NULL === $values[$bundle . '_fields'][$field]) {
      $values[$field] = [
        'custom_label' => '',
        'exclude' => 0,
      ];
    }

    $form[$bundle][$bundle . '_fields'][$field]['custom_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom Label'),
      '#size' => 30,
      '#default_value' => NULL !== $values[$field]['custom_label'] ?
      $values[$field]['custom_label'] : '',
    ];

    $form[$bundle][$bundle . '_fields'][$field]['exclude'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude From Responses'),
      '#default_value' => NULL !== $values[$field]['exclude'] ?
      $values[$field]['exclude'] : 0,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->configFactory->getEditable('purest_content.settings');
    $config->save();
  }

}
