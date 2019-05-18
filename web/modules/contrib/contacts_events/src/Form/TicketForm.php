<?php

namespace Drupal\contacts_events\Form;

use Drupal\contacts_events\Element\AjaxUpdate;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Ticket edit forms.
 *
 * @ingroup contacts_events
 */
class TicketForm extends ContentEntityForm {

  /**
   * The ticket entity.
   *
   * @var \Drupal\contacts_events\Entity\TicketInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\contacts_events\Entity\Ticket */
    $form = parent::buildForm($form, $form_state);

    // Add our update handler for the price.
    static::addPriceAjax($form, ['::rebuildForm']);

    return $form;
  }

  /**
   * Add the price update ajax handler.
   *
   * @param array $form
   *   The entity form.
   * @param array $submit_handlers
   *   An array of submit handlers.
   * @param string|null $unique_suffix
   *   A unique suffix for the update name and wrapper ID.
   */
  public static function addPriceAjax(array &$form, array $submit_handlers, $unique_suffix = NULL) {
    // If the price map isn't on the form, we wont do anything.
    if (!isset($form['mapped_price'])) {
      return;
    }

    // Add our update handler for the price.
    $price_update = AjaxUpdate::createElement();
    $name = 'price_update' . ($unique_suffix ? "[{$unique_suffix}]" : '');
    $form['price_update'] = &$price_update->getRenderArray($name);
    $form['price_update']['#submit'] = $submit_handlers;

    // Register the mapped price to be updated.
    $form['mapped_price']['#id'] = 'ticket-price-ajax-wrapper' . ($unique_suffix ? '-' . $unique_suffix : '');
    $price_update->registerElementToUpdate($form['mapped_price']);
    $price_update->registerElementToRespondTo($form['mapped_price']['widget'][0]['class'], [], $form['mapped_price']['widget']['#parents']);

    if (isset($form['mapped_price']['widget'][0]['class_overridden'])) {
      $price_update->registerElementToRespondTo($form['mapped_price']['widget'][0]['class_overridden'], [], FALSE);
    }

    if (isset($form['mapped_price']['widget'][0]['class_full'])) {
      $price_update->registerElementToRespondTo($form['mapped_price']['widget'][0]['class_full'], [], FALSE);
    }

    // Register the date of birth to trigger an update.
    if (isset($form['date_of_birth'])) {
      $options = [
        'event' => 'delayed.change',
        'no_disable' => TRUE,
        'disable-refocus' => TRUE,
      ];
      $price_update->registerElementToRespondTo($form['date_of_birth']['widget'][0]['value'], $options, $form['date_of_birth']['widget']['#parents']);
      $form['date_of_birth']['widget'][0]['value']['#attached']['library'][] = 'contacts_events/delayed_events';
      // Mark the date value element as #ajax_processed as #ajax is copied to
      // the children, resulting in a double submission.
      $form['date_of_birth']['widget'][0]['value']['#ajax_processed'] = TRUE;
    }

    // Register the price override to trigger an update.
    if (isset($form['price_override'])) {
      $options = [
        'event' => 'delayed.keyup',
        'no_disable' => TRUE,
        'disable-refocus' => TRUE,
      ];
      $price_update->registerElementToRespondTo($form['price_override']['widget'][0], $options, $form['price_override']['widget']['#parents']);
      $form['date_of_birth']['widget'][0]['value']['#attached']['library'][] = 'contacts_events/delayed_events';
    }

    // Register the email address to trigger an update.
    if (isset($form['email'])) {
      $options = [
        'event' => 'change',
        'no_disable' => TRUE,
        'disable-refocus' => TRUE,
      ];
      $price_update->registerElementToRespondTo($form['email']['widget'][0]['value'], $options, $form['email']['widget']['#parents']);
    }
  }

  /**
   * Form submission handler to rebuild the form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function rebuildForm(array &$form, FormStateInterface $form_state) {
    $this->submitForm($form, $form_state);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\contacts_events\Entity\TicketInterface $entity */
    $entity = parent::buildEntity($form, $form_state);

    // Run an early acquisition, as ticket classes may respond to the contact.
    $entity->acquire(TRUE);

    // Get the order item, ensuring our working ticket is set on it.
    $order_item = $entity->getOrderItem();
    $order_item->set('purchased_entity', $entity);

    // Recalculate the price and mapping.
    \Drupal::service('contacts_events.price_calculator')->calculatePrice($order_item);

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    // @todo: Handle the create scenario or prevent it entirely.

    // Save the order item.
    $entity->getOrderItem()->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Ticket.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Ticket.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.contacts_ticket.canonical', ['contacts_ticket' => $entity->id()]);
  }

}
