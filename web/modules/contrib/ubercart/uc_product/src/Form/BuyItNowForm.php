<?php

namespace Drupal\uc_product\Form;

use Drupal\Core\Form\BaseFormIdInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;

/**
 * Defines a simple form for adding a product to the cart.
 */
class BuyItNowForm extends FormBase implements BaseFormIdInterface {

  /**
   * Node ID of product this form is attached to.
   *
   * @var string
   */
  protected $nid;

  /**
   * Constructs a BuyItNowForm.
   *
   * @param string $nid
   *   The node ID.
   */
  public function __construct($nid) {
    $this->nid = $nid;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    // Base Form ID allows us to theme all buy-it-now-forms using the same
    // CSS class and twig template, and allows us to hook_form_BASE_ID_ALTER()
    // all buy-it-now-forms, rather than having to target each individual form.
    return 'uc_product_buy_it_now_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    // Form ID must be unique to the product so that we may have multiple
    // buy-it-now forms on a page (e.g. in a catalog view).
    return 'uc_product_buy_it_now_form_' . $this->nid;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL) {
    $query = $this->getRequest()->query->all();
    $form['#action'] = Url::fromRoute('<current>')->setOptions(['query' => $query])->toString();

    $form['nid'] = [
      '#type' => 'value',
      '#value' => $node->id(),
    ];

    $form['node'] = [
      '#type' => 'value',
      '#value' => $node,
    ];

    $form['qty'] = [
      '#type' => 'value',
      '#value' => 1,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add to cart'),
    ];

    uc_form_alter($form, $form_state, $this->getFormId());

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getRedirect()) {
      $data = \Drupal::moduleHandler()->invokeAll('uc_add_to_cart_data', [$form_state->getValues()]);
      $msg = $this->config('uc_cart.settings')->get('add_item_msg');
      $cart = \Drupal::service('uc_cart.manager')->get();
      $redirect = $cart->addItem($form_state->getValue('nid'), $form_state->getValue('qty'), $data, $msg);
      if (isset($redirect)) {
        $form_state->setRedirectUrl($redirect);
      }
    }
  }

}
