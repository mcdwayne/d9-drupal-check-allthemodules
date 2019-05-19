<?php

namespace Drupal\upgrade_tool\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Upgrade log edit forms.
 *
 * @ingroup upgrade_tool
 */
class UpgradeLogForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\upgrade_tool\Entity\UpgradeLog */
    $form = parent::buildForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Upgrade log.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Upgrade log.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.upgrade_log.canonical', ['upgrade_log' => $entity->id()]);
  }

}
