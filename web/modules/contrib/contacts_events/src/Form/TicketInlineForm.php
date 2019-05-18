<?php

namespace Drupal\contacts_events\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\contacts_events\PriceCalculator;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\inline_entity_form\Element\InlineEntityForm;
use Drupal\inline_entity_form\Form\EntityInlineForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The inline form for Ticket entities.
 */
class TicketInlineForm extends EntityInlineForm {

  /**
   * The price calculator service.
   *
   * @var \Drupal\contacts_events\PriceCalculator
   */
  protected $priceCalculator;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, EntityTypeInterface $entity_type, PriceCalculator $price_calculator) {
    parent::__construct($entity_field_manager, $entity_type_manager, $module_handler, $entity_type);
    $this->priceCalculator = $price_calculator;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $entity_type,
      $container->get('contacts_events.price_calculator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function entityForm(array $entity_form, FormStateInterface $form_state) {
    // Get the entity we're building. The item in form state is going to be the
    // most up to date, if it's available.
    if ($ticket = $form_state->get('ticket')) {
      $entity_form['#entity'] = $ticket;
    }
    else {
      $ticket = $entity_form['#entity'];
    }
    /* @var \Drupal\contacts_events\Entity\TicketInterface $ticket */

    // Ensure we have the order item set on the ticket.
    if (!$ticket->getOrderItem()) {
      // Get the order item form element.
      $parents = array_slice($entity_form['#array_parents'], 0, -4);
      $order_item_element = &NestedArray::getValue($form_state->getCompleteForm(), $parents);
      $ticket->set('order_item', $order_item_element['#entity']);
    }

    $entity_form = parent::entityForm($entity_form, $form_state);

    // Add our update handler for the price.
    TicketForm::addPriceAjax($entity_form, [[static::class, 'rebuildEntity']], $ticket->id() ?? 'new');

    return $entity_form;
  }

  /**
   * Submission handler to rebuild the form with submitted values.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function rebuildEntity(array $form, FormStateInterface $form_state) {
    // Get the entity form.
    $entity_form_parents = $form_state->getTriggeringElement()['#array_parents'];
    array_pop($entity_form_parents);
    $entity_form = NestedArray::getValue($form, $entity_form_parents);

    // Get the ahndler and trigger the rebuild.
    $inline_form_handler = InlineEntityForm::getInlineFormHandler($entity_form['#entity_type']);
    $inline_form_handler->buildEntity($entity_form, $entity_form['#entity'], $form_state);

    // Run an early acquisition, as ticket classes may respond to the contact.
    $entity_form['#entity']->acquire(TRUE);

    // Recalculate the price of the ticket using the form handler.
    // @todo: Something to optimize this and prevent it being calculated
    // mulitple time in a single request when nothing changes - perhaps an
    // onChange in the item and ticket?
    $inline_form_handler->priceCalculator->calculatePrice($entity_form['#entity']->getOrderItem());

    // Put the most up to date version into the form state and ensure the order
    // item is up to date.
    $form_state->set('ticket', $entity_form['#entity']);

    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function save(EntityInterface $entity) {
    /* @var \Drupal\contacts_events\Entity\TicketInterface $entity */
    $order_item = $entity->getOrderItem();

    // Update the order item title.
    $order_item->set('title', $entity->getOrderItemTitle());

    // New ticket and order items reference each other and end in a loop. To
    // avoid that, clear the purchased entity off the order item, save it,
    // restore it and then save again.
    // OrderItemTicketInlineEntityWidget::submitSaveEntity ensures the correct
    // order item is tracked in the form.
    if ($entity->isNew() && $order_item && $order_item->isNew()) {
      // Also set the title, as doing it this way results in it not being
      // properly set.
      $order_item
        ->set('purchased_entity', NULL)
        ->save();

      $order_item
        ->set('purchased_entity', $entity)
        ->save();
    }
    // If it's not new, we can simply save.
    elseif ($order_item) {
      $order_item->save();
    }

    // Ensure the right order item entity is on the ticket.
    $entity->setOrderItem($order_item);

    // Now we can proceed to save the ticket.
    $entity->save();
  }

}
