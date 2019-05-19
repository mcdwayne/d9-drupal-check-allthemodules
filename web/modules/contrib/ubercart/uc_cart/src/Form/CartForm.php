<?php

namespace Drupal\uc_cart\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\uc_cart\CartInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Displays the contents of the customer's cart.
 *
 * Handles simple or complex objects. Some cart items may have a list of
 * products that they represent. These are displayed but are not able to
 * be changed by the customer.
 */
class CartForm extends FormBase {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * Constructs a new CartForm.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session.
   */
  public function __construct(RendererInterface $renderer, ModuleHandlerInterface $module_handler, SessionInterface $session) {
    $this->renderer = $renderer;
    $this->moduleHandler = $module_handler;
    $this->session = $session;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('module_handler'),
      $container->get('session')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_cart_view_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, CartInterface $cart = NULL) {
    $form['#attached']['library'][] = 'uc_cart/uc_cart.styles';
    $cart_config = $this->config('uc_cart.settings');

    $form['items'] = [
      '#type' => 'table',
      '#tree' => TRUE,
      '#header' => [
        'remove' => [
          'data' => $this->t('Remove'),
          'class' => ['remove'],
        ],
        'image' => [
          'data' => '',
          'class' => ['image', RESPONSIVE_PRIORITY_LOW],
        ],
        'desc' => [
          'data' => $this->t('Product'),
          'class' => ['desc'],
        ],
        'qty' => [
          'data' => $this->t('Quantity'),
          'class' => ['qty'],
        ],
        'total' => [
          'data' => $this->t('Total'),
          'class' => ['price'],
        ],
      ],
    ];

    $form['data'] = [
      '#tree' => TRUE,
      '#parents' => ['items'],
    ];

    $i = 0;
    $subtotal = 0;
    foreach ($cart->getContents() as $cart_item) {
      $item = $this->moduleHandler->invoke($cart_item->data->module, 'uc_cart_display', [$cart_item]);
      if (Element::children($item)) {
        $form['items'][$i]['remove'] = $item['remove'];
        $form['items'][$i]['remove']['#name'] = 'remove-' . $i;
        $form['items'][$i]['remove']['#wrapper_attributes'] = ['class' => ['remove']];
        $form['items'][$i]['image'] = uc_product_get_picture($item['nid']['#value'], 'uc_cart');
        $form['items'][$i]['image']['#wrapper_attributes'] = ['class' => ['image']];
        $form['items'][$i]['desc']['title'] = $item['title'];
        $form['items'][$i]['desc']['description'] = $item['description'];
        $form['items'][$i]['desc']['#wrapper_attributes'] = ['class' => ['desc']];
        $form['items'][$i]['qty'] = $item['qty'];
        $form['items'][$i]['qty']['#wrapper_attributes'] = ['class' => ['qty']];
        $form['items'][$i]['total'] = [
          '#theme' => 'uc_price',
          '#price' => $item['#total'],
          '#wrapper_attributes' => ['class' => ['price']],
        ];
        if (!empty($item['#suffixes'])) {
          $form['items'][$i]['total']['#suffixes'] = $item['#suffixes'];
        }

        $form['data'][$i]['module'] = $item['module'];
        $form['data'][$i]['nid'] = $item['nid'];
        $form['data'][$i]['data'] = $item['data'];
        $form['data'][$i]['title'] = [
          '#type' => 'value',
          // $item['title'] can be either #markup or #type => 'link',
          // so render it.
          '#value' => drupal_render($item['title']),
        ];

        $subtotal += $item['#total'];
      }
      $i++;
    }

    $footer[] = [
      [''],
      [''],
      [
        'data' => [
          '#markup' => $this->t('Subtotal:'),
        ],
        'colspan' => 2,
        'class' => ['subtotal-title'],
      ],
      [
        'data' => [
          '#theme' => 'uc_price',
          '#price' => $subtotal,
        ],
        'class' => ['price'],
      ],
    ];
    $form['items']['#footer'] = $footer;

    $form['actions'] = ['#type' => 'actions'];

    // If the continue shopping element is enabled...
    if (($cs_type = $cart_config->get('continue_shopping_type')) !== 'none') {
      // Add the element to the form based on the element type.
      if ($cart_config->get('continue_shopping_type') == 'link') {
        $form['actions']['continue_shopping'] = [
          '#type' => 'link',
          '#title' => $this->t('Continue shopping'),
          '#url' => Url::fromUri('internal:' . $this->continueShoppingUrl()),
        ];
      }
      elseif ($cart_config->get('continue_shopping_type') == 'button') {
        $form['actions']['continue_shopping'] = [
          '#type' => 'submit',
          '#value' => $this->t('Continue shopping'),
          '#submit' => [[$this, 'submitForm'], [$this, 'continueShopping']],
        ];
      }
    }

    // Add the empty cart button if enabled.
    if ($cart_config->get('empty_button')) {
      $form['actions']['empty'] = [
        '#type' => 'submit',
        '#value' => $this->t('Empty cart'),
        '#submit' => [[$this, 'emptyCart']],
      ];
    }

    // Add the control buttons for updating and proceeding to checkout.
    $form['actions']['update'] = [
      '#type' => 'submit',
      '#name' => 'update-cart',
      '#value' => $this->t('Update cart'),
      '#submit' => [[$this, 'submitForm'], [$this, 'displayUpdateMessage']],
    ];
    $form['actions']['checkout'] = [
      '#theme' => 'uc_cart_checkout_buttons',
    ];
    if ($cart_config->get('checkout_enabled')) {
      $form['actions']['checkout']['checkout'] = [
        '#type' => 'submit',
        '#value' => $this->t('Checkout'),
        '#button_type' => 'primary',
        '#submit' => [[$this, 'submitForm'], [$this, 'checkout']],
      ];
    }

    $this->renderer->addCacheableDependency($form, $cart);
    $this->renderer->addCacheableDependency($form, $cart_config);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // If a remove button was clicked, set the quantity for that item to 0.
    $triggering_element = $form_state->getTriggeringElement();
    if (substr($triggering_element['#name'], 0, 7) == 'remove-') {
      $item = substr($triggering_element['#name'], 7);
      $form_state->setValue(['items', $item, 'qty'], 0);
      $this->messenger()->addMessage($this->t('<strong>@product</strong> removed from your shopping cart.', ['@product' => $form['data'][$item]['title']['#value']]));
    }

    // Update the items in the shopping cart based on the form values, but only
    // if a qty has changed.
    foreach ($form_state->getValue('items') as $key => $item) {
      if (isset($form['items'][$key]['qty']['#default_value']) && $form['items'][$key]['qty']['#default_value'] != $item['qty']) {
        $this->moduleHandler->invoke($item['module'], 'uc_update_cart_item', [$item['nid'], unserialize($item['data']), $item['qty']]);
      }
    }

    // Invalidate the cart order.
    $this->session->set('uc_cart_order_rebuild', TRUE);
  }

  /**
   * Displays "cart updated" message for the cart form.
   */
  public function displayUpdateMessage(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addMessage($this->t('Your cart has been updated.'));
  }

  /**
   * Continue shopping redirect for the cart form.
   */
  public function continueShopping(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirectUrl(Url::fromUri('base:' . $this->continueShoppingUrl()));
  }

  /**
   * Empty cart redirect for the cart form.
   */
  public function emptyCart(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('uc_cart.empty');
  }

  /**
   * Checkout redirect for the cart form.
   */
  public function checkout(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('uc_cart.checkout');
  }

  /**
   * Returns the URL redirect for the continue shopping element.
   *
   * @return string
   *   The URL that will be used for the continue shopping element.
   */
  protected function continueShoppingUrl() {
    $cart_config = $this->config('uc_cart.settings');
    $url = '';

    // Use the last URL if enabled and available.
    if ($cart_config->get('continue_shopping_use_last_url') && $this->session->has('uc_cart_last_url')) {
      $url = $this->session->get('uc_cart_last_url');
    }

    // If the URL is still empty, fall back to the default.
    if (empty($url)) {
      $url = $cart_config->get('continue_shopping_url');
    }

    $this->session->remove('uc_cart_last_url');

    return $url;
  }

}
