<?php

namespace Drupal\cmood\Form;

use Drupal\cmood\Storage\CmoodStorage;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CmoodAddMoodWord implements form for adding Mood Word.
 */
class CmoodAddMoodWord extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cmood_word_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $wid = NULL) {
    $form = [];
    if (isset($wid) && is_numeric($wid)) {
      $w_edit = CmoodStorage::getCmoodTableDataById('word_with_weight', [
        'column' => 'wid',
        'id' => $wid,
      ]);
    }
    $form['word'] = [
      '#type' => 'textfield',
      '#title' => t('Enter your word:'),
      '#size' => 10,
      '#maxlength' => 255,
      '#default_value' => isset($w_edit['name']) ? $w_edit['name'] : '',
      '#required' => TRUE,
      '#description' => '<p>' . t('Enter a single word no spaces allowed.')
      . '</p>',
    ];
    $form['weight'] = [
      '#type' => 'select',
      '#title' => t('Select weight of word:'),
      '#options' => CmoodStorage::cmoodWeightArray(),
      '#default_value' => isset($w_edit['weight']) ? $w_edit['weight'] : '0',
      '#description' => '<p>' . t('Select weight of the word e.g. good = 2, better = 3, best = 4, bad = -2, worse = -3, worst = -4.') . '</p>',
    ];
    $form['wid'] = [
      '#type' => 'hidden',
      '#title' => t('Word Id:'),
      '#value' => isset($w_edit['wid']) ? $w_edit['wid'] : '',
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
      $form_state->setErrorByName('word', $this->t("Word must not contain spaces."));
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
    $wid = $form_state->getValue(['wid']);
    if (isset($wid) && is_numeric($wid)) {
      CmoodStorage::updateCmoodRecords('word_with_weight', [
        'name' => $form_state->getValue(['word']),
        'weight' => $form_state->getValue(['weight']),
        'updated' => REQUEST_TIME,
      ], ['field' => 'wid', 'value' => $wid]);
    }
    else {
      $account = \Drupal::currentUser();
      CmoodStorage::writeCmoodRecords('word_with_weight', [
        'name' => $form_state->getValue(['word']),
        'weight' => $form_state->getValue(['weight']),
        'uid' => $account->id(),
        'updated' => REQUEST_TIME,
        'created' => REQUEST_TIME,
      ]);
    }
    $form_state->setRedirect('cmood.admin_cmood_word');
  }

}
