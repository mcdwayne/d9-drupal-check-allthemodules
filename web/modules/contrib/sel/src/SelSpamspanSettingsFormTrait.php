<?php

namespace Drupal\sel;

/**
 * Provides common methods for Spamspan plugins in an alternate way.
 */
trait SelSpamspanSettingsFormTrait {

  /**
   * Spamspan default settings.
   *
   * @return array
   *   Spamspan default settings.
   */
  public static function spamspanDefaultSettings() {
    return [
      'spamspan' => [
        'spamspan_at' => ' [at] ',
        'spamspan_use_graphic' => 0,
        'spamspan_dot_enable' => 0,
        'spamspan_dot' => ' [dot] ',
        'use_form' => [
          'spamspan_use_form' => 0,
          'spamspan_form_pattern' => '<a href="%url?goto=%email">%displaytext</a>',
          'spamspan_form_default_url' => 'contact',
          'spamspan_form_default_displaytext' => 'contact form',
        ],
      ],
    ];
  }

  /**
   * Spamspan settings' form elements.
   *
   * @return array
   *   Spamspan settings form elements.
   */
  public function spamspanSettings() {
    // Spamspan '@' replacement.
    $form['spamspan_at'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Replacement for "@"'),
      '#default_value' => $this->getSetting('spamspan')['spamspan_at'],
      '#required' => TRUE,
      '#description' => $this->t('Replace "@" with this text when javascript is disabled.'),
    ];
    $form['spamspan_use_graphic'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use a graphical replacement for "@"'),
      '#default_value' => $this->getSetting('spamspan')['spamspan_use_graphic'],
      '#description' => $this->t('Replace "@" with a graphical representation when javascript is disabled (and ignore the setting "Replacement for @" above).'),
    ];
    $form['spamspan_dot_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Replace dots in email with text'),
      '#default_value' => $this->getSetting('spamspan')['spamspan_dot_enable'],
      '#description' => $this->t('Switch on dot replacement.'),
    ];
    $form['spamspan_dot'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Replacement for "."'),
      '#default_value' => $this->getSetting('spamspan')['spamspan_dot'],
      '#required' => TRUE,
      '#description' => $this->t('Replace "." with this text.'),
    ];

    // No trees, see https://www.drupal.org/node/2378437.
    // We fix this in our custom validate handler.
    $form['use_form'] = [
      '#type' => 'details',
      '#title' => $this->t('Use a form instead of a link'),
      '#open' => TRUE,
    ];
    $form['use_form']['spamspan_use_form'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use a form instead of a link'),
      '#default_value' => $this->getSetting('spamspan')['use_form']['spamspan_use_form'],
      '#description' => $this->t('Link to a contact form instead of an email address. The following settings are used only if you select this option.'),
    ];
    $form['use_form']['spamspan_form_pattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Replacement string for the email address'),
      '#default_value' => $this->getSetting('spamspan')['use_form']['spamspan_form_pattern'],
      '#required' => TRUE,
      '#description' => $this->t('Replace the email link with this string and substitute the following <br />%url = the url where the form resides,<br />%email = the email address (base64 and urlencoded),<br />%displaytext = text to display instead of the email address.'),
    ];
    $form['use_form']['spamspan_form_default_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default url'),
      '#default_value' => $this->getSetting('spamspan')['use_form']['spamspan_form_default_url'],
      '#required' => TRUE,
      '#description' => $this->t('Default url to form to use if none specified (e.g. me@example.com[custom_url_to_form])'),
    ];
    $form['use_form']['spamspan_form_default_displaytext'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default displaytext'),
      '#default_value' => $this->getSetting('spamspan')['use_form']['spamspan_form_default_displaytext'],
      '#required' => TRUE,
      '#description' => $this->t('Default displaytext to use if none specified (e.g. me@example.com[custom_url_to_form|custom_displaytext])'),
    ];

    return $form;
  }

}
