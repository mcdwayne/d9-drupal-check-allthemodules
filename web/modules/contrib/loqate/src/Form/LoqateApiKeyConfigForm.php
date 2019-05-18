<?php

namespace Drupal\loqate\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class LoqateApiKeyConfigForm.
 */
class LoqateApiKeyConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'loqate.loqateapikeyconfig',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'loqate_api_key_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('loqate.loqateapikeyconfig');

    $read_more_url = Url::fromUri('https://www.loqate.com/resources/support/setup-guides/advanced-setup-guide/#creating_a_key');
    $description_read_more_link = Link::fromTextAndUrl('Read more about Loqate API.', $read_more_url);

    $form['loqate_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Loqate API key'),
      '#description' => $description_read_more_link,
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('loqate_api_key'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('loqate.loqateapikeyconfig')
      ->set('loqate_api_key', $form_state->getValue('loqate_api_key'))
      ->save();
  }

}
