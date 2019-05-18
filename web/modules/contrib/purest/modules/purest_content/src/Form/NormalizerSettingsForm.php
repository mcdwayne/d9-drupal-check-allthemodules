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
use Drupal\Core\Url;

/**
 * Class NormalizerSettingsForm.
 */
class NormalizerSettingsForm extends ConfigFormBase {

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
   * The product storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

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
    $bundle = \Drupal::routeMatch()->getParameter('bundle');
    return ucfirst(str_replace('_', ' ', $bundle)) . ' Resource Settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type = NULL, $bundle = NULL) {
    if (!$type || !$bundle) {
      return new RedirectResponse(\Drupal::url('purest_content.config'));
    }

    $this->type = $type;
    $this->bundle = $bundle;
    $config = $this->configFactory->get('purest_content.' . $this->type . '.' . $this->bundle);
    $bundle_fields = $this->entityFieldManager->getFieldDefinitions($type, $bundle);

    $form['heading'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => ucfirst(str_replace('_', ' ', $bundle)) . $this
        ->t('Field Settings'),
    ];

    $form['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this
        ->t('Use this form to customize or exclude the output of each field.'),
    ];

    $form[$bundle] = [
      '#type' => 'field_group',
      '#title' => ucfirst($bundle) . ' [' . $type . ']',
    ];

    $form[$bundle]['normalize_heading'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $this
        ->t('Purest Normalizer'),
    ];

    $form[$bundle]['normalize'] = [
      '#title' => $this->t('Use Purest normalizer'),
      '#description' => $this
        ->t('Turn this off to exlude this entity type from normalization.'),
      '#type' => 'checkbox',
      '#default_value' => NULL === $config->get('normalize') ?
      1 : $config->get('normalize'),
    ];

    $form[$bundle]['fields_heading'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $this
        ->t('Field Settings'),
    ];

    $form[$bundle]['fields'] = [
      '#title' => $this->t('Entity Fields'),
      '#type' => 'table',
      '#sticky' => TRUE,
      '#header' => [
        $this->t('Name'),
        $this->t('Label'),
        $this->t('Type'),
        $this->t('Custom Label'),
        $this->t('Hide if Empty'),
        $this->t('Exclude'),
        // $this->t('Operations'),.
      ],
    ];

    $values = $config->get('fields');

    foreach ($bundle_fields as $field_name => $field_definition) {
      $form[$bundle]['fields'][$field_name]['name'] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $field_definition->getLabel(),
      ];

      $form[$bundle]['fields'][$field_name]['label'] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $field_name,
      ];

      $form[$bundle]['fields'][$field_name]['type'] = [
        '#type' => 'textfield',
        '#disabled' => TRUE,
        '#size' => 20,
        '#default_value' => $field_definition->getType(),
      ];

      $form[$bundle]['fields'][$field_name]['custom_label'] = [
        '#type' => 'textfield',
        '#size' => 20,
        '#default_value' => NULL !== $values[$field_name]['custom_label'] ?
        $values[$field_name]['custom_label'] : '',
      ];

      $form[$bundle]['fields'][$field_name]['hide_empty'] = [
        '#type' => 'checkbox',
        '#default_value' => NULL !== $values[$field_name]['hide_empty'] ?
        intval($values[$field_name]['hide_empty']) : 0,
      ];

      $form[$bundle]['fields'][$field_name]['exclude'] = [
        '#type' => 'checkbox',
        '#default_value' => NULL !== $values[$field_name]['exclude'] ?
        intval($values[$field_name]['exclude']) : 0,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->configFactory
      ->getEditable('purest_content.' . $this->type . '.' . $this->bundle);

    $config->set('normalize', $form_state->getValue('normalize'));
    $config->set('fields', $form_state->getValue('fields'));

    $config->save();
  }

}
