<?php

namespace Drupal\cmood\Form;

use Drupal\cmood\Storage\CmoodStorage;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CmoodAddRankWord implements form for adding Rank Word.
 */
class CmoodAddRankWord extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cmood_rank_word_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $rwid = NULL) {
    $form = [];
    if (isset($rwid) && is_numeric($rwid)) {
      $rw_edit = CmoodStorage::getCmoodTableDataById('rank_word_with_weight', [
        'column' => 'rwid',
        'id' => $rwid,
      ]);
    }
    $form['word'] = [
      '#type' => 'textfield',
      '#title' => t('Enter your rank word:'),
      '#size' => 10,
      '#maxlength' => 255,
      '#default_value' => isset($rw_edit['name']) ? $rw_edit['name'] : '',
      '#required' => TRUE,
      '#description' => '<p>' . t('Enter a single word no spaces allowed. You can enter words like very, more, loud etc. Whose weight will be multiplied with mood words. For example "good" word has weight of 2 and very has weight of 3 then a node containing "good" word will have a mood of 2 and node containing "very good" will have a mood of 6.') . '</p>',
    ];
    $form['weight'] = [
      '#type' => 'select',
      '#title' => t('Select weight of rank word:'),
      '#options' => CmoodStorage::cmoodWeightArray([0, 1]),
      '#default_value' => isset($rw_edit['weight']) ? $rw_edit['weight'] : '2',
      '#description' => '<p>' . t('Select weight of the word e.g. very = 2, more = 3, loud = 4') . '</p>',
    ];
    $form['rwid'] = [
      '#type' => 'hidden',
      '#title' => t('Rank Word Id:'),
      '#value' => isset($rw_edit['rwid']) ? $rw_edit['rwid'] : '',
    ];
    $form['submit_button'] = [
      '#type' => 'submit',
      '#value' => t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $word = $form_state->getValue(['word']);
    if (strpos(trim($word), ' ')) {
      // Word can't contain spaces.
      $form_state->setErrorByName('word', $this->t("Rank Word must not contain spaces."));
    }
    $weight = $form_state->getValue(['weight']);
    if (!($weight >= -20 && $weight <= 20)) {
      // Improper weight selected.
      $form_state->setErrorByName('weight', $this->t("Weight must greater than -20 and less than 20."));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $rwid = $form_state->getValue(['rwid']);
    if (isset($rwid) && is_numeric($rwid)) {
      CmoodStorage::updateCmoodRecords('rank_word_with_weight', [
        'name' => $form_state->getValue(['word']),
        'weight' => $form_state->getValue(['weight']),
        'updated' => REQUEST_TIME,
      ], ['field' => 'rwid', 'value' => $rwid]);
    }
    else {
      $account = \Drupal::currentUser();
      CmoodStorage::writeCmoodRecords('rank_word_with_weight', [
        'name' => $form_state->getValue(['word']),
        'weight' => $form_state->getValue(['weight']),
        'uid' => $account->id(),
        'updated' => REQUEST_TIME,
        'created' => REQUEST_TIME,
      ]);
    }
    $form_state->setRedirect('cmood.admin_cmood_rank');
  }

}
