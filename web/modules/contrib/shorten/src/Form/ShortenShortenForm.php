<?php

namespace Drupal\shorten\Form;

use Drupal\Core\Form\FormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds a form which allows shortening of a URL via the UI.
 */
class ShortenShortenForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shorten_form_shorten';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $form_state_values = $form_state->getValues();
    $storage = &$form_state->getStorage();

    $form['#attached']['library'][] = 'shorten/shorten';

    //Form elements between ['opendiv'] and ['closediv'] will be refreshed via AHAH on form submission.
    $form['opendiv'] = array(
      '#markup' => '<div id="shorten_replace">',
    );
    if (empty($storage)) {
      $storage = array('step' => 0);
    }
    if (isset($storage['short_url'])) {
      // This whole "step" business keeps the form element from being cached.
      $form['shortened_url_' . $storage['step']] = array(
        '#type' => 'textfield',
        '#title' => t('Shortened URL'),
        '#default_value' => $storage['short_url'],
        '#size' => 25,
        '#attributes' => array('class' => array('shorten-shortened-url')),
      );
    }
    $form['url_' . $storage['step']] = array(
      '#type' => 'textfield',
      '#title' => t('URL'),
      '#default_value' => '',
      '#required' => TRUE,
      '#size' => 25,
      '#maxlength' => 2048,
      '#attributes' => array('class' => array('shorten-long-url')),
    );
    //Form elements between ['opendiv'] and ['closediv'] will be refreshed via AHAH on form submission.
    $form['closediv'] = array(
      '#markup' => '</div>',
    );
    $last_service = NULL;
    if (isset($storage['service'])) {
      $last_service = $storage['service'];
    }
    $service = _shorten_service_form($last_service);

    if (is_array($service)) {
      $form['service'] = $service;
    }
    $form['shorten'] = array(
      '#type' => 'submit',
      '#value' => t('Shorten'),
      '#ajax' => array(
        'callback' => 'shorten_save_js',
        'wrapper' => 'shorten_replace',
        'effect' => 'fade',
        'method' => 'replace',
      ),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $storage = &$form_state->getStorage();

    $url = $values['url_' . $storage['step']];
    if (\Drupal\Component\Utility\Unicode::strlen($url) > 4) {
      if (!strpos($url, '.', 1)) {
        $form_state->setErrorByName('url', $this->t('Please enter a valid URL.'));
      }
    }
    else {
      $form_state->setErrorByName('url', $this->t('Please enter a valid URL.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $storage = &$form_state->getStorage();

    $service = '';
    if (isset($values['service'])) {
      $service = $values['service'];
    }
    $shortened = shorten_url($values['url_' . $storage['step']], $service);
    if (isset($values['service'])) {
      $_SESSION['shorten_service'] = $values['service'];
    }
    drupal_set_message($this->t('%original was shortened to %shortened', array('%original' => $values['url_' . $storage['step']], '%shortened' => $shortened)));

    $form_state->setRebuild();

    if (empty($storage)) {
      $storage = array();
    }
    $storage['short_url'] = $shortened;
    $storage['service']   = empty($values['service'])? '' : $values['service'];
    if (isset($storage['step'])) {
      $storage['step']++;
    }
    else {
      $storage['step'] = 0;
    }

    $form_state->setStorage($storage);
  }
}
