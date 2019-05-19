<?php
/**
 * @file
 * Contains \Drupal\shurly\Form\ShurlyCreateForm.
 */

namespace Drupal\shurly\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;

/**
 * ShurlyCreateForm.
 */
class ShurlyCreateForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shurly_create_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;

    $form['#theme'] = 'shurly_create_form';

    $storage = &$form_state->getStorage();

    $form['long_url'] = array(
      '#type' => 'textfield',
      '#maxlength' => 2048,
      '#default_value' => isset($storage['shurly']['long_url']) ? $storage['shurly']['long_url'] : FALSE,
      '#attributes' => array('tabindex' => 1, 'placeholder' => t('Enter a long URL to make short')),
    );

    $short_default = \Drupal::currentUser()->hasPermission('Enter custom URLs') ? (isset($storage['shurly']['short_url']) ? $storage['shurly']['short_url'] : '') : '';

    $form['short_url'] = array(
        '#type' => 'textfield',
        '#size' => 6,
        '#field_prefix' => \Drupal::config('shurly.settings')->get('shurly_base') . '/',
        '#field_suffix' => ' <span class="shurly-choose">&lt;--- ' . t('create custom URL') . '</span>',
        '#default_value' => $short_default,
        '#access' => \Drupal::currentUser()->hasPermission('Enter custom URLs'),
        '#attributes' => array('tabindex' => 2),
      );

    if (isset($storage['shurly']['final_url'])) {
      $form['result'] = array(
        '#type' => 'textfield',
        '#size' => 30,
        '#value' => $storage['shurly']['final_url'],
        '#field_prefix' => t('Your short URL:'),
        '#field_suffix' => ' <div id="shurly-copy-container" style="position:relative;"><div id="shurly-copy">' . t('copy') . '</div></div>
        <div class="social"><a href="http://twitter.com?status=' . urlencode($storage['shurly']['final_url']) . '">' . t('Create a Twitter message with this URL') . '</a></div>',
      );
    }

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Shrink it!'),
      '#attributes' => array('tabindex' => 3),
    );

    unset($storage['shurly']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    global $base_url;

    $rate_limit = shurly_rate_limit_allowed();
    if (!$rate_limit['allowed']) {
      $form_state->setError('', t('Rate limit exceeded. You are limited to @rate requests per @time minute period.', array('@rate' => $rate_limit['rate'], '@time' => $rate_limit['time'])));
      return;
    }

    $form_state->setValue('long_url', trim($form_state->getValue('long_url')));
    $form_state->setValue('short_url', trim($form_state->getValue('short_url')));

    $vals = $form_state->getValues();

    // check that they've entered a URL
    if ($vals['long_url'] == '' || $vals['long_url'] == 'http://' || $vals['long_url'] == 'https://') {
      $form_state->setError('long_url', t('Please enter a web URL'));
    }
    elseif (!shurly_validate_long($vals['long_url'])) {
      $form_state->setErrorByName('long_url', t('Invalid URL'));
    }

    if (trim($vals['short_url']) != '') {
      // a custom short URL has been entered
      $form_state->setValue('custom', array(TRUE));

    if (!shurly_validate_custom($vals['short_url'])) {
      $form_state->setErrorByName('short_url', t('Short URL contains unallowed characters'));
    }
    elseif ($exists = shurly_url_exists($vals['short_url'], $vals['long_url'])) {
      $form_state->setErrorByName('short_url', t('This short URL has already been used'));
      }
      elseif (_surl($vals['short_url'], array('absolute' => TRUE)) == $vals['long_url'] || _surl($vals['short_url'], array('absolute' => TRUE, 'base_url' => \Drupal::config('shurly.settings')->get('shurly_base'))) == $vals['long_url']) {
        // check that link isn't to itself (creating infinite loop)
        // problem - http vs https
        $form_state->setError('short_url', t('You cannot create links to themselves'));
      }
      elseif (!shurly_path_available($vals['short_url'])) {
        $form_state->setErrorByName('short_url', t('This custom URL is reserved. Please choose another.'));
      }
    }
    else {
      // custom short URL field is empty
      $form_state->setValue('custom', TRUE);
      if ($exist = shurly_get_latest_short($vals['long_url'], \Drupal::currentUser()->uid)) {
        $short = $exist;
        // we flag this as URL Exists so that it displays but doesn't get saved to the db
        $form_state->setValue('url_exists', TRUE);
      }
      else {
        $short = shurly_next_url();
      }
      $form_state->setValue('short_url', $short);
      $form_state->setStorage(array('shurly' => array('short_url' => $short)));
    }

    // check that the destination URL is "safe"
    if(\Drupal::config('shurly.settings')->get('shurly_gsb')){

     $gsb = shurly_gsb($vals['long_url']);

      if ($gsb) {
        $form_state->setErrorByName('long_url', t('This URL is either phishing, malware, or both.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    global $base_url;

    // submit the short URL form
    $long_url = $form_state->getValue('long_url');
    $short_url = $form_state->getValue('short_url');

    $form_state->setStorage(array('shurly' => array(
      'long_url' => $long_url,
      'short_url' => $short_url,
      'final_url' => urldecode(\Drupal::config('shurly.settings')->get('shurly_base') . '/' . $short_url)
      )
    ));
    
    $custom = $form_state->setValue('custom', array($form_state->getValue('custom')));

    $form_state->setRebuild();

    if (empty($form_state->getValue('url_exists'))) {
      shurly_save_url($long_url, $short_url, NULL, $custom);
    }
  }
}
