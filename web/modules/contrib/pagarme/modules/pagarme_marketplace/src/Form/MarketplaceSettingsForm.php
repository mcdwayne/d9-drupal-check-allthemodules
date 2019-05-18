<?php

namespace Drupal\pagarme_marketplace\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;
/**
 * Class MarketplaceSettingsForm.
 *
 * @package Drupal\pagarme_marketplace\Form
 */
class MarketplaceSettingsForm extends FormBase {

  protected $route_match;

  protected $entity_type_manager;

  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entity_type_manager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'pagarme_marketplace.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pagarme_marketplace_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $recipient_id = NULL) {
    $config = $this->config('pagarme_marketplace.settings');

    $currencies = $this->entity_type_manager->getStorage('commerce_currency')->loadMultiple();
    $options = [];
    foreach ($currencies as $currency) {
      $currency_code = $currency->get('currencyCode');
      $name = $currency->get('name');
      $options[$currency_code] = $name;
    }

    $form['default_currency'] = [
      '#type' => 'select',
      '#title' =>  $this->t('Default currency'),
      '#description' => $this->t('Currency that will be used to format the values displayed in the marketplace.'),
      '#options' => $options,
      '#default_value' => $config->get('default_currency'),
      '#required' => TRUE,
    ];

    $form['number_items_per_page'] = [
      '#type' => 'number',
      '#title' =>  $this->t('Number of items per page'),
      '#description' => $this->t('Number of items displayed in the marketplace listings.'),
      '#default_value' => $config->get('number_items_per_page'),
      '#required' => TRUE,
    ];

    $product_variations = $this->entity_type_manager->getStorage('commerce_product_variation')->loadMultiple();
    $options = array();
    foreach ($product_variations as $product_variation) {
      $type = $product_variation->get('type')->getString();
      $options[$type] = $type;
    }

    $form['split_line_item_types'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Invoice item types to use split rule'),
      '#default_value' => $config->get('split_line_item_types'),
      '#options' => $options,
      '#description' => t('Select the types of order items that will be considered in the split rule calculation.'),
    );

    $form['multiple_split_per_product'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable multiple split rule per product'),
      '#default_value' => $config->get('multiple_split_per_product'),
      '#description' => t('Enables the registration of several split rule per product. <br/> <b> Note: </ b> By registering multiple division rules for the same product, the most recent rule will be used by default. To use a specific one in a given context use <b>"hook_query_load_split_product_alter"</ b> to change the query that gets the split rule of the product.'),
    );

    $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save settings'),
    ];

    return $form;
  }

  /**
    * {@inheritdoc}
    */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->configFactory()->getEditable('pagarme_marketplace.settings')
      ->set('default_currency', $values['default_currency'])
      ->set('number_items_per_page', $values['number_items_per_page'])
      ->set('split_line_item_types', $values['split_line_item_types'])
      ->set('multiple_split_per_product', $values['multiple_split_per_product'])
      ->save();
    drupal_set_message($this->t('Settings saved.'));
  }
}
