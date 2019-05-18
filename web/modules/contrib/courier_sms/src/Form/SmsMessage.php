<?php

namespace Drupal\courier_sms\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\courier\CourierTokenElementTrait;
use Drupal\courier\Entity\TemplateCollection;
use Drupal\courier_sms\SmsMessageInterface;

/**
 * Form controller for SMS.
 */
class SmsMessage extends ContentEntityForm {

  use CourierTokenElementTrait;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state, SmsMessageInterface $sms = NULL) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\courier_sms\SMSMessageInterface $sms */
    $sms = $this->entity;

    if (!$sms->isNew()) {
      $form['#title'] = $this->t('Edit SMS');
    }

    $template_collection = TemplateCollection::getTemplateCollectionForTemplate($sms);
    $form['tokens'] = [
      '#type' => 'container',
      '#weight' => 51,
    ];

    $form['tokens']['list'] = $this->templateCollectionTokenElement($template_collection);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $sms = $this->entity;
    $is_new = $sms->isNew();
    $sms->save();

    $t_args = array('%label' => $sms->label());
    if ($is_new) {
      drupal_set_message(t('SMS has been created.', $t_args));
    }
    else {
      drupal_set_message(t('SMS was updated.', $t_args));
    }
  }

}
