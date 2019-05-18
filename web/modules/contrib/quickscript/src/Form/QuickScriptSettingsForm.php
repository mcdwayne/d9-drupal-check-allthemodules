<?php

/**
 * @file
 * Contains \Drupal\quickscript\Form\QuickScriptSettingsForm.
 */

namespace Drupal\quickscript\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\quickscript\Entity\QuickScript;

/**
 * Class QuickScriptSettingsForm.
 *
 * @package Drupal\quickscript\Form
 *
 * @ingroup quickscript
 */
class QuickScriptSettingsForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'QuickScript_settings';
  }

  public static function encryptionEnabled() {
    $encrypt = \Drupal::config('quickscript.settings')->get('encrypt_code');
    if ($encrypt && \Drupal::service('module_handler')
        ->moduleExists('encrypt')) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Form validation handler.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('encrypt_code')) {
      if (!$form_state->getValue('encrypt_code_profile') || !in_array($form_state->getValue('encrypt_code_profile'), $this->getEncryptionProfileOptions())) {
        $form_state->setErrorByName('encrypt_code_profile', t('Encryption profile is required.'));
      }
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Empty implementation of the abstract submit class.
    $config = $this->configFactory()->getEditable('quickscript.settings');

    if ($config->get('encrypt_code') && !$form_state->getValue('encrypt_code')) {
      // We are disabling encryption for all scripts.
      $this->disableEncryption();
    }
    elseif (!$config->get('encrypt_code') && $form_state->getValue('encrypt_code')) {
      // We are enabling encryption.
      $this->enableEncryption();
    }

    $config->set('enable_code_editor', $form_state->getValue('enable_code_editor'));
    $config->set('encrypt_code', $form_state->getValue('encrypt_code'));
    $config->set('encrypt_code_profile', $form_state->getValue('encrypt_code_profile'));
    $config->save();
  }

  /**
   * Disables encryption for all Quick Scripts in the database.
   */
  private function disableEncryption() {
    $scripts = QuickScript::loadAll();
    foreach ($scripts as $script) {
      if ($script->encrypted->value) {
        $script->encrypted = 0;
        $script->code = $script->decrypt();
        $script->save();
      }
    }
  }

  /**
   * Encrypts all Quick Scripts in the database.
   */
  private function enableEncryption() {
    $scripts = QuickScript::loadAll();
    foreach ($scripts as $script) {
      if (!$script->encrypted->value) {
        $script->encrypted = 1;
        $script->code = $script->encrypt();
        $script->save();
      }
    }
  }

  /**
   * Gets all encryption profiles in a listing.
   */
  private function getEncryptionProfileOptions() {
    $storage = \Drupal::entityTypeManager()->getStorage('encryption_profile');
    $options = [];
    $profiles = $storage->loadByProperties([]);
    foreach ($profiles as $p) {
      $method = $p->getEncryptionMethod();
      if (!method_exists($method, 'encrypt')) {
        continue;
      }
      $options[$p->id()] = $p->label() . ' (using ' . $method->getLabel() . ')';
    }
    return $options;
  }

  /**
   * Defines the settings form for Quick Script entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('quickscript.settings');
    $form['enable_code_editor'] = [
      '#type' => 'checkbox',
      '#default_value' => $config->get('enable_code_editor') === 0 ? 0 : 1,
      '#title' => $this->t('Enable Code Editor'),
    ];

    if ($config->get('enable_code_editor') === 0 ? 0 : 1 === 1) {
      $form['#attached']['library'][] = 'quickscript/codemirror';
      $form['#attached']['library'][] = 'quickscript/quickscript';
      $form['#attached']['drupalSettings']['quickscript']['enable_code_editor'] = $config->get('enable_code_editor') === 0 ? 0 : 1;

      $form['code']['theme_selector'] = [
        '#type' => 'select',
        '#title' => t('Editor Theme'),
        '#options' => $this->getThemeOptions(),
        '#description' => t('<em>This theme will only apply to your browser.</em>'),
        '#attributes' => ['onchange' => 'javascript:Drupal.quickscript.updateTheme(this.value);'],
      ];
      $form['code']['code_preview'] = [
        '#type' => 'textarea',
        '#title' => t('Code Preview'),
        '#default_value' => $this->getCodePreview(),
      ];
    }

    $form['encrypt_code'] = [
      '#type' => 'checkbox',
      '#default_value' => !$config->get('encrypt_code') ? 0 : 1,
      '#title' => $this->t('Encrypt Code in Database'),
    ];

    if (!\Drupal::service('module_handler')->moduleExists('encrypt')) {
      $form['encrypt_code']['#default_value'] = 0;
      $form['encrypt_code']['#disabled'] = TRUE;
      $form['encrypt_code']['#description'] = t('Download, install, and configure the <a href="@url" target="_blank">Encrypt Module</a> to enable encryption.', [
        '@url' => Url::fromUri('https://www.drupal.org/project/encrypt')
          ->toString(),
      ]);
    }
    else {
      $form['encrypt_code_profile'] = [
        '#type' => 'select',
        '#title' => t('Encryption Profile'),
        '#options' => $this->getEncryptionProfileOptions(),
        '#default_value' => $config->get('encrypt_code_profile'),
        '#empty_option' => t('- select an encryption profile -'),
        '#states' => [
          'visible' => [
            ':input[name="encrypt_code"]' => ['checked' => TRUE],
          ],
        ],
      ];

      if (!$this->getEncryptionProfileOptions()) {
        $form['encrypt_code_profile']['#disabled'] = TRUE;
        $form['encrypt_code_profile']['#description'] = t('You must <a href="@url">create an encryption profile</a> first.', [
          '@url' => Url::fromRoute('entity.encryption_profile.add_form')
            ->toString(),
        ]);
      }
    }

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Save Settings'),
      ],
    ];
    return $form;
  }

  /**
   * Gets some fake PHP code for the preview window.
   */
  private function getCodePreview() {
    return <<<_PHP
<?php
// Feel free to play around with this text!
print 'this is a text string';

\$result = call_function();

\$class = new Class(\$var1, \$var2);

/**
 * This is a function comment.
 */
function call_function() {
  return FALSE;
}

_PHP;

  }

  /**
   * Gets the list of available themes for the code editor.
   *
   * @return array
   */
  private function getThemeOptions() {
    return [
      'default' => t('Default'),
      '3024-day' => t('3024 Day'),
      '3024-night' => t('3024 Night'),
      'abcdef' => t('ABCDEF'),
      'ambiance' => t('Ambiance'),
      'base16-dark' => t('Base16 Dark'),
      'base16-light' => t('Base16 Light'),
      'bespin' => t('Bespin'),
      'blackboard' => t('Blackboard'),
      'cobalt' => t('Cobalt'),
      'colorforth' => t('Colorforth'),
      'dracula' => t('Dracula'),
      'eclipse' => t('Eclipse'),
      'elegant' => t('Elegant'),
      'erlang-dark' => t('Erlang Dark'),
      'hopscotch' => t('Hopscotch'),
      'icecoder' => t('Ice Coder'),
      'isotope' => t('Isotope'),
      'lesser-dark' => t('Lesser Dark'),
      'liquibyte' => t('Liquibyte'),
      'material' => t('Material'),
      'mbo' => t('MBO'),
      'mdn-like' => t('MDN-Like'),
      'midnight' => t('Midnight'),
      'monokai' => t('Monokai'),
      'neat' => t('Neat'),
      'neo' => t('Neo'),
      'night' => t('Night'),
      'panda-syntax' => t('Panda Syntax'),
      'paraiso-dark' => t('Paraiso Dark'),
      'paraiso-light' => t('Paraiso Light'),
      'pastel-on-dark' => t('Pastel on Dark'),
      'railscasts' => t('Rails Casts'),
      'rubyblue' => t('Ruby Blue'),
      'seti' => t('SETI'),
      'solarized' => t('Solarized'),
      'the-matrix' => t('The Matrix'),
      'tomorrow-night-bright' => t('Tomorrow Night Bright'),
      'tomorrow-night-eighties' => t('Tomorrow Night Eighties'),
      'ttcn' => t('TTCN'),
      'twilight' => t('Twilight'),
      'vibrant-ink' => t('Vibrant Ink'),
      'xq-dark' => t('XQ Dark'),
      'xq-light' => t('XQ Light'),
      'yeti' => t('Yeti'),
      'zenburn' => t('Zenburn'),
    ];
  }

}
