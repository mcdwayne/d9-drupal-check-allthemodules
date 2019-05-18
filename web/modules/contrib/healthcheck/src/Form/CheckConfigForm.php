<?php

namespace Drupal\healthcheck\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CheckForm.
 */
class CheckConfigForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\healthcheck\Entity\CheckConfigInterface $checkconfig */
    $checkconfig = $this->entity;

    /** @var \Drupal\healthcheck\Plugin\HealthcheckPluginInterface $check */
    $check = $checkconfig->getCheck();

    $form = $check->buildConfigurationForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    /** @var \Drupal\healthcheck\Entity\CheckConfigInterface $checkconfig */
    $checkconfig = $this->entity;

    $checkconfig->getCheck()->validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\healthcheck\Entity\CheckConfigInterface $checkconfig */
    $checkconfig = $this->entity;

    $checkconfig->getCheck()->submitConfigurationForm($form, $form_state);

    $status = $checkconfig->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Check.', [
          '%label' => $checkconfig->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Check.', [
          '%label' => $checkconfig->label(),
        ]));
    }
    $form_state->setRedirectUrl($checkconfig->toUrl('collection'));
  }

}
