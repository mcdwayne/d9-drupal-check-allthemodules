<?php

namespace Drupal\smart_ip_locale\Form;

use Drupal\Core\Form\ConfigFormBaseTrait;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines a confirmation form for deleting an IP language negotiation mapping.
 */
class NegotiationIpDeleteForm extends ConfirmFormBase {
  use ConfigFormBaseTrait;

  /**
   * The browser language code to be deleted.
   *
   * @var string
   */
  protected $browserLangcode;

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['smart_ip_locale.mappings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %country_code?', ['%country_code' => $this->country_code]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('language.negotiation_ip');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'language_negotiation_configure_ip_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $country_code = NULL) {
    $this->country_code = $country_code;

    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('smart_ip_locale.mappings')
      ->clear('map.' . $this->country_code)
      ->save();

    $args = [
      '%country_code' => $this->country_code,
    ];

    $this->logger('language')->notice('The country code language detection mapping for the %country_code language code has been deleted.', $args);

    drupal_set_message($this->t('The mapping for the %country_code language code has been deleted.', $args));

    $form_state->setRedirect('language.negotiation_ip');
  }

}
