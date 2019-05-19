<?php

namespace Drupal\sms_ui\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\sms_ui\Entity\SmsHistory;

class SmsHistoryDeleteForm extends ConfirmFormBase {

  /**
   * @var \Drupal\sms_ui\Entity\SmsHistoryInterface
   */
  protected $smsHistoryEntity;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the SMS history item?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    // @todo With destination, the cancel url is not correctly computed. This is
    // a bug in Drupal core.
    $sms_history = $this->getSmsHistoryEntity();
    return Url::fromRoute('sms_ui.history_' . $sms_history->getStatus());
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sms_history_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($this->getSmsHistoryEntity()) {
      $this->getSmsHistoryEntity()->delete();
    }
    $form_state->setRedirect('sms_ui.history_sent');
  }

  /**
   * Gets the SMS history entity in the url.
   *
   * @return \Drupal\sms_ui\Entity\SmsHistoryInterface
   */
  protected function getSmsHistoryEntity() {
    if (!isset($this->smsHistoryEntity)) {
      $this->smsHistoryEntity = SmsHistory::load($this->getRouteMatch()->getParameter('sms_history'));
    }
    return $this->smsHistoryEntity;
  }

}
