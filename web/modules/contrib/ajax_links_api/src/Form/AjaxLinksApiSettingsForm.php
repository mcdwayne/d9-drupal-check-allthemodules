<?php

namespace Drupal\ajax_links_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Displays the Ajax links API settings form.
 */
class AjaxLinksApiSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ajax_links_api_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('ajax_links_api.admin_settings');
    $form['ajax_links_api_trigger'] = array(
      '#type' => 'textarea',
      '#title' => t('jQuery selector to trigger ajax (One per line)'),
      '#default_value' => $config->get('ajax_links_api.trigger'),
      '#description' => t('Just like jQuery, for example by providing ".content a" will ajaxify all link under .content. You can also exclude some selectors by specifying ! (for example "!#toolbar a")'),
    );
    $form['ajax_links_api_selector'] = array(
      '#type' => 'textfield',
      '#title' => t('Default Target DIV'),
      '#default_value' => $config->get('ajax_links_api.selector'),
      '#description' => t('This can be override for indivdual link by providing rel. Check Demo.'),
    );
    $form['ajax_links_api_html5'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable URL and Title change (for HTML5 Only)'),
      '#default_value' => $config->get('ajax_links_api.html5'),
      '#description' => t('Change URL and Title according to ajax content. This will work only for HTML5 supported browsers. Tested on latest Chrome,Firefox.'),
    );
    $form['ajax_links_api_scripts_included'] = array(
      '#type' => 'checkbox',
      '#title' => t('Included js-bottom-placeholder in template'),
      '#default_value' => $config->get('ajax_links_api.scripts_included'),
      '#description' => t('If you removed js-bottom-placeholder from html--ajax.html.twig, uncheck this. For details, please check https://drupal.org/node/1923320'),
    );
    $form['ajax_links_api_vpager'] = array(
      '#type' => 'checkbox',
      '#title' => t('Remove ?ajax=1 from Views pager'),
      '#default_value' => $config->get('ajax_links_api.vpager'),
      '#description' => t('Remove ?ajax=1 from Views pager. For details, please check http://drupal.org/node/1907376.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    \Drupal::configFactory()->getEditable('ajax_links_api.admin_settings')
      ->set('ajax_links_api.trigger', $values['ajax_links_api_trigger'])
      ->set('ajax_links_api.selector', $values['ajax_links_api_selector'])
      ->set('ajax_links_api.html5', $values['ajax_links_api_html5'])
      ->set('ajax_links_api.scripts_included', $values['ajax_links_api_scripts_included'])
      ->set('ajax_links_api.vpager', $values['ajax_links_api_vpager'])
      ->save();
    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['ajax_links_api.settings'];
  }

  /**
   * Get trigger classes/ids.
   *
   * Ajax links triggers now come in two varieties:
   *
   *   positive triggers: these are selectors that are used to select
   *     a set of links
   *   negative triggers: these are selectors used in .not() to remove
   *     links from the set of matched ones
   *
   * The user can specify these in the admin/config screen. Negative triggers
   * are those listed with a "!" as the first character.
   */
  public static function ajaxLinksApiGetTriggers() {
    $config = \Drupal::config('ajax_links_api.admin_settings');
    $trigger = $config->get('ajax_links_api.trigger');
    $trigger = explode("\n", $trigger);
    // Trim all entries.
    $trigger = array_map('trim', $trigger);
    // Filter out empty lines.
    $trigger = array_filter($trigger);

    $positive_triggers = array();
    $negative_triggers = array();
    foreach ($trigger as $this_trigger) {
      if (preg_match('/^!/', $this_trigger)) {
        $negative_triggers[] = substr($this_trigger, 1);
      }
      else {
        $positive_triggers[] = $this_trigger;
      }
    }

    $positive_trigger = implode(',', $positive_triggers);
    $negative_trigger = implode(',', $negative_triggers);
    return array($positive_trigger, $negative_trigger);
  }

}
