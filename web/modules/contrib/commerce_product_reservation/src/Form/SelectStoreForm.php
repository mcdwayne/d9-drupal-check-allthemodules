<?php

namespace Drupal\commerce_product_reservation\Form;

use Drupal\commerce_product_reservation\CartAdder;
use Drupal\commerce_product_reservation\Exception\AvailabilityException;
use Drupal\commerce_product_reservation\Exception\NoStockResultException;
use Drupal\commerce_product_reservation\Exception\OutOfStockException;
use Drupal\commerce_product_reservation\ReservationStorePluginManager;
use Drupal\commerce_product_reservation\SelectedStoreManager;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a Commerce Product reservation form.
 */
class SelectStoreForm extends FormBase {

  /**
   * Selected store manager.
   *
   * @var \Drupal\commerce_product_reservation\SelectedStoreManager
   */
  protected $selectedStoreManager;

  /**
   * Reservation store manager.
   *
   * @var \Drupal\commerce_product_reservation\ReservationStorePluginManager
   */
  protected $storeManager;

  /**
   * Current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request|null
   */
  protected $currentRequest;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * ETM.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Cart adder.
   *
   * @var \Drupal\commerce_product_reservation\CartAdder
   */
  protected $cartAdder;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * SelectStoreForm constructor.
   */
  public function __construct(SelectedStoreManager $selected_store_manager, ReservationStorePluginManager $reservation_store, RequestStack $request_stack, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager, CartAdder $cartAdder, LoggerInterface $logger, Messenger $messenger) {
    $this->selectedStoreManager = $selected_store_manager;
    $this->storeManager = $reservation_store;
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->cartAdder = $cartAdder;
    $this->logger = $logger;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_product_reservation.selected_store'),
      $container->get('plugin.manager.reservation_store'),
      $container->get('request_stack'),
      $container->get('module_handler'),
      $container->get('entity_type.manager'),
      $container->get('commerce_product_reservation.cart_adder'),
      $container->get('logger.factory')->get('commerce_product_reservation'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_product_reservation_select_store';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $sku = NULL, $quantity = NULL) {
    $store_stores = $this->storeManager->getDefinitions();
    foreach ($store_stores as $id => $store_provider_definition) {
      /** @var \Drupal\commerce_product_reservation\ReservationStoreInterface $store_provider */
      $store_provider = $this->storeManager->createInstance($id);
      if (!$store_provider) {
        continue;
      }
      $stores = $store_provider->getStores();
      $stock_result = [];
      if ($sku) {
        $stock_result = $store_provider->getStockByStoresAndProducts($stores, [$sku]);
        $form['sku'] = [
          '#type' => 'hidden',
          '#value' => $sku,
        ];
        $form['quantity'] = [
          '#type' => 'hidden',
          '#value' => $quantity,
        ];
      }
      foreach ($stores as $store) {
        $submit_value = $this->t('Select store');
        $wrapper_id = sprintf('%s_%s', $id, $store->getId());
        $form[$wrapper_id] = [
          '#prefix' => '<div class="reservation-select-store-wrapper">',
          '#suffix' => '</div>',
        ];
        $form[$wrapper_id]['name'] = [
          '#markup' => $store->getName(),
        ];
        $disabled = FALSE;
        if ($sku) {
          /** @var \Drupal\commerce_product_reservation\StockResult $store_result */
          $store_result = $this->getStoreStockFromResult($store->getId(), $stock_result);
          $in_stock = $store_result && $store_result->getStock();
          $disabled = !$in_stock;
          $form[$wrapper_id]['status'] = [
            '#markup' => $in_stock ? $this->t('In stock') : $this->t('Out of stock'),
          ];
        }
        $form[$wrapper_id]['submit'] = [
          '#attributes' => [
            'data-provider-id' => $id,
            'disabled' => $disabled,
            'data-store-id' => $store->getId(),
            'data-sku' => $sku,
          ],
          '#name' => $wrapper_id,
          '#submit' => [[$this, 'selectStore']],
          '#disabled' => $disabled,
          '#type' => 'submit',
          '#value' => $submit_value,
          '#ajax' => [
            'callback' => [$this, 'ajaxCallback'],
            'url' => new Url('commerce_product_reservation.select_store'),
            'options' => ['query' => ['ajax_form' => 1]],
          ],
        ];
      }
    }
    return $form;
  }

  /**
   * Helper.
   */
  protected function getStoreStockFromResult($store_id, array $stock_result) {
    /** @var \Drupal\commerce_product_reservation\StockResult $item */
    foreach ($stock_result as $item) {
      if ($item->getStoreId() == $store_id) {
        return $item;
      }
    }
  }

  /**
   * The ajax callback.
   */
  public function ajaxCallback($form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    // Now, if we have a SKU value, try to use that to add this to the cart.
    $input = $form_state->getUserInput();
    if (!empty($input['sku'])) {
      $sku = $input["sku"];
      try {
        $item = $this->cartAdder->addBySku($sku, $input["quantity"]);
        $variation = $item->getPurchasedEntity();
        $response->addCommand(new OpenModalDialogCommand('', $this->t('@entity added to <a href=":url">your cart</a>.', [
          '@entity' => $variation->label(),
          ':url' => Url::fromRoute('commerce_cart.page')->toString(),
          '@variation_id' => $variation->id(),
        ])));
        $form_state->setValue('reservation_order_item', $item);
        $this->moduleHandler->alter('commerce_product_reservation_store_select_ajax_response', $response);
        return $response;
      }
      catch (\InvalidArgumentException $e) {
        // This could happen if there was no selected store. Which should not
        // happen.
        $this->logger->error('Caught exception when trying to add item to reservation cart. Message was: @msg', [
          '@msg' => $e->getMessage(),
          '@trace' => $e->getTraceAsString(),
        ]);
        $this->messenger->addError($this->t('No selected store found. Please try to select a store and try again.'));
      }
      catch (NoStockResultException $e) {
        // This can happen if no plugins take control over the stock request,
        // and tell us how things are looking. Which is probably either an
        // error, or a problem with a third party.
        $this->logger->error('Caught exception when trying to add item to reservation cart. Message was: @msg', [
          '@msg' => $e->getMessage(),
          '@trace' => $e->getTraceAsString(),
        ]);
        $this->messenger->addError($this->t('There was an error checking the stock status for the selected product. Please try again later.'));
      }
      catch (OutOfStockException $e) {
        // This is allowed to happen of course. But it means the product was
        // never added to the cart.
        $this->logger->error('Caught exception when trying to add item to reservation cart. Message was: @msg', [
          '@msg' => $e->getMessage(),
          '@trace' => $e->getTraceAsString(),
        ]);
        $this->messenger->addError($this->t('The maximum allowed quantity for @variation is @max', [
          '@max' => $e->getMaxQuantity(),
          '@variation' => $sku,
        ]));
      }
      catch (AvailabilityException $e) {
        // This can happen if one of the availability plugins denies the
        // request.
        $this->logger->error('Caught exception when trying to add item to reservation cart. Message was: @msg', [
          '@msg' => $e->getMessage(),
          '@trace' => $e->getTraceAsString(),
        ]);
        $this->messenger->addError($this->t('There was an error trying to add the item to the cart. Please try again later.'));
      }
      catch (\Exception $e) {
        $this->logger->error('Caught exception when trying to add item to reservation cart. Message was: @msg', [
          '@msg' => $e->getMessage(),
        ]);
        $this->messenger->addStatus($this->t('There was a problem adding the item to your reservation'));
      }
    }
    $dialog = new OpenModalDialogCommand($this->t('Error'), [
      '#type' => 'status_messages',
    ]);
    $response->addCommand($dialog);
    return $response;
  }

  /**
   * Handles the selection of a store.
   */
  public function selectStore($form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    if (empty($element["#attributes"]["data-provider-id"]) || empty($element["#attributes"]["data-store-id"])) {
      return;
    }
    $store_id = $element["#attributes"]["data-store-id"];
    $store_provider_id = $element["#attributes"]["data-provider-id"];
    if (!$store = $this->storeManager->getStoreByStoreProviderAndId($store_provider_id, $store_id)) {
      return;
    }
    $this->selectedStoreManager->setSelectedStore($store_provider_id, $store_id);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
