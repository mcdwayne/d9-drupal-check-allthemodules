<?php

namespace Drupal\uc_cart_links\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\uc_cart\CartManagerInterface;
use Drupal\uc_cart_links\CartLinksValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Preprocesses a cart link, confirming with the user for destructive actions.
 */
class CartLinksForm extends ConfirmFormBase {

  /**
   * The cart link actions.
   *
   * @var string
   */
  protected $actions;

  /**
   * The cart manager.
   *
   * @var \Drupal\uc_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * The uc_cart_links.validator service.
   *
   * @var \Drupal\uc_cart_links\CartLinksValidatorInterface
   */
  protected $cartLinksValidator;

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
   * The datetime.time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $dateTime;

  /**
   * Form constructor.
   *
   * @param \Drupal\uc_cart\CartManagerInterface $cart_manager
   *   The cart manager.
   * @param \Drupal\uc_cart_links\CartLinksValidatorInterface $cart_links_validator
   *   The uc_cart_links.validator service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session.
   * @param \Drupal\Component\Datetime\TimeInterface $date_time
   *   The datetime.time service.
   */
  public function __construct(CartManagerInterface $cart_manager, CartLinksValidatorInterface $cart_links_validator, ModuleHandlerInterface $module_handler, SessionInterface $session, TimeInterface $date_time) {
    $this->cartManager = $cart_manager;
    $this->cartLinksValidator = $cart_links_validator;
    $this->moduleHandler = $module_handler;
    $this->session = $session;
    $this->dateTime = $date_time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('uc_cart.manager'),
      $container->get('uc_cart_links.validator'),
      $container->get('module_handler'),
      $container->get('session'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_cart_links_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('The current contents of your shopping cart will be lost. Are you sure you want to continue?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'uc_cart_links.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $actions = NULL) {
    $cart_links_config = $this->config('uc_cart_links.settings');

    $this->actions = $actions;

    // Fail if the link is restricted.
    $data = trim($cart_links_config->get('restrictions'));
    if (!empty($data)) {
      $restrictions = explode("\n", $cart_links_config->get('restrictions'));
      $restrictions = array_map('trim', $restrictions);

      if (!empty($restrictions) && !in_array($this->actions, $restrictions)) {
        // A destination from the request's query will always override the
        // current RedirectResponse.
        $this->getRequest()->query->remove('destination');
        $path = $cart_links_config->get('invalid_page');
        if (empty($path)) {
          return $this->redirect('<front>');
        }
        return new RedirectResponse(Url::fromUri('internal:/' . $path, ['absolute' => TRUE])->toString());
      }
    }

    // Confirm with the user if the form contains a destructive action.
    $cart = $this->cartManager->get();
    $items = $cart->getContents();
    if ($cart_links_config->get('empty') && !empty($items)) {
      $actions = explode('-', urldecode($this->actions));
      foreach ($actions as $action) {
        $action = mb_substr($action, 0, 1);
        if ($action == 'e' || $action == 'E') {
          $form = parent::buildForm($form, $form_state);
          $form['actions']['cancel']['#href'] = $cart_links_config->get('invalid_page');
          return $form;
        }
      }
    }

    // No destructive actions, so process the link immediately.
    return $this->submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    //if (!uc_cart_links_is_valid_syntax('/cart/add/' . $this->actions)) {
    //  Don't process cart link, but log it so admin knows bad link was passed.
    //}
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cart_links_config = $this->config('uc_cart_links.settings');

    $actions = explode('-', urldecode($this->actions));
    $messages = [];
    $id = $this->t('(not specified)');

    $cart = $this->cartManager->get();
    foreach ($actions as $action) {
      switch (mb_substr($action, 0, 1)) {
        // Set the ID of the Cart Link.
        case 'i':
        case 'I':
          $id = mb_substr($action, 1, 32);
          break;

        // Add a product to the cart.
        case 'p':
        case 'P':
          // Set the default product variables.
          $p = ['nid' => 0, 'qty' => 1, 'data' => []];
          $msg = TRUE;

          // Parse the product action to adjust the product variables.
          $parts = explode('_', $action);
          foreach ($parts as $part) {
            switch (mb_substr($part, 0, 1)) {
              // Set the product node ID: p23
              case 'p':
              case 'P':
                $p['nid'] = intval(mb_substr($part, 1));
                break;

              // Set the quantity to add to cart: _q2
              case 'q':
              case 'Q':
                $p['qty'] = intval(mb_substr($part, 1));
                break;

              // Set an attribute/option for the product: _a3o6
              case 'a':
              case 'A':
                $attribute = intval(mb_substr($part, 1, stripos($part, 'o') - 1));
                $option = (string) mb_substr($part, stripos($part, 'o') + 1);
                if (!isset($p['attributes'][$attribute])) {
                  $p['attributes'][$attribute] = $option;
                }
                else {
                  // Multiple options for this attribute implies checkbox
                  // attribute, which we must store as an array.
                  if (is_array($p['attributes'][$attribute])) {
                    // Already an array, just append this new option
                    $p['attributes'][$attribute][$option] = $option;
                  }
                  else {
                    // Set but not an array, means we already have at least one
                    // option, so put that into an array with this new option.
                    $p['attributes'][$attribute] = [
                      $p['attributes'][$attribute] => $p['attributes'][$attribute],
                      $option => $option,
                    ];
                  }
                }
                break;

              // Suppress the add to cart message: _s
              case 's':
              case 'S':
                $msg = FALSE;
                break;
            }
          }

          // Add the item to the cart, suppressing the default redirect.
          if ($p['nid'] > 0 && $p['qty'] > 0) {
            // If it's a product kit, we need black magic to make everything
            // work right. In other words, we have to simulate FAPI's form
            // values.
            $node = Node::load($p['nid']);
            // Ensure product is 'published'.
            if ($node->status) {
              if (isset($node->products) && is_array($node->products)) {
                foreach ($node->products as $nid => $product) {
                  $p['data']['products'][$nid] = [
                    'nid' => $nid,
                    'qty' => $product->qty,
                  ];
                }
              }
              $cart->addItem($p['nid'], $p['qty'], $p['data'] + $this->moduleHandler->invokeAll('uc_add_to_cart_data', [$p]), $msg);
            }
            else {
              $this->logger('uc_cart_link')->error('Cart Link on %url tried to add an unpublished product to the cart.', ['%url' => $this->getRequest()->server->get('HTTP_REFERER')]);
            }
          }
          break;

        // Empty the shopping cart.
        case 'e':
        case 'E':
          if ($cart_links_config->get('empty')) {
            $cart->emptyCart();
          }
          break;

        // Display a pre-configured message.
        case 'm':
        case 'M':
          // Load the messages if they haven't been loaded yet.
          if (empty($messages)) {
            $data = explode("\n", $cart_links_config->get('messages'));
            foreach ($data as $message) {
              // Skip blank lines.
              if (preg_match('/^\s*$/', $message)) {
                continue;
              }
              list($mkey, $mdata) = explode('|', $message, 2);
              $messages[trim($mkey)] = trim($mdata);
            }
          }

          // Parse the message key and display it if it exists.
          $mkey = intval(mb_substr($action, 1));
          if (!empty($messages[$mkey])) {
            $this->messenger()->addMessage($messages[$mkey]);
          }
          break;
      }
    }

    if ($cart_links_config->get('track')) {
      db_merge('uc_cart_link_clicks')
        ->key(['cart_link_id' => (string) $id])
        ->fields([
          'clicks' => 1,
          'last_click' => $this->dateTime->getRequestTime(),
        ])
        ->expression('clicks', 'clicks + :i', [':i' => 1])
        ->execute();
    }

    $this->session->set('uc_cart_last_url', $this->getRequest()->server->get('HTTP_REFERER'));

    $query = $this->getRequest()->query;
    if ($query->has('destination')) {
      $options = UrlHelper::parse($query->get('destination'));
      $path = $options['path'];
    }
    else {
      $path = 'cart';
      $options = [];
    }
    $options += ['absolute' => TRUE];

    // Form redirect is for confirmed links.
    return new RedirectResponse(Url::fromUri('base:' . $path, $options)->toString());
  }

}
