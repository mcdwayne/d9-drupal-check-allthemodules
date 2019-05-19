<?php

namespace Drupal\visualn_iframe\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;

/**
 * Provides VisualN IFrame defaults configuration form.
 *
 * @ingroup iframes_toolkit
 */
class ConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'visualn_iframe.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'visualn_iframe_configuration';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('visualn_iframe.settings');
    $form = parent::buildForm($form, $form_state);
    // Set #tree to TRUE since the form is expected to be extended by other modules
    // which should group their own config values to not override others.
    // See visualn_block_form_visualn_iframe_configuration_alter() for an example.
    $form['#tree'] = TRUE;

    $form['visualn_iframe']['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General settings'),
      '#open' => TRUE,
      // @todo: add better description
      '#description' => $this->t('General settings for VisualN IFrame module.'),
    ];
    // @todo: add a link to collect garbage manually
    // @todo: add to a separate fieldset
    // @todo: review data keys
    $form['visualn_iframe']['general']['collect_garbage_cron'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Collect garbage on cron (automatically)'),
      '#description' => $this->t('Collect garbage automatically'),
      '#default_value' => $config->get('collect_garbage_cron'),
    ];
    $form['visualn_iframe']['general']['collect_garbage_period'] = [
      '#type' => 'number',
      '#title' => $this->t('Collect garbage period'),
      '#description' => $this->t('Period of staged entries considered outdated (in seconds).
        Set to 0 to remove all.'),
      '#default_value' => $config->get('collect_garbage_period'),
      '#required' => TRUE,
      '#min' => 0,
    ];


    // @todo: maybe use link_placeholder_url and link_placeholder_title for key names

    $form['visualn_iframe']['default'] = [
      '#type' => 'details',
      '#title' => $this->t('Default settings'),
      '#open' => TRUE,
      '#description' => $this->t('Defaults are used when "Use defaults" is checked
        on iframe configuration form or settings are not provided.<br />
        Also used as default values (initial values and placeholders) for iframes configurations forms.<br />
        If "Used defaults" checked, the settings below are not stored in the respective iframe entry.'),
    ];

    // @todo: add link to the tokens list page
    //   change description depending on whether token module is enabled or not

    $form['visualn_iframe']['default']['origin_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Origin title'),
      '#description' => $this->t('The value is used if origin title is not provided. Leave empty to use global default.'),
      '#default_value' => $config->get('default.origin_title'),
    ];
    // @todo: add 'reset to default' link
    $form['visualn_iframe']['default']['origin_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Origin url'),
      '#description' => $this->t('The value is used if origin url is not provided. Leave empty to use global default. Available tokens: %tokens.', ['%tokens' => '[visualn-iframe:location]']),
      '#default_value' => $config->get('default.origin_url'),
    ];


    // @todo: add link to the tokens list page
    //   change description depending on whether token module is enabled or not
    $form['visualn_iframe']['default']['show_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show origin link'),
      '#description' => $this->t('Show origin link if not set.'),
      '#default_value' => $config->get('default.show_link'),
    ];
    $form['visualn_iframe']['default']['open_in_new_window'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open in new window'),
      '#description' => $this->t('Adds target=_blank attribute to the iframe origin link.'),
      '#default_value' => $config->get('default.open_in_new_window'),
    ];

    $form['visualn_iframe']['fallback'] = [
      '#type' => 'details',
      '#title' => $this->t('Fallback settings'),
      '#open' => TRUE,
      // @todo: add better description
      '#description' => $this->t('Fallback settings for iframe entries <strong>(not used as yet)</strong>.'),
    ];

    $form['visualn_iframe']['fallback']['origin_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Origin title'),
      '#description' => $this->t('Defaults to the site title.'),
      //'#description' => $this->t('Defaults to the site title. Global tokens can be used.'),
      '#default_value' => $config->get('fallback.origin_title'),
    ];
    $form['visualn_iframe']['fallback']['origin_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Origin url'),
      '#description' => $this->t('Defaults to the site path.'),
      '#default_value' => $config->get('fallback.origin_url'),
    ];

    // @todo: consider using redirects for the paths that do not exist (e.g. page was removed)

    // @todo: implement fallback conditions
    //   enabled in submitForm()
    $form['visualn_iframe']['fallback']['conditions'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Fallback conditions'),
      '#options' => [
        // @todo: the option should be always enabled (else use site base path and title)
        //   should it use fallback values for both url and title if only one is empty?
        'empty_defaults' => $this->t('Defaults are empty'),
        'empty_settings' => $this->t('Settings not found'),
        'origin_incompelete' => $this->t('Origin settings empty or incomplete'),
        'origin_broken' => $this->t('Origin settings broken (e.g. token substitutions not found)'),
        'no_tokens' => $this->t('Tokens not found'),
        'invalid_url' => $this->t('Invalid url'),
      ],
      '#disabled' => TRUE,
      '#description' => $this->t('Use fallback values if one of the enabled conditions found.'),
      '#default_value' => $config->get('fallback.conditions'),
    ];





    // @todo: allow user to choose between global and local defaults when
    //   checking the 'use defauls' checkbox on iframe config form (could be implemented by other contribs or custom modules)

    // @todo: local settings are also used as default values on iframe config forms
    //   if overriding chosen

    // @todo: add an additional checkbox or radio button to choose between global and local defaults
    //   add description that local defaults are stored with the entry settings



    // @todo: add a field to restrict usage of external links or path patterns
    //   though external links should still available when used on purpose
    //   maybe add a textarea for allowed/restricted paths and patterns
    //   to avoid fraud
    //   should be also checked at iframe link rendering
    //   enable the option by default

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('visualn_iframe.settings');

    $form_values = $form_state->getValue('visualn_iframe');
    $values = $form_values['general'];
    $values['default'] = $form_values['default'];
    $values['fallback'] = $form_values['fallback'];
    // @todo: remove when fallback conditions enabled
    unset($values['fallback']['conditions']);

    // @todo: maybe use setData() instead to override whole config
    //$config->setData($values);
    foreach ($values as $key => $value) {
      $config->set($key, $value);
    }

    $config->save();
    // cache tags used for rendering iframe link
    //   see Drupal\visualn_iframe\Controller\IFrameController
    // @todo: invalidate cache only if values changed
    $tags = ['visualn_iframe_settings'];
    Cache::invalidateTags($tags);
  }

}
