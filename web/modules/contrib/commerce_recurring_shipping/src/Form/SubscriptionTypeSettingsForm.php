<?php

namespace Drupal\commerce_recurring_shipping\Form;


use Drupal\commerce\EntityTraitManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SubscriptionTypeSettingsForm
 *
 * @package Drupal\commerce_recurring_shipping\Form
 */
class SubscriptionTypeSettingsForm extends ConfigFormBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * @var \Drupal\commerce\EntityTraitManagerInterface
   */
  protected $entityTraitManager;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * SubscriptionTypeSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   * @param \Drupal\commerce\EntityTraitManagerInterface $entity_trait_manager
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityTraitManagerInterface $entity_trait_manager, EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($config_factory);

    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityTraitManager = $entity_trait_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.bundle.info'),
      $container->get('plugin.manager.commerce_entity_trait'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['commerce_recurring_shipping.settings'];
  }

  /**
   * Returns a unique string identifying the form.
   *
   * The returned ID should be a unique string that can be a valid PHP function
   * name, since it's used in hook implementation names such as
   * hook_form_FORM_ID_alter().
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'commerce_recurring_shipping_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('commerce_recurring_shipping.settings');
    $options = [];
    $bundles = $this->entityTypeBundleInfo->getBundleInfo('commerce_subscription');
    foreach ($bundles as $bundle_name => $bundle) {
      $options[$bundle_name] = $bundle['label'];
    }
    $form['subscription_types'] = [
      '#type' => 'checkboxes',
      '#options' => $options,
      '#title' => t('Shippable subscription types'),
      '#description' => t('Enabled subscriptions types will allow to store shipping information'),
      '#default_value' => $config->get('subscription_types'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('commerce_recurring_shipping.settings');
    $bundles = $form_state->getValue('subscription_types');
    $bundles_list = $this->entityTypeBundleInfo->getBundleInfo('commerce_subscription');
    /** @var \Drupal\commerce\Plugin\Commerce\EntityTrait\EntityTraitInterface $trait */
    $trait = $this->entityTraitManager->createInstance('shippable_subscription');
    foreach ($bundles as $bundle_name => $bundle) {
      if ($bundle && $config->get('subscription_types.' . $bundle_name) === 0) {
        $this->entityTraitManager->installTrait($trait, 'commerce_subscription', $bundle_name);
      }
      if (!$bundle && $config->get('subscription_types.' . $bundle_name) !== 0) {
        $field_definitions = $this->entityFieldManager->getFieldDefinitions('commerce_subscription', $bundle_name);
        if (!isset($field_definitions['shipping_profile']) || !isset($field_definitions['shipping_method'])) {
          $this->messenger->addWarning($this->t('The fields were not previously installed. Please try to add them again.'));
        } else {
          if ($this->entityTraitManager->canUninstallTrait($trait, 'commerce_subscription', $bundle_name)) {
            $this->entityTraitManager->uninstallTrait($trait, 'commerce_subscription', $bundle_name);
          }
          else {
            $bundles[$bundle_name] = $bundle_name;
            $this->messenger->addWarning($this->t('Subscription type @name already has values in shipping fields, please delete them first and then try again to disable shippable option.', ['@name' => $bundles_list[$bundle_name]['label']]));
          }
        }
      }
    }
    $config->set('subscription_types', $bundles);

    $config->save();
  }

}
