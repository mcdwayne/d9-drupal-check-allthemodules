<?php

/**
 * @file
 * Contains \Drupal\po_translations_report\Form\PoTranslationsReportAdmin.
 */

namespace Drupal\po_translations_report\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\po_translations_report\DetailsDisplayerPluginManager;
use Drupal\po_translations_report\DisplayerPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form.
 */
class PoTranslationsReportAdmin extends ConfigFormBase {

  /**
   * Name of the config being edited.
   */
  const CONFIGNAME = 'po_translations_report.admin_config';

  /**
   * DisplayerPluginManager service.
   */
  private $displayerPluginManager;

  /**
   * DetailsDisplayerPluginManager service.
   */
  private $detailsDisplayerPluginManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, DisplayerPluginManager $displayer_plugin_manager, DetailsdisplayerPluginManager $details_displayer_plugin_manager) {
    parent::__construct($config_factory);
    $this->displayerPluginManager = $displayer_plugin_manager;
    $this->detailsDisplayerPluginManager = $details_displayer_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('config.factory'),
        $container->get('plugin.manager.po_translations_report.displayer'),
        $container->get('plugin.manager.po_translations_report.detailsdisplayer')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array(static::CONFIGNAME);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'po_translations_report_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::CONFIGNAME);
    $form['folder_path'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Folder path'),
      '#description' => $this->t('Add the complete path to the folder that contains po files.'),
      '#default_value' => $config->get('folder_path'),
    );

    $form['display_method'] = array(
      '#type' => 'select',
      '#title' => $this->t('Display method'),
      '#description' => $this->t('Select the display method you want to use.'),
      '#empty_value' => '',
      '#options' => $this->getDisplayPluginInformations()['labels'],
      '#default_value' => $config->get('display_method'),
      '#required' => TRUE,
      '#ajax' => array(
        'callback' => array(get_class($this), 'buildAjaxDisplayConfigForm'),
        'wrapper' => 'po-translations-report-display-config-form',
        'method' => 'replace',
        'effect' => 'fade',
      ),
    );

    $this->buildDisplayConfigForm($form, $form_state);
    $form['details_display_method'] = array(
      '#type' => 'select',
      '#title' => $this->t('Details display method'),
      '#description' => $this->t('Select the display method you want to use for details.'),
      '#empty_value' => '',
      '#options' => $this->getDetailsDisplayPluginInformations()['labels'],
      '#default_value' => $config->get('details_display_method'),
      '#required' => TRUE,
      '#ajax' => array(
        'callback' => array(get_class($this), 'buildAjaxDetailsDisplayConfigForm'),
        'wrapper' => 'po-translations-report-details-display-config-form',
        'method' => 'replace',
        'effect' => 'fade',
      ),
    );

    $this->buildDetailsDisplayConfigForm($form, $form_state);

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
    );
    return $form;
  }

  /**
   * Get definition of Display plugins from their annotation definition.
   *
   * @return array
   *   Array with 'labels' and 'descriptions' as keys containing plugin ids
   *   and their labels or descriptions.
   */
  public function getDisplayPluginInformations() {
    $options = array(
      'labels' => array(),
      'descriptions' => array(),
    );
    foreach ($this->getDisplayerPluginManager()->getDefinitions() as $plugin_id => $plugin_definition) {
      $options['labels'][$plugin_id] = Html::escape($plugin_definition['label']);
      $options['descriptions'][$plugin_id] = Html::escape($plugin_definition['description']);
    }
    return $options;
  }

  /**
   * Get definition of Details display plugins from their annotation definition.
   *
   * @return array
   *   Array with 'labels' and 'descriptions' as keys containing plugin ids
   *   and their labels or descriptions.
   */
  public function getDetailsDisplayPluginInformations() {
    $options = array(
      'labels' => array(),
      'descriptions' => array(),
    );
    foreach ($this->getDetailsDisplayerPluginManager()->getDefinitions() as $plugin_id => $plugin_definition) {
      $options['labels'][$plugin_id] = Html::escape($plugin_definition['label']);
      $options['descriptions'][$plugin_id] = Html::escape($plugin_definition['description']);
    }
    return $options;
  }

  /**
   * Subform.
   *
   * It will be updated with Ajax to display the configuration of a
   * dipslay plugin method.
   *
   * @param array $form
   *   The form array.
   * @param FormStateInterface $form_state
   *   The form state object.
   */
  public function buildDisplayConfigForm(array &$form, FormStateInterface $form_state) {
    $form['displayer_config'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'id' => 'po-translations-report-display-config-form',
      ),
      '#tree' => TRUE,
    );
    $config = $this->config(static::CONFIGNAME);
    if ($form_state->getValue('display_method') != '') {
      // It is due to the ajax.
      $displayer_plugin_id = $form_state->getValue('display_method');
    }
    else {
      $displayer_plugin_id = $config->get('display_method');
      $ajax_submitted_empty_value = $form_state->getValue('form_id');
    }
    $form['displayer_config']['#type'] = 'details';
    $form['displayer_config']['#title'] = $this->t('Configure displayer %plugin', array('%plugin' => $this->getDisplayPluginInformations()['labels'][$displayer_plugin_id]));
    $form['displayer_config']['#description'] = $this->getDisplayPluginInformations()['descriptions'][$displayer_plugin_id];
    $form['displayer_config']['#open'] = TRUE;
    // If the form is submitted with ajax and the empty value is chosen or if
    // there is no configuration yet and no extraction method was chosen in the
    // form.
    if (isset($ajax_submitted_empty_value) || !$displayer_plugin_id) {
      $form['displayer_config']['#title'] = $this->t('Please make a choice');
      $form['displayer_config']['#description'] = $this->t('Please choose an display method in the list above.');
    }

    if ($displayer_plugin_id && !isset($ajax_submitted_empty_value)) {
      $configuration = $config->get($displayer_plugin_id . '_configuration');
      $displayer_plugin = $this->getDisplayerPluginManager()->createInstance($displayer_plugin_id, $configuration);
      $displayer_form = $displayer_plugin->buildConfigurationForm(array(), $form_state);

      $form['displayer_config'] += $displayer_form;
    }
  }

  /**
   * Subform.
   *
   * It will be updated with Ajax to display the configuration of a
   * details dipslay plugin method.
   *
   * @param array $form
   *   The form array.
   * @param FormStateInterface $form_state
   *   The form state object.
   */
  public function buildDetailsDisplayConfigForm(array &$form, FormStateInterface $form_state) {
    $form['details_displayer_config'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'id' => 'po-translations-report-details-display-config-form',
      ),
      '#tree' => TRUE,
    );
    $config = $this->config(static::CONFIGNAME);
    if ($form_state->getValue('details_display_method') != '') {
      // It is due to the ajax.
      $details_displayer_plugin_id = $form_state->getValue('details_display_method');
    }
    else {
      $details_displayer_plugin_id = $config->get('details_display_method');
      $ajax_submitted_empty_value = $form_state->getValue('form_id');
    }
    $form['details_displayer_config']['#type'] = 'details';
    $form['details_displayer_config']['#title'] = $this->t('Configure details displayer %plugin', array('%plugin' => $this->getDetailsDisplayPluginInformations()['labels'][$details_displayer_plugin_id]));
    $form['details_displayer_config']['#description'] = $this->getDetailsDisplayPluginInformations()['descriptions'][$details_displayer_plugin_id];
    $form['details_displayer_config']['#open'] = TRUE;
    // If the form is submitted with ajax and the empty value is chosen or if
    // there is no configuration yet and no extraction method was chosen in the
    // form.
    if (isset($ajax_submitted_empty_value) || !$details_displayer_plugin_id) {
      $form['details_displayer_config']['#title'] = $this->t('Please make a choice');
      $form['details_displayer_config']['#description'] = $this->t('Please choose an details display method in the list above.');
    }

    if ($details_displayer_plugin_id && !isset($ajax_submitted_empty_value)) {
      $configuration = $config->get($details_displayer_plugin_id . '_configuration');
      $details_displayer_plugin = $this->getDetailsDisplayerPluginManager()->createInstance($details_displayer_plugin_id, $configuration);
      $details_displayer_form = $details_displayer_plugin->buildConfigurationForm(array(), $form_state);

      $form['details_displayer_config'] += $details_displayer_form;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Check if the path is for valid readable folder.
    $folder_path = $form_state->getValue('folder_path');
    if ($folder_path == '') {
      $form_state->setErrorByName('folder_path', $this->t('Please fill in a directory path.'));
    }
    elseif (!is_dir($folder_path)) {
      $form_state->setErrorByName('folder_path', $this->t('%folder_path is not a directory.', array('%folder_path' => $folder_path)));
    }
    elseif (!is_readable($folder_path)) {
      $form_state->setErrorByName('folder_path', $this->t('%folder_path is not a readable directory.', array('%folder_path' => $folder_path)));
    }

    $config = $this->config(static::CONFIGNAME);
    // Add display method form.
    $displayer_plugin_id = $form_state->getValue('display_method');
    if ($displayer_plugin_id) {
      $configuration = $config->get($displayer_plugin_id . '_configuration');
      $displayer_plugin = $this->getDisplayerPluginManager()->createInstance($displayer_plugin_id, $configuration);
      $displayer_plugin->validateConfigurationForm($form, $form_state);
    }
    // Add details display method form.
    $details_displayer_plugin_id = $form_state->getValue('details_display_method');
    if ($details_displayer_plugin_id) {
      $configuration = $config->get($details_displayer_plugin_id . '_configuration');
      $details_displayer_plugin = $this->getDetailsDisplayerPluginManager()->createInstance($details_displayer_plugin_id, $configuration);
      $details_displayer_plugin->validateConfigurationForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config(static::CONFIGNAME)
        ->set('folder_path', $form_state->getValue('folder_path'))
        ->save();

    $config = $this->config(static::CONFIGNAME);
    $editable_config = \Drupal::configFactory()->getEditable(static::CONFIGNAME);

    // Treat the displayer methods.
    $displayer_plugin_id = $form_state->getValue('display_method');
    if ($displayer_plugin_id) {
      $configuration = $config->get($displayer_plugin_id . '_configuration');
      $displayer_plugin = $this->getDisplayerPluginManager()->createInstance($displayer_plugin_id, $configuration);
      $displayer_plugin->submitConfigurationForm($form, $form_state);
    }

    // Set the display method variable.
    $editable_config->set('display_method', $displayer_plugin_id);
    $editable_config->save();

    // Treat the details displayer methods.
    $details_displayer_plugin_id = $form_state->getValue('details_display_method');
    if ($details_displayer_plugin_id) {
      $configuration = $config->get($details_displayer_plugin_id . '_configuration');
      $details_displayer_plugin = $this->getDetailsDisplayerPluginManager()->createInstance($details_displayer_plugin_id, $configuration);
      $details_displayer_plugin->submitConfigurationForm($form, $form_state);
    }

    // Set the details display method variable.
    $editable_config->set('details_display_method', $details_displayer_plugin_id);
    $editable_config->save();

    // Show the "configuration is saved" message.
    parent::submitForm($form, $form_state);

    // Redirect to reports page.
    $route = 'po_translations_report.content';
    $form_state->setRedirect($route);
  }

  /**
   * Ajax callback.
   *
   * @param array $form
   *   The form array.
   * @param FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   subform
   */
  public static function buildAjaxDisplayConfigForm(array $form, FormStateInterface $form_state) {
    // We just need to return the relevant part of the form here.
    return $form['displayer_config'];
  }

  /**
   * Ajax callback.
   *
   * @param array $form
   *   The form array.
   * @param FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   subform
   */
  public static function buildAjaxDetailsDisplayConfigForm(array $form, FormStateInterface $form_state) {
    // We just need to return the relevant part of the form here.
    return $form['details_displayer_config'];
  }

  /**
   * Returns the displayer plugin manager.
   *
   * @return \Drupal\po_translations_report\DisplayerPluginManager
   *   The displayer plugin manager.
   */
  protected function getDisplayerPluginManager() {
    return $this->displayerPluginManager ?: \Drupal::service('plugin.manager.po_translations_report.displayer');
  }

  /**
   * Returns the details displayer plugin manager.
   *
   * @return \Drupal\po_translations_report\DetailsDisplayerPluginManager
   *   The details displayer plugin manager.
   */
  protected function getDetailsDisplayerPluginManager() {
    return $this->detailsDisplayerPluginManager ?: \Drupal::service('plugin.manager.po_translations_report.detailsdisplayer');
  }

}
