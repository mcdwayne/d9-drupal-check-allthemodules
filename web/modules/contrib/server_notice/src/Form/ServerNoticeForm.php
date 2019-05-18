<?php

namespace Drupal\server_notice\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Url;
use Drupal\server_notice\Entity\ServerNotice;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements a server notice form.
 */
class ServerNoticeForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_id = $form['#form_id'];
    parent::validateForm($form, $form_state);
    $potential_fqdn = $form_state->getValue(['fqdn', 0, 'value']);

    // Search for duplicate.
    $server_notice_entity_array = ServerNotice::loadMultiple(NULL);

    foreach ($server_notice_entity_array as $server_notice) {
      $fqdn = $server_notice->get('fqdn')->value;
      if ($potential_fqdn == $fqdn && $form_id == 'server_notice_add_form') {
        $form_state->setErrorByName('server_notice_fqdn', $this->t('The domain %fqdn already has a notice. Do you want to <a href="@edit-page">edit the existing notice</a>?',
          [
            '%fqdn' => $fqdn,
            '@edit-page' => $server_notice->url('edit-form'),
          ]
        ));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    drupal_set_message($this->t('The server notice has been saved.'));
    $form_state->setRedirect('server_notice.list');
  }

}
