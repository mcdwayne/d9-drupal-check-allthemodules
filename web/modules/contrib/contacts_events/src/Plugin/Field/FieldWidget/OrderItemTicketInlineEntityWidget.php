<?php

namespace Drupal\contacts_events\Plugin\Field\FieldWidget;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowBase;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\inline_entity_form\Plugin\Field\FieldWidget\InlineEntityFormBase;
use Drupal\inline_entity_form\Plugin\Field\FieldWidget\InlineEntityFormComplex;

/**
 * Inline widget for tickets.
 *
 * @FieldWidget(
 *   id = "inline_entity_form_order_item_tickets",
 *   label = @Translation("Booking Tickets"),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = true
 * )
 */
class OrderItemTicketInlineEntityWidget extends InlineEntityFormComplex {

  /**
   * {@inheritdoc}
   */
  protected function getTargetBundles() {
    // Don't allow creation of any other order item type other than ticket.
    return ['contacts_ticket'];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return ['form_mode' => 'booking'] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $element['allow_new']['#access'] = FALSE;
    $element['allow_existing']['#access'] = FALSE;
    $element['match_operator']['#access'] = FALSE;
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return InlineEntityFormBase::settingsSummary();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Set up a few bits early so we can pre-open a specific order item.
    if ($early_open = $form_state->get('inline_entity_form_order_item_tickets')) {
      $parents = array_merge($element['#field_parents'], [$items->getName(), 'form']);
      $this->setIefId(sha1(implode('-', $parents)));
      // Only act if entities aren't already initialised.
      $location = ['inline_entity_form', $this->getIefId(), 'entities'];
      if ($form_state->get($location) === NULL) {
        $this->prepareFormState($form_state, $items, $this->isTranslating($form_state));
        $entities = $form_state->get($location);
        foreach ($entities as $delta => $entity) {
          if ($entity['entity']->id() == $early_open['id']) {
            $location[] = $delta;
            $location[] = 'form';
            $form_state->set($location, $early_open['op']);
            break;
          }
        }
      }
    }

    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Disable the table drag as we don't want that.
    $element['entities']['#disable_tabledrag'] = TRUE;

    // @todo: How to handle non ticket entities, for now throw an exception.
    if ($items->offsetExists($delta) && $items[$delta]->entity->bundle() != 'contacts_ticket') {
      throw new \InvalidArgumentException('Non ticket order items not yet supported.');
    }

    // Override the field title as we are only dealing with tickets.
    $element['#field_title'] = $this->t('Tickets');

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function buildEntityFormActions($element) {
    $element = parent::buildEntityFormActions($element);
    foreach (Element::children($element['actions']) as $key) {
      $element['actions'][$key]['#submit'][] = [static::class, 'clearTicketFromState'];
    }
    return $element;
  }

  /**
   * Submission callback to clear the ticket from the form state.
   *
   * @param array $entity_form
   *   The entity form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function clearTicketFromState(array &$entity_form, FormStateInterface $form_state) {
    $form_state->set('ticket', NULL);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityTypeLabels() {
    // The admin has specified the exact labels that should be used.
    if ($this->getSetting('override_labels')) {
      return [
        'singular' => $this->getSetting('label_singular'),
        'plural' => $this->getSetting('label_plural'),
      ];
    }
    else {
      return [
        'singular' => $this->t('ticket'),
        'plural' => $this->t('tickets'),
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // Only allow for order items on the contacts booking bundle of orders.
    return $field_definition->getTargetEntityTypeId() == 'commerce_order'
      && $field_definition->getTargetBundle() == 'contacts_booking'
      && $field_definition->getName() == 'order_items';
  }

  /**
   * {@inheritdoc}
   */
  public static function submitSaveEntity($entity_form, FormStateInterface $form_state) {
    // TicketInlineForm::save handles saving new order items, but we need to
    // make sure we track the correct item, so always pull from the ticket form.
    $ticket_form = $entity_form['purchased_entity']['widget'][0]['inline_entity_form'];
    $order_item = $ticket_form['#entity']->getOrderItem();
    $entity_form['#entity'] = $order_item;

    parent::submitSaveEntity($entity_form, $form_state);

    $form_object = $form_state->getFormObject();
    // The form object may be a CheckoutFlowBase object which does not extend
    // FormBase so needs another method for accessing the order.
    /* @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $form_object instanceof CheckoutFlowBase ? $form_object->getOrder() : $form_object->getEntity();

    // We're explicitly updating the order items, so skip refreshing. Otherwise
    // we get a double save and the entity in the form is out of date.
    $order->setRefreshState(OrderInterface::REFRESH_SKIP);

    // Ensure the item is added to the order and the total recalculated.
    $order->addItem($order_item)
      ->save();
  }

}
