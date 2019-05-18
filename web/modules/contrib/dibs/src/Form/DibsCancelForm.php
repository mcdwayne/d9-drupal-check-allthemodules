<?php

namespace Drupal\dibs\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dibs\Entity\DibsTransaction;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DibsRedirectForm.
 *
 * @package Drupal\dibs\Form
 */
class DibsCancelForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dibs_cancel_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $transaction = $form_state->getBuildInfo()['args'][0]['transaction'];
    $form['hash'] = [
      '#type' => 'hidden',
      '#value' => $transaction->hash->value,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Return to DIBS payment'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $transaction = DibsTransaction::loadByHash($form_state->getValue('hash'));
    switch ($this->config('dibs.settings')->get('general.retry_handling')) {
      case 'new_order_id': {
        // @todo implement this case.
        break;
      }
      case 'add_retry_suffix': {
        $transaction->retry_count->value++;
        $transaction->save();
        break;
      }
    }

    $form_state->setRedirect('dibs.dibs_pages_controller_redirect', ['transaction_hash' => $transaction->hash->value]);
  }
}
