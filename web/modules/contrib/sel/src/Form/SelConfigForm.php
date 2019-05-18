<?php

namespace Drupal\sel\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Safe External Links config form.
 */
class SelConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['sel.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sel_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('sel.settings');
    $options = _sel_rel_defaults();
    $optional_options = _sel_rel_optionals();
    $default_rel = 'noreferrer';

    $form['#title'] = $this->t('Standard Link Settings');

    $form['sel_settings'] = [
      '#type' => 'vertical_tabs',
    ];

    $form['menu_links'] = [
      '#type' => 'details',
      '#title' => $this->t('Menu link'),
      '#group' => 'sel_settings',
      '#open' => TRUE,
    ];

    $form['menu_links']['menu_links__enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable processing external menu links'),
      '#description' => $this->t('When enabled, rendered external menu links get a <code>_blank</code> target and the selected rel as attributes'),
      '#default_value' => empty($config->get('menu_links.enabled')) ? 0 : 1,
    ];

    $form['menu_links']['menu_links__rel'] = [
      '#type' => 'select',
      '#title' => $this->t('Required rel attribute value for external menu links'),
      '#description' => $this->t('One of these rel values are required for protecting the <code>window</code> object of this site'),
      '#options' => $options,
      '#default_value' => $config->get('menu_links.rel') ?: $default_rel,
      '#required' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="menu_links__enabled"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    $form['menu_links']['menu_links__rel_optionals'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Additional rel attribute values for external menu links'),
      '#description' => $this->t('These rel values are optional. Some validators may report invalidity even if the attribute value is valid.'),
      '#options' => $optional_options,
      '#default_value' => $config->get('menu_links.rel_optionals') ?: [],
      '#states' => [
        'visible' => [
          ':input[name="menu_links__enabled"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    $form['link_fields'] = [
      '#type' => 'details',
      '#title' => $this->t('Link formatter defaults'),
      '#group' => 'sel_settings',
    ];

    $form['link_fields']['link_fields__default'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Set Safe External Links' formatter as default formatter for link fields"),
      '#description' => $this->t("Newly added link field will have the <code>sel_link</code> formatter on Field UIs' <code>entity_view_display</code> forms"),
      '#default_value' => empty($config->get('link_fields.default')) ? 0 : 1,
    ];

    $form['link_fields']['link_fields__rel_default'] = [
      '#type' => 'select',
      '#title' => $this->t('Required rel attribute value for link formatter default settings'),
      '#description' => $this->t('One of these rel values are required for protecting the <code>window</code> object of this site'),
      '#options' => $options,
      '#default_value' => $config->get('link_fields.rel_default') ?: $default_rel,
      '#required' => TRUE,
    ];

    $form['link_fields']['link_fields__rel_optionals_default'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Additional rel attribute values for link formatter default settings'),
      '#description' => $this->t('These rel values are optional. Some validators may report invalidity even if the attribute value is valid.'),
      '#options' => $optional_options,
      '#default_value' => $config->get('link_fields.rel_optionals_default') ?: [],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $save_needed = FALSE;
    $config = $this->config('sel.settings');
    $form_values = $form_state->getValues();
    $config_form_keys = [
      'menu_links__enabled',
      'menu_links__rel',
      'menu_links__rel_optionals',
      'link_fields__default',
      'link_fields__rel_default',
      'link_fields__rel_optionals_default',
    ];

    // @TODO Empty field info cache.
    foreach ($config_form_keys as $config_form_key) {
      $config_key = str_replace('__', '.', $config_form_key);
      $config_form_value = $form_values[$config_form_key];
      if ($config_form_value !== $config->get($config_key)) {
        if (is_array($config_form_value)) {
          $config_form_value = array_values(array_filter($config_form_value));
        }
        $config->set($config_key, $config_form_value);
        $save_needed = TRUE;
      }
    }

    if ($save_needed) {
      $config->save();
    }
  }

}
