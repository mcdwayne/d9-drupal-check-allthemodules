<?php

namespace Drupal\commerce_decoupled_checkout\Plugin\rest\resource;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayInterface;
use Drupal\commerce_price\Price;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

/**
 * Provides a resource for decoupled orders creation.
 *
 * @RestResource(
 *   id = "commerce_decoupled_checkout_order_create",
 *   label = @Translation("Commerce Order create"),
 *   uri_paths = {
 *     "create" = "/commerce/order/create"
 *   }
 * )
 */
class OrderCreateResource extends ResourceBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Default value for customer profile type.
   *
   * @var string
   */
  protected $defaultProfileType = 'customer';

  /**
   * Default value for commerce order type.
   *
   * @var string
   */
  protected $defaultOrderType = 'default';

  /**
   * Default value for commerce order item type.
   *
   * @var string
   */
  protected $defaultOrderItemType = 'default';

  /**
   * Constructs a new object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, EntityTypeManagerInterface $entity_type_manager, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->entityTypeManager = $entity_type_manager;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('commerce_decoupled_checkout'),
      $container->get('entity_type.manager'),
      $container->get('datetime.time')
    );
  }

  /**
   * @param array $data
   *  $data = [
   *
   *    'order' => [
   *      'type' => 'default', // optional. Order bundle name. Defaults to "default".
   *      'email' => 'customer@example.com', // optional. Defaults to user email.
   *      'store' => 1, // optional. Store ID. Defaults to the default store in the system.
   *      'field_name' => 'value', // optional. Any additional order field value.
   *      'order_items' => [ // optional.
   *        [
   *          'type' => 'default', // optional. Order item bundle name. Defaults to "default".
   *          'title' => '', // optional, defaults to referenced purchasable entity label.
   *          'quantity' => 1, // optional. Defaults to 1.
   *          'unit_price' => [ // optional. Only if need to override product price. Defaults to purchased_entity price * quantity.
   *            'number' => 5, // required if unit_price is defined.
   *            'currency_code' => // required if unit_price is defined.
   *          ],
   *          'purchased_entity' => [ // required if order_items is defined.
   *            'sku' => 'PRODUCT_SKU', // required. Product variation SKU.
   *          ],
   *          'field_name' => 'value', // optional. Any additional order item field value.
   *        ],
   *      ],
   *    ],
   *    // User profile associated with the order.
   *    'profile' => [
   *      'type' => 'customer', // optional. Profile bundle name. Defaults to "customer".
   *      'status' => FALSE, // optional. Activates profile after creation. Defaults to FALSE.
   *      'field_name' => 'value', // optional. Any additional profile field value.
   *    ],
   *    // A user account associated with the transaction.
   *    // Creates a new user if didn't not exist, or uses existing one.
   *    // In the second case fields WILL NOT be updated.
   *    'user' => [
   *      'mail' => 'user@example.com', // required.
   *      'name' => 'Kate',  // optional. User account name. Defaults to email value.
   *      'status' => FALSE, // optional. Actives user account after creation. Defaults to FALSE.
   *      'field_name' => 'value', // optional. Any additional user field value.
   *    ],
   *    // If you want to process the payment alongside with order submission,
   *    // then fill in the details of this field. Otherwise you can skip it
   *    // and use other REST endpoints to handle payments separately.
   *    'payment' => [
   *      'gateway' => 'paypal_test', // required. Commerce Payment Gateway name.
   *      'type' => 'paypal_ec', // required. Commerce Payment Type name.
   *      'details' => [], // optional. Payment details associated with the payment.
   *    ],
   *  ];
   *
   * @return \Drupal\rest\ResourceResponse
   *   Normalized commerce order entity.
   */
  public function post(array $data) {

    // Validate incoming data.
    $this->validateInput($data);

    // Prepare all necessary entities.
    $user = $this->getUser($data);
    $profile = $this->getProfile($data, $user);
    $order_items = $this->getOrderItems($data);
    $order = $this->getOrder($data, $user, $profile, $order_items);

    // Processes payment details if they exist in the payload.
    $this->processPayment($data, $order);

    // TODO: Create secure token for further orders process?
    return new ResourceResponse($order, 201);
  }

  /**
   * Validates data sent by a client.
   *
   * @param array $data
   *    User input data. See ::post() description for more info.
   */
  protected function validateInput(array $data) {
    try {

      // Make sure input contains user email.
      if (empty($data['user']['mail'])) {
        throw new \Exception($this->t('Validation error: User mail is required.'));
      }

      if (!empty($data['order']['order_items'])) {
        foreach ($data['order']['order_items'] as $key => $order_item) {

          // Make sure every order item has product SKU defined.
          if (empty($order_item['purchased_entity']['sku'])) {
            throw new \Exception($this->t('Validation error: SKU of purchased_entity is required for each order item.'));
          }

          // Make sure all referenced purchased entities actually exist.
          /** @var \Drupal\commerce_product\Entity\ProductVariationInterface[] $product_variations */
          $product_variations = $this->entityTypeManager->getStorage('commerce_product_variation')
            ->loadByProperties(['sku' => $order_item['purchased_entity']['sku']]);
          if (empty($product_variations)) {
            throw new \Exception($this->t('Could not load product variation with SKU @sku', [
              '@sku' => $order_item['purchased_entity']['sku'],
            ]));
          }
        }
      }

      // If payment data is supplied, then it should contain several mandatory
      // fields.
      if (!empty($data['payment'])) {

        // Make sure payment gateway is not empty in the payload
        if (empty($data['payment']['gateway'])) {
          throw new \Exception($this->t('Validation error: Payment gateway is required.'));
        }

        // Make sure payment method type is not empty in the payload.
        if (empty($data['payment']['type'])) {
          throw new \Exception($this->t('Validation error: Payment method type is required.'));
        }

        // Make sure payment gateway exists in the system.
        $payment_gateway = $this->entityTypeManager->getStorage('commerce_payment_gateway')
          ->load($data['payment']['gateway']);
        if (empty($payment_gateway)) {
          throw new \Exception($this->t('Validation error: Payment gateway "@gateway" does not exist.', [
            '@gateway' => $data['payment']['gateway'],
          ]));
        }
      }
    } catch (\Exception $exception) {
      $this->logger->error($exception->getMessage());
      throw new NotAcceptableHttpException($exception->getMessage());
    }
  }

  /**
   * Creates a new or reuses existing user account.
   *
   * @param array $data
   *   User input data. See ::post() description for more info.
   *
   * @return bool|\Drupal\user\UserInterface|object
   *   User entity.
   */
  protected function getUser(array $data) {
    try {
      $user = user_load_by_mail($data['user']['mail']);
      if (empty($user)) {

        // Use email instead of user account name if it is empty.
        $data['user']['name'] = !empty($data['user']['name']) ? $data['user']['name'] : $data['user']['mail'];

        // Unset potentially unsafe values.
        foreach (['init', 'roles', 'pass', 'created', 'access', 'login'] as $field) {
          unset($data['user'][$field]);
        }

        /** @var \Drupal\user\UserInterface $user */
        $user = $this->entityTypeManager->getStorage('user')
          ->create($data['user']);

        // Make sure the user account entity is valid before saving.
        $this->validateEntity($user);

        $user->save();
      }
    } catch (\Exception $exception) {
      $message = $this->t('Could not prepare User object: @message', [
        '@message' => $exception->getMessage()
      ]);
      $this->logger->error($message);
      throw new BadRequestHttpException($message);
    }

    return $user;
  }

  /**
   * Creates a new or reuses existing user profile.
   *
   * @param array $data
   *   User input data. See ::post() description for more info.
   *
   * @param \Drupal\user\UserInterface $user
   *   User entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\profile\Entity\ProfileInterface
   *   Profile entity.
   */
  protected function getProfile(array $data, UserInterface $user) {
    try {

      // Make sure profile type is set.
      $data['profile']['type'] = !empty($data['profile']['type']) ? $data['profile']['type'] : $this->defaultProfileType;

      // Set profile's owner.
      $data['profile']['uid'] = $user->id();

      /** @var \Drupal\profile\Entity\ProfileTypeInterface $profile_type */
      $profile_type = $this->entityTypeManager->getStorage('profile_type')
        ->load($data['profile']['type']);

      // Make sure profile type exists.
      if (empty($profile_type)) {
        $message = $this->t('Profile type @type does not exist.', ['@type' => $data['profile']['type']]);
        throw new \Exception($message);
      }

      // If the profile type does not support multiple profiles, then we
      // should reuse existing profile (if available).
      if (!$profile_type->getMultiple()) {
        $profiles = $this->entityTypeManager->getStorage('profile')->loadByProperties([
          'type' => $data['profile']['type'],
          'uid' =>  $data['profile']['uid']
        ]);

        if (!empty($profiles)) {
          return reset($profiles);
        }
      }

      /** @var \Drupal\profile\Entity\ProfileInterface $profile */
      $profile = $this->entityTypeManager->getStorage('profile')
        ->create($data['profile']);

      // Make sure the user profile entity is valid before saving.
      $this->validateEntity($profile);

      $profile->save();
    } catch (\Exception $exception) {
      $message = $this->t('Could not prepare Profile object: @message', [
        '@message' => $exception->getMessage()
      ]);
      $this->logger->error($message);
      throw new BadRequestHttpException($message);
    }

    return $profile;
  }

  /**
   * Creates a list of order items for the order.
   *
   * @param array $data
   *   User input data. See ::post() description for more info.
   *
   * @return \Drupal\commerce_order\Entity\OrderItemInterface[]
   */
  protected function getOrderItems(array $data) {
    try {

      /** @var \Drupal\commerce_order\Entity\OrderItemInterface[] $order_items */
      $order_items = [];

      if (empty($data['order']['order_items'])) {
        return $order_items;
      }

      foreach ($data['order']['order_items'] as $key => $order_item) {

        /** @var \Drupal\commerce_product\Entity\ProductVariationInterface[] $product_variations */
        $product_variations = $this->entityTypeManager->getStorage('commerce_product_variation')
          ->loadByProperties(['sku' => $order_item['purchased_entity']['sku']]);
        $product_variation = reset($product_variations);

        // Prepare default order item fields.
        $order_item['type'] = !empty($order_item['type']) ? $order_item['type'] : $this->defaultOrderItemType;
        $order_item['quantity'] = !empty($order_item['quantity']) ? $order_item['quantity'] : 1;
        $order_item['title'] = !empty($order_item['title']) ? $order_item['title'] : $product_variation->label();
        $order_item['purchased_entity'] = $product_variation;

        // Prepare a new Price object if order item should override the default
        // price calculated from quantity & product price.
        if (!empty($order_item['unit_price'])) {
          $unit_price = Price::fromArray($order_item['unit_price']);
          unset($order_item['unit_price']);
        }

        // Make sure order item type exists.
        $commerce_order_item_type = $this->entityTypeManager->getStorage('commerce_order_item_type')
          ->load($order_item['type']);
        if (empty($commerce_order_item_type)) {
          $message = $this->t('Order Item type @type does not exist.', ['@type' => $order_item['type']]);
          throw new \Exception($message);
        }

        $order_items[$key] = $this->entityTypeManager->getStorage('commerce_order_item')
          ->create($order_item);

        // Override unit price if it was set from the frontend.
        // TODO: Permission to do so?
        if (!empty($unit_price)) {
          $order_items[$key]->setUnitPrice($unit_price, TRUE);
        }

        // Make sure the order item entity is valid before saving.
        $this->validateEntity($order_items[$key]);

        // Save recently created commerce order item entity.
        $order_items[$key]->save();
      }
    } catch (\Exception $exception) {
      $message = $this->t('Could not prepare Order Items object: @message', [
        '@message' => $exception->getMessage()
      ]);
      $this->logger->error($message);
      throw new BadRequestHttpException($message);
    }

    return $order_items;
  }

  /**
   * Creates a new commerce order.
   *
   * @param array $data
   *   User input data. See ::post() description for more info.
   *
   * @param \Drupal\user\UserInterface $user
   *   User entity.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   Profile entity.
   *
   * @param array $order_items
   *   Array of commerce order items.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   Created commerce order entity.
   */
  protected function getOrder(array $data, UserInterface $user, ProfileInterface $profile, array $order_items) {
    try {

      // Make sure order type is set.
      $data['order']['type'] = !empty($data['order']['type']) ? $data['order']['type'] : $this->defaultOrderType;
      $data['order']['email'] = !empty($data['order']['email']) ? $data['order']['email'] : $user->getEmail();
      $data['order']['order_items'] = $order_items;
      $data['order']['billing_profile'] = $profile;
      $data['order']['uid'] = $user->id();
      $data['order']['placed'] = $this->time->getRequestTime();

      if (!empty($data['order']['store'])) {
        $data['order']['store_id'] = $data['order']['store'];
        unset($data['order']['store']);
      }
      else {
        /** @var \Drupal\commerce_store\Entity\StoreInterface $default_store */
        $default_store = $this->entityTypeManager->getStorage('commerce_store')
          ->loadDefault();
        $data['order']['store_id'] = $default_store->id();
      }

      // Make sure order type exists.
      $commerce_order_type = $this->entityTypeManager->getStorage('commerce_order_type')
        ->load($data['order']['type']);
      if (empty($commerce_order_type)) {
        $message = $this->t('Order type @type does not exist.', ['@type' => $data['order']['type']]);
        throw new \Exception($message);
      }

      /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
      $order = $this->entityTypeManager->getStorage('commerce_order')
        ->create($data['order']);

      // Make sure the order entity is valid before saving.
      $this->validateEntity($order);

      // Save the order object to the database.
      $order->save();

      // If order number was not populated from the frontend, then use order id
      // to populate it.
      if (!$order->getOrderNumber()) {
        $order->setOrderNumber($order->id());
        $order->save();
      }
    } catch (\Exception $exception) {
      $message = $this->t('Could not prepare Order object: @message', [
        '@message' => $exception->getMessage()
      ]);
      $this->logger->error($message);
      throw new BadRequestHttpException($message);
    }

    return $order;
  }

  /**
   * Processes payment for the order.
   *
   * @param array $data
   *   User input data. See ::post() description for more info.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   Order entity.
   */
  protected function processPayment(array $data, OrderInterface $order) {
    try {

      // Do not process payment is it does not exist in the payload.
      if (empty($data['payment'])) {
        return;
      }

      // Make sure payment details are not empty.
      $data['payment']['details'] = !empty($data['payment']['details']) ? $data['payment']['details'] : [];

      // Load payment gateway specified in the payload.
      /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
      $payment_gateway = $this->entityTypeManager->getStorage('commerce_payment_gateway')
        ->load($data['payment']['gateway']);

      // Create a new payment method based on payment info from the payload.
      /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
      $payment_method = $this->entityTypeManager->getStorage('commerce_payment_method')
        ->create([
          'payment_gateway' => $payment_gateway->id(),
          'type' => $data['payment']['type'],
        ]);

      // Set user and profile info for payment method.
      $payment_method->setOwner($order->getCustomer());
      $payment_method->setBillingProfile($order->getBillingProfile());

      /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayInterface $payment_gateway_plugin */
      $payment_gateway_plugin = $payment_gateway->getPlugin();

      // Make sure the payment is onsite payment. Otherwise not sure how can we
      // support it.
      if (!$payment_gateway_plugin instanceof OnsitePaymentGatewayInterface) {
        throw new \Exception($this->t('The payment gateway is not onsite payment and therefore not supported.'));
      }

      $payment_gateway_plugin->createPaymentMethod($payment_method, $data['payment']['details']);

      /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
      $payment = $this->entityTypeManager->getStorage('commerce_payment')
        ->create([
          'amount' => $order->getTotalPrice(),
          'payment_gateway' => $payment_gateway->id(),
          'order_id' => $order->id(),
          'payment_method' => $payment_method,
        ]);

      // Create & capture payment.
      $payment_gateway_plugin->createPayment($payment);

      // Add payment details to the order.
      $order->payment_gateway = $payment->getPaymentGatewayId();
      $order->payment_method = $payment->getPaymentMethodId();

      // Complete the order if the payment went through.
      $payment_state = $payment->getState();
      if ($payment_state->value == 'completed') {
        $order_state = $order->getState();
        $order_state_transitions = $order_state->getTransitions();
        if (!empty($order_state_transitions['place'])) {
          $order_state->applyTransition($order_state_transitions['place']);
        }

        // Add total paid amount.
        $order->setTotalPaid($order->getTotalPrice());
      }

      // Finally save all changes to the order.
      $order->save();

    } catch (\Exception $exception) {
      $message = $this->t('Could not process payment: @message', [
        '@message' => $exception->getMessage()
      ]);
      $this->logger->error($message);
      throw new BadRequestHttpException($message);
    }
  }

  /**
   * Checks entity violations (validation).
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity object.
   *
   * @throws \Exception
   */
  protected function validateEntity(ContentEntityInterface $entity) {
    /** @var \Drupal\Core\Entity\EntityConstraintViolationListInterface $violations */
    $violations = $entity->validate()->getEntityViolations();
    if ($violations->count() > 0) {
      foreach ($violations as $violation) {
        $message = $this->t('@entity validation error: @message', [
          '@entity' => $entity->getEntityTypeId(),
          '@message' => $violation->getMessage(),
        ]);
        throw new \Exception($message);
      }
    }
  }

}
