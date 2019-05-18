<?php

namespace Drupal\uc_order\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_order\Plugin\LineItemManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to add a line item to an order.
 */
class AddLineItemForm extends FormBase {

  /**
   * The line item manager.
   *
   * @var \Drupal\uc_order\Plugin\LineItemManagerInterface
   */
  protected $lineItemManager;

  /**
   * Form constructor.
   *
   * @param \Drupal\uc_order\Plugin\LineItemManagerInterface $line_item_manager
   *   The line item manager.
   */
  public function __construct(LineItemManagerInterface $line_item_manager) {
    $this->lineItemManager = $line_item_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.uc_order.line_item')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_order_add_line_item_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, OrderInterface $order = NULL, $line_item_id = '') {
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Line item title'),
      '#description' => $this->t('Display title of the line item.'),
      '#size' => 32,
      '#maxlength' => 128,
      '#default_value' => $$this->lineItemManager->getDefinition($line_item_id)['title'],
    ];
    $form['amount'] = [
      '#type' => 'uc_price',
      '#title' => $this->t('Line item amount'),
      '#allow_negative' => TRUE,
    ];

    $form['order_id'] = [
      '#type' => 'hidden',
      '#value' => $order->id(),
    ];
    $form['line_item_id'] = [
      '#type' => 'hidden',
      '#value' => $line_item_id,
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add line item'),
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('entity.uc_order.edit_form', ['uc_order' => $order->id()]),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    uc_order_line_item_add($form_state->getValue('order_id'), $form_state->getValue('line_item_id'), $form_state->getValue('title'), $form_state->getValue('amount'));
    $this->messenger()->addMessage($this->t('Line item added to order.'));
    $form_state->setRedirect('entity.uc_order.edit_form', ['uc_order' => $form_state->getValue('order_id')]);
  }

}
