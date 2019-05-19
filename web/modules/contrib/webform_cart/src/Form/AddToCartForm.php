<?php

namespace Drupal\webform_cart\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform_cart\Ajax\DataLayerPush;
use Drupal\webform_cart\WebformCartInterface;

/**
 * Class AddToCartForm.
 */
class AddToCartForm extends FormBase {

  protected $webformCart;

  protected $formId;

  /**
   * @inheritDoc
   */
  public function __construct(WebformCartInterface $webform_cart) {
    $this->webformCart = $webform_cart;
  }

  public function setFormId($form_id) {
    $this->formId = 'add_to_cart_form' . $form_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    if (isset($this->formId)) {
      return $this->formId;
    } else {
      return 'add_to_cart_form';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node_id = NULL, $order_type = NULL, $data1 = NULL, $data2 = NULL, $dataLayer = NULL, $qtySetting = NULL) {

    $form['#prefix'] = '<div id="wrapper-' . $this->getFormId() . '">';
    $form['#suffix'] = '</div>';
    $form['#attached']['library'][] = "webform_cart/webform_cart-dataLayer";

    $form['node_id'] = [
      '#type' => 'hidden',
      '#title' => $this->t('Node ID'),
      '#value' => $node_id,
    ];
    $form['order_type'] = [
      '#type' => 'hidden',
      '#title' => $this->t('Order Type'),
      '#value' => $order_type,
    ];
    $form['data1'] = [
      '#type' => 'hidden',
      '#title' => $this->t('data1'),
      '#value' => $data1,
    ];
    $form['data2'] = [
      '#type' => 'hidden',
      '#title' => $this->t('data2'),
      '#value' => $data2,
    ];
    $form['dataLayer'] = [
      '#type' => 'hidden',
      '#title' => $this->t('dataLayer'),
      '#value' => $dataLayer,
    ];
    $form['quantitySetting'] = [
      '#type' => 'hidden',
      '#title' => $this->t('dataLayer'),
      '#value' => $qtySetting,
    ];
    $qty_vars = json_decode($qtySetting);

    if (!empty($qty_vars->field_resource_qty_select_values)) {
      $options = explode(PHP_EOL, $qty_vars->field_resource_qty_select_values);
      $opt = [];
      foreach ($options as $option) {
        $item = explode("|", $option);
        $opt[$item[0]] = $item[1];
      }
      $form['quantity'] = [
        '#type' => 'select',
        '#title' => $this->t('Quantity'),
        '#weight' => '0',
        '#options' => $opt,
        '#suffix' => '<span id="validate-' . $this->getFormId() . '"></span>',
      ];
    }
    elseif (!empty($qty_vars->field_resource_maximum_order)) {
      $form['quantity'] = [
        '#type' => 'number',
        '#title' => $this->t('Quantity'),
        '#weight' => '0',
        '#max_error' => $this->t('You can add a maximum of @max per order', array('@max' => $qty_vars->field_resource_maximum_order)),
        '#description' => $this->t('Maximum order: @max', array('@max' => $qty_vars->field_resource_maximum_order)),
        '#max' => $qty_vars->field_resource_maximum_order,
        '#min' => 0,
        '#default_value' => 0,
        '#suffix' => '<span id="validate-' . $this->getFormId() . '"></span>',
      ];
    }
    else {
      $form['quantity'] = [
        '#type' => 'number',
        '#title' => $this->t('Quantity'),
        '#weight' => '0',
        '#min' => 0,
        '#default_value' => 0,
        '#suffix' => '<span id="validate-' . $this->getFormId() . '"></span>',
      ];
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add To Cart'),
      '#ajax' => [
        'callback' => '::ajaxSubmit',
        'wrapper' => 'webform_cart-ajax-wrapper',
        'progress' => [
          'type' => 'throbber',
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('quantity') == '0' || empty($form_state->getValue('quantity'))) {
      $form_state->setErrorByName('quantity');
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Display result.
    foreach ($form_state->getValues() as $key => $value) {
//      drupal_set_message($key . ': ' . $value);
    }

  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array|\Drupal\Core\Ajax\AjaxResponse
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    if ($form_state::hasAnyErrors() == TRUE) {
      $response = new AjaxResponse();
      $id = 'validate-' . $this->getFormId();
      $itemAdded = '<span id="' . $id . '" class="error" >Please add a value.</span>';
      $id = '#' . $id;
      $response->addCommand(new ReplaceCommand($id, $itemAdded));
      return $response;
    }
    else {
      $webformCartResponse = $this->webformCart->setCart($form_state->getValues());
      $dataLayer = $this->addQuantity($form_state->getValue('dataLayer'), $form_state->getValue('quantity'));
      $response = new AjaxResponse();
      $id = '#wrapper-' . $this->getFormId();
      $basket_update = '<span class="webform-cart__basket-indicator empty" id="added_to_cart">' . $webformCartResponse . '</span>';
      $itemAdded = '<span class="item_added">Item added.</span>';
      $response->addCommand(new DataLayerPush($dataLayer));
      $response->addCommand(new ReplaceCommand($id, $itemAdded));
      $response->addCommand(new ReplaceCommand('#added_to_cart', $basket_update));
      return $response;
    }
  }

  private function addQuantity($dataLayer, $quantity) {

    $dataArray = json_decode($dataLayer, true);
    $qty['quantity'] = $quantity;
    $dataArray = array_merge($dataArray, $qty);
    return json_encode($dataArray);
  }

}
