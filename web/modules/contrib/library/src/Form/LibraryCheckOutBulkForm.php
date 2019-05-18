<?php

namespace Drupal\library\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\library\Entity\LibraryAction;
use Drupal\library\Entity\LibraryItem;
use Drupal\library\Entity\LibraryTransaction;
use Drupal\library\Event\ActionEvent;
use Drupal\library\Helper\LibraryItemHelper;
use Drupal\node\Entity\Node;

/**
 * Class LibraryCheckOutBulkForm.
 *
 * @package Drupal\library\Form
 */
class LibraryCheckOutBulkForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'library_check_out_bulk_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $action = NULL) {

    if ($action == NULL) {
      $form_state->setErrorByName('action', $this->t('Required parameters missing'));
      return $form;
    }

    $form['action'] = [
      '#type' => 'value',
      '#value' => $action,
    ];

    for ($i = 0; $i < 6; $i++) {
      $form['item_' . $i] = [
        '#type' => 'textfield',
        '#title' => $this->t('Item'),
        '#description' => $this->t('Enter items by barcode'),
        '#maxlength' => 20,
        '#size' => 20,
      ];
    }

    $actionEntity = LibraryAction::load($action);

    if ($actionEntity->action() == LibraryAction::CHANGE_TO_UNAVAILABLE) {
      $form['patron'] = [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'user',
        '#title' => $this->t('Patron'),
        '#required' => TRUE,
      ];
    }

    $form['notes'] = [
      '#type' => 'textarea',
      '#title' => t('Message'),
      '#required' => FALSE,
      '#maxlength' => 250,
      '#default_value' => '',
      '#description' => t('If you are reserving an item, make sure to include the date and time you would like it to be ready.'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $actionEntity->label(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $data = $form_state->getValues();
    foreach ($data as $key => $item) {
      if (strpos($key, 'item_') !== FALSE && !empty($item)) {
        $libraryItem = LibraryItemHelper::findByBarcode($item);
        if ($libraryItem) {
          $itemState = $libraryItem->get('library_status')->value;
          $action = LibraryAction::load($data['action']);
          if ($action->action() == LibraryAction::CHANGE_TO_AVAILABLE) {
            if ($itemState != LibraryItem::ITEM_UNAVAILABLE) {
              $form_state->setErrorByName('item_' . $key, $this->t('Item @item is already checked in.', ['@item' => $item]));
            }
          }
          if ($action->action() == LibraryAction::CHANGE_TO_UNAVAILABLE) {
            if ($itemState != LibraryItem::ITEM_AVAILABLE) {
              $form_state->setErrorByName('item_' . $key, $this->t('Item @item is already checked out.', ['@item' => $item]));
            }
          }
          if ($libraryItem->get('in_circulation')->value == LibraryItem::REFERENCE_ONLY) {
            $form_state->setErrorByName('item_' . $key, $this->t('Item @item cannot be checked out.', ['@item' => $item]));
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $data = $form_state->getValues();
    foreach ($data as $key => $item) {
      if (strpos($key, 'item_') !== FALSE && !empty($item)) {
        $libraryItem = LibraryItemHelper::findByBarcode($item);
        if ($libraryItem) {
          $this->processTransaction($libraryItem, $data);
        }
      }
    }
  }

  /**
   * Process a transaction.
   *
   * @param \Drupal\library\Entity\LibraryItem $item
   *   Item to process.
   * @param array $data
   *   Data context.
   */
  private function processTransaction(LibraryItem $item, array $data) {
    $transaction = LibraryTransaction::create();
    $transaction->set('librarian_id', \Drupal::currentUser()->id());
    $transaction->set('nid', $item->get('nid')->getValue());
    if (isset($data['patron'])) {
      $transaction->set('uid', $data['patron']);
    }
    $transaction->set('library_item', $item->id());
    $transaction->set('action', $data['action']);
    $transaction->set('due_date', LibraryItemHelper::computeDueDate($data['action'], $item->get('nid')->getValue()[0]['target_id']));
    $transaction->set('notes', $data['notes']);
    $transaction->save();

    LibraryItemHelper::updateItemAvailability($item->id(), $data['action']);

    \Drupal::service('cache_tags.invalidator')->invalidateTags(['node:' . $item->get('nid')->getValue()[0]['target_id']]);

    $node = Node::load($item->get('nid')->getValue()[0]['target_id']);
    $item_name = $node->getTitle() . ' (' . $item->get('barcode')->value . ')';

    drupal_set_message(t('Transaction processed for @item.', ['@item' => $item_name]));
    \Drupal::service('event_dispatcher')->dispatch('library.action', new ActionEvent($transaction));
  }

}
