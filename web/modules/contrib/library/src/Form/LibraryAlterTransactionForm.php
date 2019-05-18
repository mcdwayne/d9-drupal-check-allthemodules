<?php

namespace Drupal\library\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\library\Entity\LibraryItem;
use Drupal\library\Entity\LibraryTransaction;
use Drupal\library\Event\ActionEvent;
use Drupal\node\Entity\Node;

/**
 * Alter the library transaction form.
 */
class LibraryAlterTransactionForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'library_alter_transaction_form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param null|int $transaction
   *   The transaction.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $transaction = NULL) {

    if ($transaction == NULL) {
      $form_state->setErrorByName('transaction', $this->t('Required parameters missing'));
      return $form;
    }

    $form['transaction'] = [
      '#type' => 'value',
      '#value' => $transaction,
    ];

    $transactionEntity = LibraryTransaction::load($transaction);

    if (!$transactionEntity) {
      $form_state->setErrorByName('transaction', $this->t('Required data missing'));
      return $form;
    }

    $itemEntity = LibraryItem::load($transactionEntity->get('library_item')->value);
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

    $form['notes'] = [
      '#type' => 'textarea',
      '#title' => t('Message'),
      '#required' => FALSE,
      '#maxlength' => 250,
      '#default_value' => $transactionEntity->get('notes')->value,
      '#description' => t('If you are reserving an item, make sure to include the date and time you would like it to be ready.'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update'),
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
    $transaction = LibraryTransaction::load($form_state->getValue('transaction'));
    $transaction->set('notes', $form_state->getValue('notes'));
    $transaction->save();

    \Drupal::service('cache_tags.invalidator')->invalidateTags(['node:' . $form_state->getValue('nid')]);

    drupal_set_message(t('Transaction updated.'));
    \Drupal::service('event_dispatcher')->dispatch('library.action', new ActionEvent($transaction));

    $form_state->setRedirect('entity.node.canonical', ['node' => $form_state->getValue('nid')]);
  }

}
