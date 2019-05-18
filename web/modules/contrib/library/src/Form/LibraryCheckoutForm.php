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
 * Library checkout form.
 */
class LibraryCheckoutForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'library_checkout_form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $action
   *   Relevant action.
   * @param string $item
   *   Relevant item.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $action = NULL, $item = NULL) {

    if ($action == NULL || $item == NULL) {
      $form_state->setErrorByName('action', $this->t('Required parameters missing'));
      return $form;
    }

    $form['action'] = [
      '#type' => 'hidden',
      '#value' => $action,
    ];

    $form['library_item'] = [
      '#type' => 'hidden',
      '#value' => $item,
    ];

    $itemEntity = LibraryItem::load($item);
    if ($itemEntity->get('nid')->getValue()) {
      $node = Node::load($itemEntity->get('nid')->getValue()[0]['target_id']);

      if ($itemEntity->get('barcode')->value) {
        $format_title = $node->getTitle() . ' (' . $itemEntity->get('barcode')->value . ')';
      }
      else {
        $format_title = $node->getTitle();
      }

      $form['item_display'] = [
        '#type' => 'textfield',
        '#title' => t('Item'),
        '#value' => $format_title,
        '#disabled' => TRUE,
      ];
      $form['nid'] = [
        '#type' => 'value',
        '#value' => $node->id(),
      ];
    }
    else {
      $form_state->setErrorByName('item_display', $this->t('Required parameters missing'));
      return $form;
    }

    $actionEntity = LibraryAction::load($action);

    if ($actionEntity->action() == LibraryAction::CHANGE_TO_UNAVAILABLE) {
      $form['patron'] = [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'user',
        '#tags' => TRUE,
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
    // TODO: Verify that the state change is allowed, don't trust the parameters.
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $transaction = LibraryTransaction::create();
    $transaction->set('librarian_id', \Drupal::currentUser()->id());
    $transaction->set('nid', $form_state->getValue('nid'));
    $transaction->set('uid', $form_state->getValue('patron')[0]['target_id']);
    $transaction->set('library_item', $form_state->getValue('library_item'));
    $transaction->set('action', $form_state->getValue('action'));
    $transaction->set('due_date', LibraryItemHelper::computeDueDate($form_state->getValue('action'), $form_state->getValue('nid')));
    $transaction->set('notes', $form_state->getValue('notes'));
    $transaction->save();

    LibraryItemHelper::updateItemAvailability($form_state->getValue('library_item'), $form_state->getValue('action'));

    \Drupal::service('cache_tags.invalidator')->invalidateTags(['node:' . $form_state->getValue('nid')]);

    drupal_set_message(t('Transaction processed.'));
    \Drupal::service('event_dispatcher')->dispatch('library.action', new ActionEvent($transaction));

    $form_state->setRedirect('entity.node.canonical', ['node' => $form_state->getValue('nid')]);
  }

}
