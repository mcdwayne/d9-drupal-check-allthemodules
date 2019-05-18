<?php

namespace Drupal\shorten\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Keys Page.
 */
class ShortenKeysForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shorten_keys';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'shorten.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('shorten.settings');
    $form['shorten_bitly'] = array(
      '#type' => 'fieldset',
      '#title' => t('Bit.ly and j.mp'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
    $form['shorten_bitly']['shorten_bitly_login'] = array(
      '#type' => 'textfield',
      '#title' => t('Bit.ly Login'),
      '#default_value' => \Drupal::config('shorten.settings')->get('shorten_bitly_login'),
    );
    $form['shorten_bitly']['shorten_bitly_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Bit.ly API Key'),
      '#default_value' => \Drupal::config('shorten.settings')->get('shorten_bitly_key'),
    );
    $form['shorten_budurl'] = array(
      '#type' => 'textfield',
      '#title' => t('BudURL API Key'),
      '#default_value' => \Drupal::config('shorten.settings')->get('shorten_budurl'),
    );
    $form['shorten_cligs'] = array(
      '#type' => 'textfield',
      '#title' => t('Cligs API Key'),
      '#default_value' => \Drupal::config('shorten.settings')->get('shorten_cligs'),
    );
    $form['shorten_ez'] = array(
      '#type' => 'textfield',
      '#title' => t('Ez API Key'),
      '#default_value' => \Drupal::config('shorten.settings')->get('shorten_ez'),
    );
    $form['shorten_fwd4me'] = array(
      '#type' => 'textfield',
      '#title' => t('Fwd4.me API Key'),
      '#default_value' => \Drupal::config('shorten.settings')->get('shorten_fwd4me'),
    );
    $form['shorten_googl'] = array(
      '#type' => 'textfield',
      '#title' => t('Goo.gl API Key'),
      '#default_value' => \Drupal::config('shorten.settings')->get('shorten_googl'),
    );
    $form['shorten_redirec'] = array(
      '#type' => 'textfield',
      '#title' => t('Redir.ec API Key'),
      '#default_value' => \Drupal::config('shorten.settings')->get('shorten_redirec'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('shorten.settings')
      ->set('shorten_bitly_login', $values['shorten_bitly_login'])
      ->set('shorten_bitly_key', $values['shorten_bitly_key'])
      ->set('shorten_budurl', $values['shorten_budurl'])
      ->set('shorten_cligs', $values['shorten_cligs'])
      ->set('shorten_ez', $values['shorten_ez'])
      ->set('shorten_fwd4me', $values['shorten_fwd4me'])
      ->set('shorten_googl', $values['shorten_googl'])
      ->set('shorten_redirec', $values['shorten_redirec'])
      ->save();
  }
}
