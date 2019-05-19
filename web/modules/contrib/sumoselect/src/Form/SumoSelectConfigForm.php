<?php

namespace Drupal\sumoselect\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements a ChosenConfig form.
 */
class SumoSelectConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sumoselect_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['sumoselect.settings'];
  }

  /**
   * Configuration form.
   *
   * @todo Structure this form.
   *
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('sumoselect.settings');

    $form['enable_regular'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable on regular pages'),
      '#default_value' => $config->get('enable_regular'),
    ];

    $form['regular_path_patterns'] = [
      '#type' => 'textarea',
      '#rows' => 3,
      '#title' => $this->t('Regular pages to enable sumoselect'),
      '#default_value' => $config->get('regular_path_patterns'),
    ];

    $form['regular_path_excluded_patterns'] = [
      '#type' => 'textarea',
      '#rows' => 3,
      '#title' => $this->t('Regular pages to disable sumoselect'),
      '#default_value' => $config->get('regular_path_excluded_patterns'),
    ];

    $form['enable_admin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable on admin pages'),
      '#default_value' => $config->get('enable_admin'),
    ];

    $form['admin_path_patterns'] = [
      '#type' => 'textarea',
      '#rows' => 3,
      '#title' => $this->t('Admin pages to enable sumoselect'),
      '#default_value' => $config->get('admin_path_patterns'),
    ];

    $form['admin_path_excluded_patterns'] = [
      '#type' => 'textarea',
      '#rows' => 3,
      '#title' => $this->t('Admin pages to disable sumoselect'),
      '#default_value' => $config->get('admin_path_excluded_patterns'),
    ];

    $form['selector'] = [
      '#type' => 'textarea',
      '#rows' => 3,
      '#title' => $this->t('Selector for elements'),
      '#default_value' => $config->get('selector'),
    ];

    $form['selector_to_exclude'] = [
      '#type' => 'textarea',
      '#rows' => 3,
      '#title' => $this->t('Selector to explicitly exclude elements'),
      '#default_value' => $config->get('selector_to_exclude'),
    ];

    $form['selector_to_include'] = [
      '#type' => 'textarea',
      '#rows' => 3,
      '#title' => $this->t('Selector to explicitly include elements'),
      '#default_value' => $config->get('selector_to_include'),
    ];

    // @see http://hemantnegi.github.io/jquery.sumoselect/
    $form['search_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search-Placeholder'),
      '#description' => $this->t('Placeholder for search input.'),
      '#default_value' => $config->get('search_text'),
    ];

    $form['no_match'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nomatch-Placeholder'),
      '#description' => $this->t('Placeholder to display if no itmes matches the search term.'),
      '#default_value' => $config->get('no_match'),
    ];

    $form['locale_ok'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OK-Label'),
      '#description' => $this->t('Label for OK button.'),
      '#default_value' => $config->get('locale_ok'),
    ];

    $form['locale_cancel'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cancel-Label'),
      '#description' => $this->t('Label for Cancel button.'),
      '#default_value' => $config->get('locale_cancel'),
    ];

    $form['locale_selectall'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Selectall-Label'),
      '#description' => $this->t('Label for Select-all button.'),
      '#default_value' => $config->get('locale_selectall'),
    ];

    $form['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#description' => $this->t('The fallback palceholder text to be displayed in the rendered select widget.'),
      '#default_value' => $config->get('placeholder'),
    ];

    $form['csv_disp_count'] = [
      '#type' => 'number',
      '#min' => 0,
      '#step' => 1,
      '#title' => $this->t('Max. not-many-items'),
      '#description' => $this->t('The maximum number of items that are not "many" and will be shown explicitly, for more only the count is shown. 0 for infinite.'),
      '#default_value' => $config->get('csv_disp_count'),
    ];

    $form['caption_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Many-items format'),
      '#description' => $this->t('The format to display count of many items.'),
      '#default_value' => $config->get('caption_format'),
    ];

    $form['caption_format_all_selected'] = [
      '#type' => 'textfield',
      '#title' => $this->t('All-items format'),
      '#description' => $this->t('The format to display count of all items.'),
      '#default_value' => $config->get('caption_format_all_selected'),
    ];

    $form['float_width'] = [
      '#type' => 'number',
      '#min' => 0,
      '#step' => 1,
      '#title' => $this->t('Float width'),
      '#description' => $this->t('Minimum screen width of device below which the options list is rendered in floating popup fashion.'),
      '#default_value' => $config->get('float_width'),
    ];

    $form['force_custom_rendering'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Force custom rendering'),
      '#description' => $this->t('Force the custom modal (Floating list) on all devices below floatWidth resolution.'),
      '#default_value' => $config->get('force_custom_rendering'),
    ];

    $form['ok_cancel_in_multi'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display OK and cancel buttons'),
      '#description' => $this->t('Displays Ok Cancel buttons in desktop mode multiselect also.'),
      '#default_value' => $config->get('ok_cancel_in_multi'),
    ];

    $form['is_click_away_ok'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Click-away OK'),
      '#description' => $this->t('Whether click-away triggers OK.'),
      '#default_value' => $config->get('is_click_away_ok'),
    ];

    $form['select_all'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Select all'),
      '#description' => $this->t('Whether select-all ckecker is displayed.'),
      '#default_value' => $config->get('select_all'),
    ];

    $form['search'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable search'),
      '#description' => $this->t('Whether to enable search.'),
      '#default_value' => $config->get('search'),
    ];

    $form['native_on_device'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Native on device'),
      '#description' => $this->t('The keywords to identify a mobile device from useragent string. The system default select list is rendered on the matched device.'),
      '#default_value' => implode("\n", $config->get('native_on_device')),
    ];

    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * Configuration form submit handler.
   *
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('sumoselect.settings');

    $config
      ->set('enable_regular', (bool) $form_state->getValue('enable_regular'))
      ->set('regular_path_patterns', $form_state->getValue('regular_path_patterns'))
      ->set('regular_path_excluded_patterns', $form_state->getValue('regular_path_excluded_patterns'))
      ->set('enable_admin', (bool) $form_state->getValue('enable_admin'))
      ->set('admin_path_patterns', $form_state->getValue('admin_path_patterns'))
      ->set('admin_path_excluded_patterns', $form_state->getValue('admin_path_excluded_patterns'))
      ->set('selector', $form_state->getValue('selector'))
      ->set('selector_to_exclude', $form_state->getValue('selector_to_exclude'))
      ->set('selector_to_include', $form_state->getValue('selector_to_include'))
      ->set('search_text', $form_state->getValue('search_text'))
      ->set('no_match', $form_state->getValue('no_match'))
      ->set('locale_ok', $form_state->getValue('locale_ok'))
      ->set('locale_cancel', $form_state->getValue('locale_cancel'))
      ->set('locale_selectall', $form_state->getValue('locale_selectall'))
      ->set('placeholder', $form_state->getValue('placeholder'))
      ->set('csv_disp_count', (int) $form_state->getValue('csv_disp_count'))
      ->set('caption_format', $form_state->getValue('caption_format'))
      ->set('caption_format_all_selected', $form_state->getValue('caption_format_all_selected'))
      ->set('float_width', (int) $form_state->getValue('float_width'))
      ->set('force_custom_rendering', (bool) $form_state->getValue('force_custom_rendering'))
      ->set('ok_cancel_in_multi', (bool) $form_state->getValue('ok_cancel_in_multi'))
      ->set('is_click_away_ok', (bool) $form_state->getValue('is_click_away_ok'))
      ->set('select_all', (bool) $form_state->getValue('select_all'))
      ->set('search', (bool) $form_state->getValue('search'))
      ->set('native_on_device', array_filter(preg_split("~(\r\n?|\n)~u",
      $form_state->getValue('native_on_device'))));

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
