<?php

/**
 * @file
 * Contains \Drupal\ayah\Form\AYAHAdminSettingsForm.
 */

namespace Drupal\ayah\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Configure custom settings for this site.
 */
class AYAHAdminSettingsForm extends ConfigFormBase {

  /**
   * Constructs a new AYAHAdminSettingsForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ayah_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ayah.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Load existing settings.
    $ayah = \Drupal::config('ayah.settings');

    // Configuration of which forms to protect, with what challenge.
    $form['ayah_form_protection']['ayah_form_id_overview'] = array(
      '#type' => 'details',
      '#title' => t('Form protection'),
      '#description' => t("Enter the form id(s) you want the AYAH game to appear on.  Some common id's include <em>comment_node_page_form, contact_personal_form, contact_site_form, forum_node_form, user_login, user_login_block, user_pass, user_register_form.</em>"),
      '#prefix' => '<div id="ayah-row-wrapper">',
      '#suffix' => '</div>',
      '#open' => TRUE,
    );

    _ayah_form_id_table($ayah, $form, $form_state);

    // Field for the AYAH game on admin pages.
    $form['ayah_form_protection']['ayah_allow_on_admin_pages'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow AYAH games on administrative pages'),
      '#default_value' => $ayah->get('ayah_allow_on_admin_pages') != NULL ? $ayah->get('ayah_allow_on_admin_pages') : 0,
      '#description' => t("This option makes it possible to add AYAH games to forms on administrative pages. AYAH games are disabled by default on administrative pages (which shouldn't be accessible to untrusted users normally) to avoid the related overhead. In some situations, e.g. in the case of demo sites, it can be usefull to allow AYAH games on administrative pages."),
    );

    // Field for AYAH form id finder.
    $form['ayah_form_protection']['ayah_form_finder'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display form id of current form'),
      '#default_value' => $ayah->get('ayah_form_finder') != NULL ? $ayah->get('ayah_form_finder') : 0,
      '#description' => t("This option displays the form id with a status message making it easier to find form ids."),
    );

    // Authentication section.
    $form['authentication'] = array(
      '#type' => 'details',
      '#title' => t('Authentication'),
      '#description' => t('The Publisher and Scoring Keys associated with your domain on the <a href="http://portal.areyouahuman.com/" target="_blank">portal</a>'),
      '#open' => TRUE,
    );

    // Publisher key.
    $form['authentication']['ayah_publisher_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Publisher key'),
      '#default_value' => $ayah->get('ayah_publisher_key') != NULL ? $ayah->get('ayah_publisher_key') : '',
      '#required' => TRUE,
    );

    // Scoring key.
    $form['authentication']['ayah_scoring_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Scoring key'),
      '#default_value' => $ayah->get('ayah_scoring_key') != NULL ? $ayah->get('ayah_scoring_key') : '',
      '#required' => TRUE,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('ayah.settings');

    $config->set('ayah_allow_on_admin_pages', $form_state->getValue(array('ayah_allow_on_admin_pages')))
      ->set('ayah_form_finder', $form_state->getValue(array('ayah_form_finder')))
      ->set('ayah_publisher_key', $form_state->getValue(array('ayah_publisher_key')))
      ->set('ayah_scoring_key', $form_state->getValue(array('ayah_scoring_key')));

    // Process ayah forms.
    $form_config = array();
    foreach ($form_state->getValue('ayah_ayah_forms') as $key => $ayah_new_form_id) {
      if (!empty($ayah_new_form_id['form_id'])) {
        $form_config[$key] = $ayah_new_form_id['form_id'];
      }
    }
    $config->set('ayah_forms', $form_config);

    $config->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('ayah_ayah_forms') as $key => $ayah_new_form_id) {
      if (!preg_match('/^[a-z0-9_]*$/', $ayah_new_form_id['form_id'])) {
        $element = 'ayah_ayah_forms][' . $key . '][form_id';
        $form_state->setErrorByName($element, t('Illegal form_id'));
      }
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * Submit function for adding additional form_id media rows.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function formIdRows($form, FormStateInterface &$form_state) {
    return $form['ayah_form_protection']['ayah_form_id_overview'];
  }

}

/**
 * Helper function to render a table for the form_ids.
 *
 * Determines if the add/remove buttons have been clicked for the appropriate
 * number of rows.
 *
 * @param \Drupal\Core\Config\ImmutableConfig $ayah
 *   The config object for this form.
 * @param array $form
 *   An associative array containing the structure of the form.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current state of the form.
 */
function _ayah_form_id_table(ImmutableConfig $ayah, &$form, FormStateInterface &$form_state) {
  $form_id_table = $ayah->get('ayah_forms');
  // Check to see how many form_id media rows we need.
  if (isset($form_id_table) && !$form_state->isRebuilding()) {
    $form_state->setValue('form_id_rows', $form_state->getCompleteForm()['ayah']['form_id_rows']['#value'] ?
      $form_state->getCompleteForm()['ayah']['form_id_rows']['#value'] : count($form_id_table));
  }
  elseif ($form_state->isRebuilding() && $form_state->getValue('op')->render() == 'Add another') {
    $form_state->setValue('form_id_rows', ($form_state->getCompleteForm()['ayah']['form_id_rows']['#value'] ?
        $form_state->getCompleteForm()['ayah']['form_id_rows']['#value'] : count($form_id_table)) + 1);
  }
  elseif ($form_state->isRebuilding() && $form_state->getValue('op')->render() == 'Delete') {
    $form_state->setValue('form_id_rows', ($form_state->getCompleteForm()['ayah']['form_id_rows']['#value'] ?
        $form_state->getCompleteForm()['ayah']['form_id_rows']['#value'] : count($form_id_table)) - 1);

    unset($form_id_table[$form_state->getTriggeringElement()['#array_parents'][3]]);
    unset($form['ayah_form_protection']['ayah_form_id_overview']['ayah_ayah_forms'][$form_state->getTriggeringElement()['#array_parents'][3]]);

    if ($form_state->getValue('form_id_rows') == 0) {
      $form_state->setValue('form_id_rows', 1);
    }
  }
  else {
    $form_state->setValue('form_id_rows', 1);
  }
  $form['ayah']['form_id_rows']['#value'] = $form_state->getValue('form_id_rows');

  // List known form_ids.
  $form['ayah_form_protection']['ayah_form_id_overview']['ayah_ayah_forms'] = array(
    '#type' => 'table',
    '#header' => array(t('Form ID'), t('Operations')),
  );

  if (empty($form_id_table)) {
    drupal_set_message(t('No forms are configured.  Please add some in the AYAH configuration.'), 'warning');

    if ($form_state->getValue('form_id_rows') == 0) {
      // Form items for new form_id.
      _ayah_admin_add_form_id_row($form, 0, array());
    }
  }

  for ($i = 0; $i <= $form_state->getValue('form_id_rows'); $i++) {
    if (isset($form_id_table[$i])) {
      _ayah_admin_add_form_id_row($form, $i, $form_id_table);
    }
    else {
      _ayah_admin_add_form_id_row($form, $i, $form_id_table);
    }
  }

  $form['ayah_form_protection']['add_form_id_row'] = array(
    '#type' => 'button',
    '#value' => t('Add another'),
    '#ajax' => array(
      'callback' => 'Drupal\ayah\Form\AYAHAdminSettingsForm::formIdRows',
      'wrapper' => 'ayah-row-wrapper',
    ),
  );
}

/**
 * Helper function to add a row in the form_id table.
 *
 * @param array $form
 *   An associative array containing the structure of the form.
 * @param int $key
 *   The index for the form value.
 * @param array $form_id_value
 *   An array with data for the form_id table.
 */
function _ayah_admin_add_form_id_row(&$form, $key = 0, $form_id_value = array()) {
  $form['ayah_form_protection']['ayah_form_id_overview']['ayah_ayah_forms'][$key] = array();

  // Textfield for form_id.
  $form['ayah_form_protection']['ayah_form_id_overview']['ayah_ayah_forms'][$key]['form_id']['#type'] = 'textfield';

  if (isset($form_id_value[$key])) {
    $form['ayah_form_protection']['ayah_form_id_overview']['ayah_ayah_forms'][$key]['form_id']['#default_value'] = $form_id_value[$key];
    $form['ayah_form_protection']['ayah_form_id_overview']['ayah_ayah_forms'][$key]['operations'] = array(
      '#type' => 'button',
      '#value' => t('Delete'),
      '#ajax' => array(
        'callback' => 'Drupal\ayah\Form\AYAHAdminSettingsForm::formIdRows',
        'wrapper' => 'ayah-row-wrapper',
      ),
    );
  }
  else {
    // Holder for operations.
    $form['ayah_form_protection']['ayah_form_id_overview']['ayah_ayah_forms'][$key]['operations']['#markup'] = '';
  }
}
