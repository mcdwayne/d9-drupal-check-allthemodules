<?php

namespace Drupal\record_shorten\Form;

use Drupal\Core\Form\FormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Report form.
 */
class RecordshortenClearAll extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'record_shorten_clear_all';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['warning'] = array(
      '#markup' => '<p><strong>' . t('Warning: there is no confirmation page. Cleared records are permanently deleted.') . '</strong></p>',
    );
    $form['note'] = array(
      '#markup' => '<p>' . t('Note: clearing records does not clear the Shorten URLs cache.') . ' ' .
        t('Also, URLs already in the cache are not recorded again.') . '</p>',
    );
    $form['clear'] = array(
      '#type' => 'submit',
      '#value' => t('Clear all records'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    db_query("TRUNCATE TABLE {record_shorten}");
    drupal_set_message('Shorten Url records cleared.');
  }

}
