<?php

namespace Drupal\revealjs\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Mailchimp\MailchimpAPIException;

/**
 * Configure Revealjs global settings.
 */
class RevealjsAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'revealjs_admin_settings';
  }

  protected function getEditableConfigNames() {
    return ['revealjs.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('revealjs.settings');

    $form['classlist'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable %name plugin', ['%name' => 'ClassList']),
      '#default_value' => $config->get('classlist') ? $config->get('classlist') : FALSE,
      '#description' => $this->t('Cross-browser shim that fully implements classList'),
    ];

    $form['markdown'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable %name plugin', ['%name' => 'Markdown']),
      '#default_value' => $config->get('markdown') ? $config->get('markdown') : FALSE,
      '#description' => $this->t('Interpret Markdown in section elements'),
    ];

    $form['highlight'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable %name plugin', ['%name' => 'Highlight']),
      '#default_value' => $config->get('highlight') ? $config->get('highlight') : FALSE,
      '#description' => $this->t('Syntax highlight in code elements'),
    ];

    $form['zoom'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable %name plugin', ['%name' => 'Zoom']),
      '#default_value' => $config->get('zoom') ? $config->get('zoom') : FALSE,
      '#description' => $this->t('Zoom in and out with Alt+click'),
    ];

    $form['notes'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable %name plugin', ['%name' => 'Notes']),
      '#default_value' => $config->get('notes') ? $config->get('notes') : FALSE,
      '#description' => $this->t('Speaker notes'),
    ];

    $form['math'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable %name plugin', ['%name' => 'Mathjax']),
      '#default_value' => $config->get('math') ? $config->get('math') : FALSE,
      '#description' => $this->t('Render a beautiful math, check samples to the website of <a href="https://www.mathjax.org/#samples">Mathjax</a>'),
    ];

    $states = [
      'visible' => [
        ':input[name="math"]' => ['checked' => TRUE],
      ],
      'invisible' => [
        ':input[name="math"]' => ['checked' => FALSE],
      ],
    ];

    $form['math_config'] = [
      '#type' => 'select',
      '#title' => $this->t('Mathjax configuration'),
      '#states' => $states,
      '#default_value' => $config->get('math_config') ? $config->get('math_config') : 'none',
      '#options' => [
        'none' => $this->t('None'),
        'TeX-MML-AM_CHTML' => $this->t('TeX-MML-AM_CHTML'),
        'TeX-MML-AM_HTMLorMML' => $this->t('TeX-MML-AM_HTMLorMML'),
        'TeX-MML-AM_SVG' => $this->t('TeX-MML-AM_SVG'),
        'TeX-AMS-MML_HTMLorMML' => $this->t('TeX-AMS-MML_HTMLorMML'),
        'TeX-AMS_CHTML' => $this->t('TeX-AMS_CHTML'),
        'TeX-AMS_SVG' => $this->t('TeX-AMS_SVG'),
        'TeX-AMS_HTML' => $this->t('Tex-AMS_HTML'),
        'TeX-AMS_HTML-full' => $this->t('Tex-AMS_HTML-full'),
        'MML_CHTML' => $this->t('MML_CHTML'),
        'MML_SVG' => $this->t('MML_SVG'),
        'MML_HTMLorMML' => $this->t('MML_HTMLorMML'),
        'AM_CHTML' => $this->t('AM_CHTML'),
        'AM_SVG' => $this->t('AM_SVG'),
        'AM_HTMLorMML' => $this->t('AM_HTMLorMML'),
        'TeX-AMS-MML_SVG' => $this->t('TeX-AMS-MML_SVG'),
      ],
    ];

    $form['math_path'] = [
      '#type' => 'hidden',
      '#default_value' => $config->get('math_path') ? $config->get('math_path') : 'https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.5/MathJax.js',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    parent::validateForm($form, $form_state);

    $element = $form_state->getValue('math');
    $library_math = 'libraries/MathJax/MathJax.js';

    if ($element == TRUE) {
        if (file_exists(DRUPAL_ROOT . '/' . $library_math)) {
          $form_state->setValue('math_path', $library_math);
        }
        else {
          $form_state->setValue('math_path', 'https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.5/MathJax.js');
        }
    }

  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('revealjs.settings');
    $config
      ->set('classlist', $form_state->getValue('classlist'))
      ->set('markdown', $form_state->getValue('markdown'))
      ->set('highlight', $form_state->getValue('highlight'))
      ->set('zoom', $form_state->getValue('zoom'))
      ->set('notes', $form_state->getValue('notes'))
      ->set('math', $form_state->getValue('math'))
      ->set('math_config', $form_state->getValue('math_config'))
      ->set('math_path',  $form_state->getValue('math_path'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
