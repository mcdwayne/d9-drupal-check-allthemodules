<?php

namespace Drupal\simple_styleguide\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class StyleguideSettings.
 *
 * @package Drupal\simple_styleguide\Form
 */
class StyleguideSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'simple_styleguide.styleguidesettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'styleguide_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('simple_styleguide.styleguidesettings');

    $form['link'] = [
      '#markup' => '<p><a href="/simple-styleguide" class="button">View Styleguide</a></p>',
    ];

    $form['intro'] = [
      '#markup' => 'Choose any of the default html patterns you would like to see on your styleguide. You can also create custom patterns as needed.',
    ];

    $form['default_patterns'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Default Patterns'),
      '#options' => [
        'headings' => 'headings',
        'text' => 'text',
        'lists' => 'lists',
        'blockquote' => 'blockquote',
        'rule' => 'horizontal rule',
        'table' => 'table',
        'alerts' => 'alerts',
        'breadcrumbs' => 'breadcrumbs',
        'forms' => 'forms',
        'buttons' => 'buttons',
        'pagination' => 'pagination',
      ],
      '#default_value' => (!empty($config->get('default_patterns')) && count($config->get('default_patterns')) > 0) ? $config->get('default_patterns') : [],
    ];

    $button_link = Url::fromRoute('entity.styleguide_pattern.collection')->toString();
    $form['custom'] = [
      '#markup' => '<p><a href="' . $button_link . '" class="button">Create Custom Styleguide Patterns</a></p>',
    ];

    $form['color_palette'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Color Palette'),
    ];
    $form['color_palette']['default_colors'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Hex Color Code|Class Name|Usage Description'),
      '#default_value' => ($config->get('default_colors') ? implode("\r\n", $config->get('default_colors')) : ''),
      '#description' => $this->t('For example: #FF0000|red|Usage text...'),
      '#prefix' => $this->t('<p>Create a list of all the colors you would like represented in your styleguide. Each color should be on a separate line. By default, hex values will be used in an inline style for the color palette section of the styleguide.</p>'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('simple_styleguide.styleguidesettings');
    $config->set('default_patterns', $form_state->getValue('default_patterns'));
    $config->set('default_colors', explode("\r\n", $form_state->getValue('default_colors')));

    $config->save();
  }

}
