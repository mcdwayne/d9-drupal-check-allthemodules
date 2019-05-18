<?php

namespace Drupal\commerce_webform_order\Plugin\WebformHandler;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Render\Markup;
use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_checkout\CheckoutOrderManagerInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Resolver\OrderTypeResolverInterface;
use Drupal\commerce_price\Price;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Creates a commerce order with a webform submission.
 *
 * @WebformHandler(
 *   id = "commerce_webform_order",
 *   label = @Translation("Commerce Webform Order Handler"),
 *   category = @Translation("External"),
 *   description = @Translation("Creates a commerce order with a webform submission."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class CommerceWebformOrderHandler extends WebformHandlerBase {

  use DependencySerializationTrait;

  /**
   * The created cart order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $cart;

  /**
   * The cart manager.
   *
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * The checkout order manager.
   *
   * @var \Drupal\commerce_checkout\CheckoutOrderManagerInterface
   */
  protected $checkoutOrderManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The order type resolver.
   *
   * @var \Drupal\commerce_order\Resolver\OrderTypeResolverInterface
   */
  protected $orderTypeResolver;

  /**
   * Turns a render array into a HTML string.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The webform token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * The webform element manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $webformElementManager;

  /**
   * CommerceWebformOrderHandler constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\webform\WebformSubmissionConditionsValidatorInterface $conditions_validator
   *   The webform submission conditions (#states) validator.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Turns a render array into a HTML string.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\commerce_cart\CartManagerInterface $cart_manager
   *   The cart manager.
   * @param \Drupal\commerce_cart\CartProviderInterface $cart_provider
   *   The cart provider.
   * @param \Drupal\commerce_order\Resolver\OrderTypeResolverInterface $order_type_resolver
   *   The order type resolver.
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $webform_element_manager
   *   The webform element manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack object.
   * @param \Drupal\commerce_checkout\CheckoutOrderManagerInterface $checkout_order_manager
   *   The checkout order manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   * @param \Drupal\webform\WebformTokenManagerInterface $token_manager
   *   The webform token manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, WebformSubmissionConditionsValidatorInterface $conditions_validator, RendererInterface $renderer, AccountInterface $current_user, CartManagerInterface $cart_manager, CartProviderInterface $cart_provider, OrderTypeResolverInterface $order_type_resolver, WebformElementManagerInterface $webform_element_manager, EntityFieldManagerInterface $entity_field_manager, RouteMatchInterface $route_match, RequestStack $request_stack, CheckoutOrderManagerInterface $checkout_order_manager, TimeInterface $time, WebformTokenManagerInterface $token_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger_factory, $config_factory, $entity_type_manager, $conditions_validator);

    $this->cartManager = $cart_manager;
    $this->cartProvider = $cart_provider;
    $this->checkoutOrderManager = $checkout_order_manager;
    $this->requestStack = $request_stack;
    $this->currentUser = $current_user;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->orderTypeResolver = $order_type_resolver;
    $this->renderer = $renderer;
    $this->routeMatch = $route_match;
    $this->time = $time;
    $this->tokenManager = $token_manager;
    $this->webformElementManager = $webform_element_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory'),
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('webform_submission.conditions_validator'),
      $container->get('renderer'),
      $container->get('current_user'),
      $container->get('commerce_cart.cart_manager'),
      $container->get('commerce_cart.cart_provider'),
      $container->get('commerce_order.chain_order_type_resolver'),
      $container->get('plugin.manager.webform.element'),
      $container->get('entity_field.manager'),
      $container->get('current_route_match'),
      // We want to  use the current request, but we can not use it directly
      // $container->get('request_stack')->getCurrentRequest() because it is not
      // a serializable object.
      $container->get('request_stack'),
      $container->get('commerce_checkout.checkout_order_manager'),
      $container->get('datetime.time'),
      $container->get('webform.token_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'store' => [
        'store_entity' => NULL,
      ],
      'order_item' => [
        'product_variation_entity' => NULL,
        'title' => NULL,
        'overwrite_price' => FALSE,
        'amount' => NULL,
        'currency' => NULL,
        'quantity' => 1,
        'order_item_bundle' => NULL,
        'fields' => [],
      ],
      'checkout' => [
        'empty_cart' => TRUE,
        'owner' => NULL,
        'hide_add_to_cart_message' => FALSE,
        'redirect' => TRUE,
      ],
      'states' => [WebformSubmissionInterface::STATE_COMPLETED],
      'debug' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Apply submitted form state settings to configuration.
    $this->applyFormStateToConfiguration($form_state);

    // Get #options array of webform elements.
    $webform_elements = $this->getElements();

    $form['tabs_wrapper'] = [
      '#type' => 'fieldset',
    ];

    $form['tabs_wrapper']['tabs'] = [
      '#type' => 'vertical_tabs',
      '#title_display' => FALSE,
      '#default_tab' => 'edit-store',
    ];

    // Settings: Store.
    $form['tabs_wrapper']['store'] = [
      '#type' => 'details',
      '#title' => $this->t('Store'),
      '#group' => 'settings][tabs_wrapper][tabs',
    ];
    $form['tabs_wrapper']['store']['store_entity'] = [
      '#type' => 'webform_select_other',
      '#title' => $this->t('Store'),
      '#description' => $this->t('The Store ID or Name. Empty for default store.'),
      '#required' => empty($this->configFactory->get('commerce_store.settings')->get('default_store')),
      '#options' => $webform_elements,
      '#default_value' => $this->configuration['store']['store_entity'],
      '#empty_option' => $this->t('- Select -'),
      '#parents' => [
        'settings',
        'store',
        'store_entity',
      ],
      '#other__type' => 'entity_autocomplete',
      '#other__target_type' => 'commerce_store',
      '#other__maxlength' => 2500,
    ];
    // If there is only a store use it as default value.
    if ($this->configuration['store']['store_entity'] === NULL) {
      $stores = $this->entityTypeManager
        ->getStorage('commerce_store')
        ->getQuery()
        ->range(0, 2)
        ->execute();

      if (count($stores) == 1) {
        $form['tabs_wrapper']['store']['store_entity']['#default_value'] = reset($stores);
      }
      elseif (($default_store = $this->entityTypeManager->getStorage('commerce_store')->loadDefault()) !== NULL) {
        $form['tabs_wrapper']['store']['store_entity']['#default_value'] = $default_store;
      }
    }

    // Settings: Order item.
    $form['tabs_wrapper']['order_item'] = [
      '#type' => 'details',
      '#title' => $this->t('Order item'),
      '#group' => 'settings][tabs_wrapper][tabs',
    ];

    $form['tabs_wrapper']['order_item']['product_variation_entity'] = [
      '#type' => 'webform_select_other',
      '#title' => $this->t('Product variation'),
      '#description' => $this->t('The product variation ID or SKU of the order item.'),
      '#suffix' => '<hr />',
      '#required' => TRUE,
      '#options' => $webform_elements,
      '#default_value' => $this->configuration['order_item']['product_variation_entity'],
      '#empty_option' => $this->t('- Select -'),
      '#parents' => [
        'settings',
        'order_item',
        'product_variation_entity',
      ],
      '#other__type' => 'entity_autocomplete',
      '#other__target_type' => 'commerce_product_variation',
      '#other__maxlength' => 2500,
    ];

    $form['tabs_wrapper']['order_item']['title'] = [
      '#type' => 'webform_select_other',
      '#title' => $this->t('Title'),
      '#description' => $this->t('The title of the order item.'),
      '#suffix' => '<hr />',
      '#options' => $webform_elements,
      '#default_value' => $this->configuration['order_item']['title'],
      '#empty_value' => NULL,
      '#empty_option' => $this->t('Use the product variation selected'),
      '#parents' => [
        'settings',
        'order_item',
        'title',
      ],
      '#other__type' => 'textfield',
      '#other__maxlength' => 2500,
    ];

    $form['tabs_wrapper']['order_item']['amount'] = [
      '#type' => 'webform_select_other',
      '#title' => $this->t('Amount'),
      '#description' => $this->t('The unit price of the order item.'),
      '#suffix' => '<hr />',
      '#options' => $webform_elements,
      '#default_value' => $this->configuration['order_item']['amount'],
      '#empty_value' => NULL,
      '#empty_option' => $this->t('Use the product variation selected'),
      '#parents' => [
        'settings',
        'order_item',
        'amount',
      ],
      '#other__type' => 'number',
      '#other__min' => 0,
    ];

    $form['tabs_wrapper']['order_item']['currency'] = [
      '#type' => 'webform_select_other',
      '#title' => $this->t('Currency'),
      '#description' => $this->t('The currency code, name or numeric code.'),
      '#suffix' => '<hr />',
      '#options' => $webform_elements,
      '#default_value' => $this->configuration['order_item']['currency'],
      '#empty_value' => NULL,
      '#empty_option' => $this->t('Use the product variation selected'),
      '#parents' => [
        'settings',
        'order_item',
        'currency',
      ],
      '#other__type' => 'webform_entity_select',
      '#other__target_type' => 'commerce_currency',
      '#other__selection_handler' => 'default',
      '#other__maxlength' => 2500,
    ];

    $form['tabs_wrapper']['order_item']['quantity'] = [
      '#type' => 'webform_select_other',
      '#title' => $this->t('Quantity'),
      '#description' => $this->t('The units of the order item.'),
      '#suffix' => '<hr />',
      '#options' => $webform_elements,
      '#default_value' => $this->configuration['order_item']['quantity'] == 1 ? NULL : $this->configuration['order_item']['quantity'],
      '#empty_value' => 1,
      '#empty_option' => $this->t('One item'),
      '#parents' => [
        'settings',
        'order_item',
        'quantity',
      ],
      '#other__type' => 'number',
    ];

    // @TODO: Use AJAX to reload order item bundle fields on product variation change.
    $form['tabs_wrapper']['order_item']['order_item_bundle'] = [
      '#type' => 'webform_entity_select',
      '#title' => $this->t('Bundle'),
      '#description' => $this->t('The order item fields.'),
      '#suffix' => '<hr />',
      '#required' => TRUE,
      '#target_type' => 'commerce_order_item_type',
      '#selection_handler' => 'default',
      '#default_value' => $this->configuration['order_item']['order_item_bundle'],
      '#empty_value' => NULL,
      '#empty_option' => $this->t('- Select -'),
      '#parents' => [
        'settings',
        'order_item',
        'order_item_bundle',
      ],
    ];

    $order_items = $this->getOrderItemBundles();
    foreach ($order_items as $order_item_id => $order_item) {
      /** @var \Drupal\field\Entity\FieldConfig $field */
      foreach ($order_item['fields'] as $field_id => $field) {
        $form['tabs_wrapper']['order_item'][$order_item_id][$field_id] = [
          '#type' => 'webform_select_other',
          '#title' => $field->label(),
          '#description' => $field->getDescription(),
          '#suffix' => '<hr />',
          '#options' => $webform_elements,
          '#default_value' => isset($this->configuration['order_item']['fields'][$order_item_id][$field_id]) ? $this->configuration['order_item']['fields'][$order_item_id][$field_id] : NULL,
          '#empty_option' => $this->t('- Select -'),
          '#parents' => [
            'settings',
            'order_item',
            'fields',
            $order_item_id,
            $field_id,
          ],
          // @TODO: Use the same type of this order item field.
          '#other__type' => 'textfield',
          '#other__maxlength' => 2500,
          '#states' => [
            'visible' => [
              ':input[name="settings[order_item][order_item_bundle]"]' => ['value' => $order_item_id],
            ],
          ],
        ];

        // Mark it as required if the order item field is required.
        if ($field->isRequired()) {
          $form['tabs_wrapper']['order_item'][$order_item_id][$field_id]['#states']['required'] = [
            ':input[name="settings[order_item][order_item_bundle]"]' => ['value' => $order_item_id],
          ];
        }
      }
    }

    // Settings: Checkout.
    $form['tabs_wrapper']['checkout'] = [
      '#type' => 'details',
      '#title' => $this->t('Checkout'),
      '#group' => 'settings][tabs_wrapper][tabs',
    ];
    $form['tabs_wrapper']['checkout']['empty_cart'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Empty the current cart order'),
      '#description' => $this->t('If checked, current users cart will be emptied.'),
      '#suffix' => '<hr />',
      '#return_value' => TRUE,
      '#parents' => ['settings', 'checkout', 'empty_cart'],
      '#default_value' => $this->configuration['checkout']['empty_cart'],
    ];

    $form['tabs_wrapper']['checkout']['owner'] = [
      '#type' => 'webform_select_other',
      '#title' => $this->t("Owner's e-mail"),
      '#description' => $this->t("The owner's e-mail of the order."),
      '#suffix' => '<hr />',
      '#options' => $webform_elements,
      '#default_value' => $this->configuration['checkout']['owner'],
      '#empty_value' => NULL,
      '#empty_option' => $this->t('- Select -'),
      '#parents' => [
        'settings',
        'checkout',
        'owner',
      ],
      '#other__type' => 'textfield',
      '#other__maxlength' => 2500,
    ];

    $form['tabs_wrapper']['checkout']['hide_add_to_cart_message'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide the add to cart message'),
      '#description' => $this->t('If checked, add to cart message will be removed.'),
      '#return_value' => TRUE,
      '#parents' => [
        'settings',
        'checkout',
        'hide_add_to_cart_message',
      ],
      '#default_value' => $this->configuration['checkout']['hide_add_to_cart_message'],
    ];

    $form['tabs_wrapper']['checkout']['redirect'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Redirect to the checkout page'),
      '#description' => $this->t('If checked, current user will be redirected to the checkout page after submit this webform.'),
      '#return_value' => TRUE,
      '#parents' => [
        'settings',
        'checkout',
        'redirect',
      ],
      '#default_value' => $this->configuration['checkout']['redirect'],
    ];

    // Additional.
    $results_disabled = $this->getWebform()->getSetting('results_disabled');
    $form['additional'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Additional settings'),
    ];
    // Settings: States.
    $form['additional']['states'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Create order'),
      '#options' => [
        WebformSubmissionInterface::STATE_DRAFT => $this->t('…when <b>draft</b> is saved.'),
        WebformSubmissionInterface::STATE_CONVERTED => $this->t('…when anonymous submission is <b>converted</b> to authenticated.'),
        WebformSubmissionInterface::STATE_COMPLETED => $this->t('…when submission is <b>completed</b>.'),
        WebformSubmissionInterface::STATE_UPDATED => $this->t('…when submission is <b>updated</b>.'),
        WebformSubmissionInterface::STATE_DELETED => $this->t('…when submission is <b>deleted</b>.'),
      ],
      '#parents' => [
        'settings',
        'states',
      ],
      '#access' => $results_disabled ? FALSE : TRUE,
      '#default_value' => $results_disabled ? [WebformSubmissionInterface::STATE_COMPLETED] : $this->configuration['states'],
    ];

    // Settings: Debug.
    $form['development'] = [
      '#type' => 'details',
      '#title' => $this->t('Debug'),
      '#open' => FALSE,
    ];
    $form['development']['enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debugging'),
      '#description' => $this->t('If checked, created orders will be displayed onscreen to all users.'),
      '#return_value' => TRUE,
      '#parents' => [
        'settings',
        'debug',
      ],
      '#default_value' => $this->configuration['debug'],
    ];

    $form['token_tree_link'] = $this->tokenManager->buildTreeLink(
      ['webform', 'webform_submission'],
      $this->t('Use [webform_submission:values:ELEMENT_KEY:raw] to get plain text values.')
    );

    // ISSUE: TranslatableMarkup is breaking the #ajax.
    // WORKAROUND: Convert all Render/Markup to strings.
    WebformElementHelper::convertRenderMarkupToStrings($form);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValues();

    // Cleanup states.
    $values['states'] = array_values(array_filter($values['states']));

    foreach ($this->configuration as $name => $value) {
      if (isset($values[$name])) {
        $this->configuration[$name] = $values[$name];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $state = $webform_submission->getWebform()->getSetting('results_disabled') ? WebformSubmissionInterface::STATE_COMPLETED : $webform_submission->getState();
    if ($this->configuration['states'] && in_array($state, $this->configuration['states'])) {
      try {
        // Collect data from the handler and the webform submission.
        $data = $this->prepareData($webform_submission);

        /** @var \Drupal\commerce_order\OrderStorage $order_item_storage */
        $order_item_storage = $this->entityTypeManager->getStorage('commerce_order_item');

        // Create the order item.
        /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
        $order_item = $order_item_storage->create([
          'type' => $data['order_item_bundle'],
          'title' => $data['title'],
          'unit_price' => $data['price'],
          'overridden_unit_price' => TRUE,
          'quantity' => (string) $data['quantity'],
          'purchased_entity' => $data['product_variation'],
          'commerce_webform_order_submissions' => $webform_submission->id(),
        ]);

        // Add non BaseFieldDefinition field values.
        foreach ($data['order_item_fields'] as $field => $value) {
          $order_item->set($field, $value);
        }

        // Create or update the cart.
        $order_type_id = $this->orderTypeResolver->resolve($order_item);
        $this->cart = $this->cartProvider->getCart($order_type_id, $data['store']);
        if (!$this->cart) {
          $this->cart = $this->cartProvider->createCart($order_type_id, $data['store']);
        }
        elseif ($this->configuration['checkout']['empty_cart']) {
          $this->cartManager->emptyCart($this->cart);
        }

        // Set the owner and the email if the user is not an anonymous user.
        if ($this->currentUser->isAuthenticated()) {
          $this->cart->setCustomerId($this->currentUser->id());
          $this->cart->setEmail($this->currentUser->getEmail());
        }
        // Set the email.
        elseif (!empty($data['owner_email'])) {
          $this->cart->setEmail($data['owner_email']);
        }

        // Add the order item to the order, and save the order.
        $this->cartManager->addOrderItem($this->cart, $order_item);
        $this->cart->save();

        // Add the reference to the order and save the submission without
        // triggering any hooks or handlers.
        $webform_submission->set('commerce_webform_order_orders', [$this->cart]);
        $webform_submission->resave();

        // Remove the add to cart status message.
        if ($this->configuration['checkout']['hide_add_to_cart_message']) {
          $messages = $this->messenger()->messagesByType('status');
          $this->messenger()->deleteByType('status');
          /** @var \Drupal\Core\Render\Markup $original_message */
          foreach ($messages as $original_message) {
            if ($original_message instanceof Markup) {
              $message = $original_message->__toString();
            }
            else {
              $message = $original_message;
            }

            /* @see \Drupal\commerce_cart\EventSubscriber\CartEventSubscriber::displayAddToCartMessage */
            if (!is_string($message) || preg_match('/.* added to <a href=".*">your cart<\/a>\./', $message) === FALSE) {
              $this->messenger()->addMessage($message, 'status');
            }
          }
        }

        // Log message in Drupal's log.
        $context = [
          '@form' => $this->getWebform()->label(),
          '@title' => $this->label(),
          'link' => $this->getWebform()->toLink($this->t('Edit'), 'handlers')->toString(),
        ];
        $this->getLogger()->notice('@form webform created @title order.', $context);

        // Log message in Webform's submission log.
        $context = [
          '@order_id' => $this->cart->get('order_id')->getString(),
          '@owner_email' => $this->cart->getEmail(),
          'webform_submission' => $webform_submission,
          'handler_id' => $this->getHandlerId(),
          'data' => [],
        ];
        if ($this->cart->getEmail() !== NULL) {
          $this->getLogger('webform_submission')->notice("Order #@order_id created to '@owner_email'.", $context);
        }
        else {
          $this->getLogger('webform_submission')->notice("Order #@order_id created.", $context);
        }

        // Debug by displaying create order onscreen.
        if ($this->configuration['debug']) {
          $t_args = [
            '%order_id' => $this->cart->get('order_id')->getString(),
            '%owner_email' => $this->cart->getEmail(),
          ];
          if ($this->cart->getEmail() !== NULL) {
            $this->messenger()->addWarning($this->t("Order #%order_id created to '%owner_email'.", $t_args), TRUE);
          }
          else {
            $this->messenger()->addWarning($this->t("Order #%order_id created.", $t_args), TRUE);
          }
          $debug_message = $this->buildDebugMessage($this->cart);
          $this->messenger()->addWarning($this->renderer->renderPlain($debug_message), TRUE);
        }
      }
      catch (\Exception $exception) {
        watchdog_exception('commerce_webform_order', $exception);
        $this->messenger()->addWarning($this->t('There was a problem processing your request. Please, try again.'), TRUE);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function confirmForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    try {
      if ($this->configuration['checkout']['redirect']) {
        $url = Url::fromRoute(
          'commerce_checkout.form',
          [
            'commerce_order' => $this->cart->get('order_id')->getString(),
            'step' => NULL,
          ],
          [
            'query' => $this->requestStack->getCurrentRequest()->query->all(),
          ]
        );

        $form_state->setRedirectUrl($url);
      }
    }
    catch (\Exception $exception) {
      watchdog_exception('commerce_webform_order', $exception);
      $this->messenger()->addWarning($this->t('There was a problem processing your request. Please, try again.'), TRUE);
    }
  }

  /**
   * Build debug message.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   A commerce order.
   *
   * @return array
   *   Debug message.
   */
  protected function buildDebugMessage(OrderInterface $order) {
    // Title.
    $build = [
      '#type' => 'details',
      '#title' => $this->t('Debug: Order: @title', ['@title' => $this->label()]),
    ];

    // Values.
    $values = [
      $order->getStore()->getName() => $this->t('Store'),
      $order->get('order_id')->getString() => $this->t('Order ID'),
      'hr1' => '---',
    ];
    if ($order->getEmail() !== NULL) {
      $values += [$order->getEmail() => $this->t("Owner's e-mail")];
    }
    $values += [
      $order->getTotalPrice()->getNumber() => $this->t('Amount'),
      $order->getTotalPrice()->getCurrencyCode() => $this->t('Currency'),
      'hr2' => '---',
    ];

    foreach ($order->getItems() as $key => $order_item) {
      $values[$order_item->getTitle()] = $this->t('Item #@number', ['@number' => $key + 1]);
    }
    foreach ($values as $name => $title) {
      if ($title == '---') {
        $build[$name] = ['#markup' => '<hr />'];
      }
      else {
        $build[$name] = [
          '#type' => 'item',
          '#title' => $title,
          '#markup' => $name,
          '#wrapper_attributes' => [
            'class' => ['container-inline'],
            'style' => 'margin: 0;',
          ],
        ];
      }
    }

    return $build;
  }

  /**
   * Prepare data from the handler and the webform submission.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The webform submission entity.
   *
   * @return array
   *   The prepared data from the handler and the submission.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function prepareData(WebformSubmissionInterface $webform_submission) {
    // Get the handler configuration and replace the values of the mapped
    // elements.
    $data = $this->configuration;
    array_walk_recursive($data, function (&$value) use ($webform_submission) {
      if (strpos($value, 'input:') !== FALSE) {
        list(, $element_key) = explode(':', $value);
        $value = $webform_submission->getElementData($element_key);
      }
      $value = $this->tokenManager->replace($value, $webform_submission, [], ['clear' => TRUE]);
    });

    // Load the entity values, and replace the tokens if they are supported.
    if (empty($data['store']['store_entity'])) {
      $prepared_data['store'] = $this->loadEntityValue(
        $this->configFactory->get('commerce_store.settings')->get('default_store'),
        'commerce_store',
        ['uuid']
      );
    }
    else {
      $prepared_data['store'] = $this->loadEntityValue(
        $data['store']['store_entity'],
        'commerce_store',
        ['store_id', 'name']
      );
    }

    $prepared_data['product_variation'] = $this->loadEntityValue(
      $data['order_item']['product_variation_entity'],
      'commerce_product_variation',
      ['variation_id', 'sku']
    );

    $prepared_data['title'] = $data['order_item']['title'];

    if (empty($data['order_item']['amount'])) {
      $data['order_item']['amount'] = $prepared_data['product_variation']->getPrice()->getNumber();
    }

    if (empty($data['order_item']['currency'])) {
      $data['order_item']['currency'] = $prepared_data['product_variation']->getPrice()->getCurrencyCode();
    }
    else {
      $currency = $this->loadEntityValue(
        $data['order_item']['currency'],
        'commerce_currency',
        ['currencyCode', 'name', 'numericCode']
      );

      $data['order_item']['currency'] = $currency->getCurrencyCode();
    }

    $prepared_data['price'] = new Price($data['order_item']['amount'], $data['order_item']['currency']);

    $prepared_data['quantity'] = $data['order_item']['quantity'];

    $prepared_data['order_item_bundle'] = $data['order_item']['order_item_bundle'];

    $prepared_data['order_item_fields'] = [];
    if (!empty($data['order_item']['fields'][$prepared_data['order_item_bundle']])) {
      foreach ($data['order_item']['fields'][$prepared_data['order_item_bundle']] as $field_key => $field) {
        $prepared_data['order_item_fields'][$field_key] = $field;
      }
    }

    $prepared_data['owner_email'] = $data['checkout']['owner'];

    return $prepared_data;
  }

  /**
   * Prepare #options array of webform elements.
   *
   * @return array
   *   Prepared array of webform elements.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getElements() {
    $elements_options = [];
    foreach ($this->getWebform()->getElementsDecodedAndFlattened() as $key => $element) {
      /* @var \Drupal\webform\Plugin\WebformElementInterface $element_handler */
      $element_handler = $this->webformElementManager->createInstance($element['#type']);
      if ($element_handler->isInput($element)) {
        $title = empty($element['#title']) ? $key : $element['#title'] . " ($key)";
        $elements_options['input:' . $key] = $title;
      }
    }

    return $elements_options;
  }

  /**
   * Prepare array of order item types with its non BaseFieldDefinition fields.
   *
   * @return array
   *   Prepared array of order item types with its fields.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getOrderItemBundles() {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorage $order_item_type_storage */
    $order_item_type_storage = $this->entityTypeManager->getStorage('commerce_order_item_type');

    $order_items = [];
    /** @var \Drupal\commerce_order\Entity\OrderItemType $order_item_type */
    foreach ($order_item_type_storage->loadMultiple() as $order_item_type) {
      if ($order_item_type->getOrderTypeId() != 'recurring') {
        if ($order_item_type->getPurchasableEntityTypeId() !== NULL) {
          $fields = $this->entityFieldManager->getFieldDefinitions('commerce_order_item', $order_item_type->id());
          $base_fields = $this->entityFieldManager->getBaseFieldDefinitions('commerce_order_item');

          $order_items[$order_item_type->id()] = [
            'label' => $order_item_type->label(),
            'fields' => array_diff_key($fields, $base_fields),
          ];
        }
      }
    }

    return $order_items;
  }

  /**
   * Helper method to load entity values.
   *
   * @param mixed $value
   *   The value to load.
   * @param string $entity_type
   *   The entity type id.
   * @param array $properties
   *   A property array to try to load the entity by them.
   *
   * @return mixed
   *   The loaded entity or the input key.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function loadEntityValue($value, $entity_type, array $properties = []) {
    // Return the same value if it is an element value or is empty.
    if (empty($value) || strpos($value, 'input:') !== FALSE) {
      return $value;
    }

    /** @var \Drupal\Core\Entity\EntityStorageInterface $entity_storage */
    $entity_storage = $this->entityTypeManager->getStorage($entity_type);

    // Try to load the entity for each property and return the first
    // occurrence.
    if (!empty($properties)) {
      try {
        $query = $entity_storage->getQuery();

        // Query all conditions.
        $or = $query->orConditionGroup();
        foreach ($properties as $property) {
          $or->condition($property, $value);
        }
        $query->condition($or);
        $query->range(0, 1);

        $entities = $query->execute();
        if (!empty($entities)) {
          $entity = reset($entities);
          return $entity_storage->load($entity);
        }
      }
      catch (\Exception $exception) {
        watchdog_exception('commerce_webform_order', $exception);
      }
    }

    return $value;
  }

}
