<?php

namespace Drupal\mothermayi\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class MothermayiSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mothermayi.settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('mothermayi.settings');

    // TODO: We ought to clear the user registration form from the cache
    // any time changes are made in mothermayi settings. captcha does that
    // if you're also using it. But maybe not everyone is.

    $vars = array(
      'mothermayi_secret_hint',
      'mothermayi_secret_word',
      'mothermayi_use_preg',
      'mothermayi_empty',
      'mothermayi_empty_description',
      'mothermayi_weight',
      );

    foreach ($vars as $variable) {
      $config->set($variable, $form_state->getValue($variable));
    }

    if ($config->get('mothermayi_flush_needed')) {
      // This is the first time the settings were changed since
      // install. Flush caches to ensure our hook gets called.
      drupal_flush_all_caches();
      $config->set('mothermayi_flush_needed', FALSE);
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['mothermayi.settings'];
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {

    $config = $this->config('mothermayi.settings');

    $fs = [
      '#type' => 'fieldset',
      '#title' => $this->t('Secret word'),
      '#description' => $this->t('Specify a site-specific word that potential users must enter before applying for an account. See discussion at <a href="@url">the Drupal Mother May I site</a>.', [
        '@url' => Url::fromUri('http://drupal.org/project/mothermayi')->toString()
        ]),
    ];

    $fs['mothermayi_secret_word'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret word required to create an account'),
      '#size' => 15,
      '#default_value' => $config->get('mothermayi_secret_word'),
      '#description' => $this->t('If supplied, a new user must enter this word to create an account. Leave blank to disable.'),
    ];
    $fs['mothermayi_use_preg'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use regular expression'),
      '#default_value' => $config->get('mothermayi_use_preg'),
      '#description' => $this->t('If set, the secret word is a regular expression as used with preg_match(), for example @example for case-insensitive match.', [
        '@example' => '\'/^aword$/i\''
        ]),
    ];
    $fs['_mothermayi_test'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Test word'),
      '#default_value' => '',
      '#description' => $this->t('If you put an entry here, it will be checked against your secret word.'),
    ];

    $secret_hint = $config->get('mothermayi_secret_hint');
    $fs['mothermayi_secret_hint'] = [
      '#base_type' => 'textarea',
      '#type' => 'text_format',
      '#title' => $this->t('User hint'),
      '#cols' => 40,
      '#rows' => 5,
      '#resizable' => TRUE,
      '#default_value' => $secret_hint['value'],
      '#description' => $this->t('If specified, this will be listed as a hint to the knowledgable user.'),
      '#format' => $secret_hint['format'],
    ];
    $form['mothermayi_fs1'] = $fs;

    $fs = [
      '#type' => 'fieldset',
      '#title' => $this->t('Empty field'),
      '#description' => $this->t('This lets you include an empty field that must be left empty. Many bots will try to fill it in.'),
    ];

    $fs['mothermayi_empty'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show text field that must be left empty.'),
      '#default_value' => $config->get('mothermayi_empty', FALSE),
      '#description' => $this->t('If checked, the user will see a text field that must be left empty.'),
    ];

    $fs['mothermayi_empty_description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Emptyfield description'),
      '#cols' => 40,
      '#rows' => 5,
      '#resizable' => TRUE,
      '#default_value' => $config->get('mothermayi_empty_description'),
      '#description' => $this->t('If specified, this will be used as descriptive text telling the prospective user to leave the field empty'),
    ];
    $form['mothermayi_fs2'] = $fs;

    $w = $config->get('mothermayi_weight', 10);

    $form['mothermayi_weight'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Form weight'),
      '#size' => 4,
      '#default_value' => $w,
      '#description' => $this->t('Weight controls location of item on page'),
    ];
    return parent::buildForm($form, $form_state);
  }

   /**
   * {@inheritdoc}
   */
   public function validateForm(array &$form, FormStateInterface $form_state) {
    $w = $form_state->getValue('mothermayi_weight');
    if (!is_numeric($w)) {
      $form_state->setErrorByName('mothermayi_weight', $this->t('Weight must be numeric.'));
    }

    $theword = $form_state->getValue('mothermayi_secret_word');
    $up = $form_state->getValue('mothermayi_use_preg');
    if ($theword != '') {
      if ($up == 0 && !ctype_alnum($theword)) {
        $form_state->setErrorByName('mothermayi_secret_word', $this->t('Secret word must be alphanumeric.'));
      }

      if ($up) {
        // Check that the regular expression is OK.
        if (@preg_match($theword, 'foo') === FALSE) {
          $form_state->setErrorByName('mothermayi_secret_word', $this->t('Invalid preg.'));
          return;
        }
      }
      $tw = $form_state->getValue('_mothermayi_test');
      if ($tw != '' && !_mothermayi_check_secret($tw, $theword, $up)) {
          $form_state->setErrorByName('_mothermayi_test', $this->t("This doesn't match the secret word."));
      }
    }
  }

}
