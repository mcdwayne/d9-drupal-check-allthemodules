<?php

namespace Drupal\webform_cart\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\webform_cart\Form\AddToCartForm;
use Drupal\webform_cart\WebformCartInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A handler to provide a field that is completely custom by the administrator.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("add_to_cart")
 */
class AddToCart extends FieldPluginBase {

  protected $webformCart;

  /**
   * AddToCart constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\webform_cart\WebformCartInterface $webform_cart
   * @param \Drupal\webform_cart\Form\AddToCartForm $addto_cart_form
   */
  public function __construct(array $configuration,
                                 $plugin_id,
                                 $plugin_definition,
                                 WebformCartInterface $webform_cart) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->webformCart = $webform_cart;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container,
                                array $configuration,
                                $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('webform_cart.webformcart')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['order_type'] = ['default' => NULL];
    $options['data1'] = ['default' => NULL];
    $options['data2'] = ['default' => NULL];
    $options['dataLayer'] = ['default' => NULL];
    $options['qtySetting'] = ['default' => NULL];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $entity_type = 'webform_cart_order_type';
    $order_types = \Drupal::entityTypeManager()
      ->getStorage($entity_type)
      ->loadMultiple();
    $options = [];
    foreach ($order_types as $order_type) {
      $options[$order_type->id()] = $order_type->label();
    }
    $form['order_type'] = [
      '#title' => $this->t('Choose Order Type'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $this->options['order_type'],
    ];
    $form['data1'] = [
      '#title' => $this->t('Data 1, Add Tokens or Replacement Value'),
      '#description' => $this->t('For passing additional values to the line item, Add Tokens or Replacement Value, format: {"valOne": "val1", "valTwo": "val2"}'),
      '#type' => 'textarea',
      '#default_value' => $this->options['data1'],
    ];
    $form['data2'] = [
      '#title' => $this->t('Data 2, Add Tokens or Replacement Value'),
      '#type' => 'textarea',
      '#description' => $this->t('For passing additional values to the line item, Add Tokens or Replacement Value, format: {"valOne": "val1", "valTwo": "val2"}'),
      '#default_value' => $this->options['data2'],
    ];
    $form['qtySetting'] = [
      '#title' => $this->t('Quantity Setting, Add Tokens or Replacement Value'),
      '#type' => 'textarea',
      '#description' => $this->t('For limiting the quantity field, Add Tokens or Replacement Value, format: {"field_resource_maximum_order":"{{ field_maximum_order }}","field_resource_qty_select_values":"0|Please Select\n2|Two\n4|four"}'),
      '#default_value' => $this->options['qtySetting'],
    ];
    $form['dataLayer'] = [
      '#title' => $this->t('DataLayer, Add Tokens or Replacement Value'),
      '#type' => 'textarea',
      '#description' => $this->t('For passing values back to the Data Layer through onclick event, Add Tokens or Replacement Value, format: {"valOne": "val1", "valTwo": "val2"}'),
      '#default_value' => $this->options['dataLayer'],
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $tokens = $this->getRenderTokens(NULL);
    $data1 = (isset($this->options['data1']) ? $this->viewsTokenReplace($this->options['data1'], $tokens) : NULL);
    $data2 = (isset($this->options['data2']) ? $this->viewsTokenReplace($this->options['data2'], $tokens) : NULL);
    $dataLayer = (isset($this->options['dataLayer']) ? $this->viewsTokenReplace($this->options['dataLayer'], $tokens) : NULL);
    $qtySetting = (isset($this->options['qtySetting']) ? $this->viewsTokenReplace($this->options['qtySetting'], $tokens) : NULL);
    $nodeId = $this->getEntityId($values);
    $form_pre = new AddToCartForm($this->webformCart);
    $form_pre->setFormId('-' . $values->index . '-' . $nodeId);
    $form = \Drupal::formBuilder()->getForm($form_pre, $nodeId, $this->options['order_type'], $data1, $data2, $dataLayer, $qtySetting);
    return $form;
  }

  /**
   * @param $values
   *
   * @return bool
   */
  private function getEntityId($values) {
    if ($values->_entity == NULL && isset($values->search_api_id)) {
      $id = preg_replace('/[^0-9]/', '', $values->search_api_id);
    }
    elseif (isset($values->nid)) {
      $id = $values->nid;
    }
    else {
      $id = NULL;
    }
    return $id;
  }

}
