<?php

namespace Drupal\commerce_product_reservation;

use Drupal\commerce_product_reservation\Exception\AvailabilityException;
use Drupal\commerce_product_reservation\Exception\NoStockResultException;
use Drupal\commerce_product_reservation\Exception\OutOfStockException;
use Drupal\commerce_product_reservation\Form\SelectStoreForm;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * CartFormHandler service.
 */
class CartFormHandler {

  use StringTranslationTrait;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Messenger.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Selected store.
   *
   * @var \Drupal\commerce_product_reservation\SelectedStoreManager
   */
  protected $selectedStoreManager;

  /**
   * Reservation manager.
   *
   * @var \Drupal\commerce_product_reservation\ReservationStorePluginManager
   */
  protected $reservationManager;

  /**
   * Current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request|null
   */
  protected $currentRequest;

  /**
   * Form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Cart adder.
   *
   * @var \Drupal\commerce_product_reservation\CartAdder
   */
  protected $cartAdder;

  /**
   * CartFormHandler constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactoryInterface $logger_factory, MessengerInterface $messenger, SelectedStoreManager $selected_store, ReservationStorePluginManager $reservation_manager, RequestStack $request_stack, FormBuilderInterface $form_builder, ModuleHandlerInterface $module_handler, CartAdder $cartAdder) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger_factory->get('commerce_product_reservation');
    $this->messenger = $messenger;
    $this->selectedStoreManager = $selected_store;
    $this->reservationManager = $reservation_manager;
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->formBuilder = $form_builder;
    $this->moduleHandler = $module_handler;
    $this->cartAdder = $cartAdder;
  }

  /**
   * Handle the cart form.
   *
   * Add another button there.
   */
  public function handleCartFormAlter(&$form, FormStateInterface $form_state, $form_id) {
    // First see if we have any plugins to use at all.
    $ids = $this->reservationManager->getDefinitions();
    if (empty($ids)) {
      return;
    }
    $submit = [$this, 'submitForm'];
    if (!$this->selectedStoreManager->getSelectedStore()) {
      $submit = [$this, 'redirectToSelectStore'];
    }
    $form['actions']['reservation'] = [
      '#value' => $this->t('Reserve in store'),
      '#type' => 'submit',
      '#submit' => [$submit],
      '#validate' => [[$this, 'validateForm']],
      '#ajax' => [
        'callback' => [$this, 'ajaxCallback'],
      ],
      '#attached' => [
        'library' => [
          'core/drupal.dialog.ajax',
        ],
      ],
    ];
  }

  /**
   * Ajax callback.
   *
   * Handles these cases:
   *
   * - There was an error.
   * - The user has not yet selected a store,
   * - The thing was actually added to the cart.
   */
  public function ajaxCallback($form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    if ($form_state->hasAnyErrors() || $form_state->get('error')) {
      $dialog = new OpenModalDialogCommand($this->t('Error'), [
        '#type' => 'status_messages',
      ]);
      $response->addCommand($dialog);
      return $response;
    }
    // This seems hacky.
    if (!$this->selectedStoreManager->getSelectedStore()) {
      $values = $form_state->getValues();
      // Todo: What do we do if it is not a product variation entity?
      if (empty($values["purchased_entity"][0]["variation"]) || empty($values["quantity"][0]["value"])) {
        // Nothing to do.
        throw new \Exception('No quantity or item id found');
      }
      $variation_storage = $this->entityTypeManager->getStorage('commerce_product_variation');
      $entity_id = $values["purchased_entity"][0]["variation"];
      /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
      if (!$variation = $variation_storage->load($entity_id)) {
        throw new \Exception('No variation could be loaded from the entity id');
      }
      $quantity = 1;
      if (!empty($values["quantity"][0]["value"])) {
        $quantity = $values["quantity"][0]["value"];
      }
      $dialog = new OpenModalDialogCommand($this->t('Select store'), $this->formBuilder->getForm(SelectStoreForm::class, $variation->getSku(), $quantity), [
        'classes' => [
          'ui-dialog' => 'reservation-select-store-dialog',
        ],
      ]);
      $response->addCommand($dialog);
      $this->moduleHandler->alter('commerce_product_reservation_ajax_form_dialog_response', $response);
      return $response;
    }
    $dialog = new OpenModalDialogCommand($this->t('Status'), $form_state->get('status_message'));
    $response->addCommand($dialog);
    $this->moduleHandler->alter('commerce_product_reservation_ajax_dialog_success_response', $response);
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm($form, FormStateInterface $form_state) {
    // @todo: Probably validate that it's possible to reserve in any store at
    // all.
  }

  /**
   * No-js fallback.
   */
  public function redirectToSelectStore($form, FormStateInterface $form_state) {
    if ($this->currentRequest->isXmlHttpRequest()) {
      // We will open it in a modal.
      return;
    }
    $form_state->setRedirect('commerce_product_reservation.select_store');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm($form, FormStateInterface $form_state) {
    // If we have no selected store, we do not have any way of adding the
    // correct info on the order.
    if (!$this->selectedStoreManager->getSelectedStore()) {
      if ($this->currentRequest->isXmlHttpRequest()) {
        return;
      }
      $form_state->setRedirect('commerce_product_reservation.select_store');
      return;
    }
    $values = $form_state->getValues();
    $store_id = $this->selectedStoreManager->getSelectedStore();
    try {
      // Todo: What do we do if it is not a product variation entity?
      if (empty($values["purchased_entity"][0]["variation"]) || empty($values["quantity"][0]["value"])) {
        // Nothing to do.
        throw new \Exception('No quantity or item id found');
      }
      $entity_id = $values["purchased_entity"][0]["variation"];
      $variation_storage = $this->entityTypeManager->getStorage('commerce_product_variation');
      $variation = $variation_storage->load($entity_id);
      $quantity = $values["quantity"][0]["value"];
      $order_item = $this->cartAdder->addEntity($variation, $quantity);
      $form_state->set('status_message', $this->t('@entity added to <a href=":url">your cart</a>.', [
        '@entity' => $variation->label(),
        ':url' => Url::fromRoute('commerce_cart.page')->toString(),
        '@variation_id' => $variation->id(),
      ]));
      $order = $order_item->getOrder();
      if ($order->hasField('reservation_store_id')) {
        $order->set('reservation_store_id', $store_id->getId());
        $order->save();
      }
      $form_state->setValue('reservation_order_item', $order_item);
      return;
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
      // This can happen if no plugins take control over the stock request, and
      // tell us how things are looking. Which is probably either an error, or a
      // problem with a third party.
      $this->logger->error('Caught exception when trying to add item to reservation cart. Message was: @msg', [
        '@msg' => $e->getMessage(),
        '@trace' => $e->getTraceAsString(),
      ]);
      $this->messenger->addError($this->t('There was an error checking the stock status for the selected product. Please try again later.'));
    }
    catch (OutOfStockException $e) {
      // This is allowed to happen of course. But it means the product was never
      // added to the cart.
      $this->logger->error('Caught exception when trying to add item to reservation cart. Message was: @msg', [
        '@msg' => $e->getMessage(),
        '@trace' => $e->getTraceAsString(),
      ]);
      $this->messenger->addError($this->t('The maximum allowed quantity for @variation is @max', [
        '@max' => $e->getMaxQuantity(),
        '@variation' => $variation->label(),
      ]));
    }
    catch (AvailabilityException $e) {
      // This can happen if one of the availability plugins denies the request.
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
    // We should only end up here if there was an exception.
    $form_state->set('error', $e->getMessage());
  }

}
