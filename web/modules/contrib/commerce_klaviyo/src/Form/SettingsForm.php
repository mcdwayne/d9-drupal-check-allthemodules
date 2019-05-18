<?php

namespace Drupal\commerce_klaviyo\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_product\Entity\ProductType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form that configures commerce_klaviyo settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityDisplayRepositoryInterface $entity_display_repository, EntityFieldManagerInterface $entity_field_manager) {
    $this->entityDisplayRepository = $entity_display_repository;
    $this->entityFieldManager = $entity_field_manager;
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_display.repository'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_klaviyo_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['commerce_klaviyo.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $klaviyo_config = $this->config('commerce_klaviyo.settings');
    $product_types_config = $klaviyo_config->get('product_types');
    $definitions = $this->entityFieldManager->getFieldDefinitions('profile', 'customer');

    foreach ($definitions as $field_name => $definition) {
      if ($definition instanceof FieldConfigInterface) {
        if ($definition->getType() == 'telephone') {
          $telephone_fields[$field_name] = $definition->getLabel();
        }
      }
    }

    $form['public_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Public Key'),
      '#default_value' => $klaviyo_config->get('public_key'),
      '#required' => TRUE,
    ];
    $form['private_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Private Key'),
      '#default_value' => $klaviyo_config->get('private_key'),
      '#required' => TRUE,
    ];
    $form['telephone_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Telephone'),
      '#options' => $telephone_fields ?: [],
      '#empty_value' => '',
      '#description' => $this->t("Choose a phone field from the 'customer' profile, from which a customer's phone can be retrieved and sent to Klaviyo for tracking."),
      '#default_value' => $klaviyo_config->get('telephone_field'),
    ];

    if ($product_types = ProductType::loadMultiple()) {
      $form['product_types'] = [
        '#type' => 'container',
        '#tree' => TRUE,
        '#attributes' => ['class' => ['product-types']],
      ];
      $product_view_modes = $this->entityDisplayRepository->getViewModeOptions('commerce_product');
      /** @var \Drupal\commerce_product\Entity\ProductType $product_type */
      foreach ($product_types as $product_type_id => $product_type) {
        $product_type_config = $product_types_config[$product_type_id] ?: [];
        $form['product_types'][$product_type_id] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Configure which data should be sent to Klaviyo for the product type %product_type_label.', [
            '%product_type_label' => $product_type->label(),
          ]),
        ];

        $form['product_types'][$product_type_id]['view_modes'] = [
          '#type' => 'checkboxes',
          '#title' => $this->t('View modes'),
          '#options' => $product_view_modes,
          '#default_value' => $product_type_config['view_modes'] ?: NULL,
          '#description' => $this->t('Klaviyo allows us to send "Viewed product" events. By this setting you can choose for which products send these events and on which view modes. Leave the "View modes" setting empty to do not notify klaviyo about the "Viewed product" event for the specific product type.'),
        ];

        $definitions = $this->entityFieldManager->getFieldDefinitions('commerce_product', $product_type_id);

        foreach ($definitions as $field_name => $definition) {
          if ($definition instanceof FieldConfigInterface) {
            // TODO Support other field types.
            $field_type = $definition->getType();
            $entity_reference = $field_type == 'entity_reference';
            $taxonomy_term = $definition->getSetting('target_type') == 'taxonomy_term';

            if ($entity_reference && $taxonomy_term) {
              $taxonomy_reference_fields[$field_name] = $definition->getLabel();
            }
            elseif ($field_type == 'image') {
              $image_fields[$field_name] = $definition->label();
            }
          }
        }

        $base = [
          '#type' => 'select',
          '#options' => $taxonomy_reference_fields ?: [],
          '#empty_value' => '',
          '#description' => $this->t('For now only taxonomy term references are allowed.'),
        ];
        $form['product_types'][$product_type_id]['category_field'] = [
          '#title' => $this->t('Category field'),
          '#default_value' => $product_type_config['category_field'] ?: NULL,
        ] + $base;
        $form['product_types'][$product_type_id]['brand_field'] = [
          '#title' => $this->t('Brand field'),
          '#default_value' => $product_type_config['brand_field'] ?: NULL,
        ] + $base;
        $form['product_types'][$product_type_id]['image_field'] = [
          '#title' => $this->t('Image field'),
          '#options' => $image_fields ?: [],
          '#default_value' => $product_type_config['image_field'] ?: NULL,
        ] + $base;
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $product_types = $values['product_types'] ?: [];

    foreach ($product_types as $product_type_id => $product_type_values) {
      if (!empty($product_type_values['view_modes'])) {
        $product_types[$product_type_id]['view_modes'] = array_filter($product_type_values['view_modes']);
      }
    }

    $this->config('commerce_klaviyo.settings')
      ->set('public_key', $values['public_key'])
      ->set('private_key', $values['private_key'])
      ->set('telephone_field', $values['telephone_field'])
      ->set('product_types', $product_types)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
