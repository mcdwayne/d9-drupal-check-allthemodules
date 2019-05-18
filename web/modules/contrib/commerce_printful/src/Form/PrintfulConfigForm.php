<?php

namespace Drupal\commerce_printful\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The Printful configuration form.
 */
class PrintfulConfigForm extends ConfigFormBase {

  /**
   * A list of purchasable entity types and bundles.
   *
   * @var array
   */
  protected $purchasableEntityTypes;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManager $entity_type_manager, EntityTypeBundleInfo $entity_type_bundle_info) {
    parent::__construct($config_factory);

    // Prepare the list of purchasable entity types and bundles.
    $entity_types = $entity_type_manager->getDefinitions();
    $purchasable_entity_types = array_filter($entity_types, function ($entity_type) {
      return $entity_type->isSubclassOf('\Drupal\commerce\PurchasableEntityInterface');
    });
    $purchasable_entity_types = array_map(function ($entity_type) {
      return $entity_type->getLabel();
    }, $purchasable_entity_types);
    foreach ($purchasable_entity_types as $type => $label) {
      $this->purchasableEntityTypes[$type] = [
        'label' => $label,
        'bundles' => [],
      ];
      foreach ($entity_type_bundle_info->getBundleInfo($type) as $bundle_id => $bundle_info) {
        $this->purchasableEntityTypes[$type]['bundles'][$bundle_id] = $bundle_info['label'];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_printful_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('commerce_printful.settings');

    $form['connection'] = [
      '#type' => 'details',
      '#title' => $this->t('Connection'),
      '#open' => TRUE,
    ];

    $form['connection']['help'] = [
      '#type' => 'details',
      '#title' => $this->t('Help'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $form['connection']['help']['list'] = [
      '#theme' => 'item_list',
      '#type' => 'ol',
      '#items' => [
        $this->t('Log in to your Printful account in order to access the dashboard.'),
        $this->t('Click on your username in the header to access the profile menu.'),
        $this->t('Click "Stores".'),
        $this->t('Click "Edit" to the right of the desired store.'),
        $this->t('Click "API" in the menu on the left.'),
        $this->t('Click "Enable API Access".'),
        $this->t('Enter the "API Key" from the Printful dashboard into the field below.'),
        $this->t('Click "Save configuration".'),
      ],
    ];

    $form['connection']['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#default_value' => $config->get('api_key'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->config('commerce_printful.settings');
    $config->set('api_key', $values['api_key']);
    $config->save();

    drupal_set_message($this->t('Printful configuration updated.'));
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'commerce_printful.settings',
    ];
  }

}
