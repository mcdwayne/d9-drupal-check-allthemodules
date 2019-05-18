<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 3/15/2016
 * Time: 3:27 PM
 */

namespace Drupal\forena\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\forena\DocManager;

/**
 * Implements \Drupal\forena\Form\DocumentConfigForm
 */
class DocumentConfigForm extends ConfigFormBase {

  protected $possible_docs = [];

  public function __construct() {
    // Load the possible documents based on the service.
    $pm = \Drupal::service('frxplugin.manager.document');
    $plugins = $pm->getDefinitions();
    foreach ($plugins as $plugin) {
      $id = $plugin['id'];
      $name = $plugin['name'];
      $ext = $plugin['ext'];
      if ($id != 'drupal' ) $this->possible_docs[$id] = "($ext)$name";
    }
    asort($this->possible_docs);
  }

  /**
   * {@inherit}
   */
  public function getFormId() {
    return 'forena_document_configuration_form';
  }

  public function getEditableConfigNames() {
    return ['forena.settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $possible_docs = DocManager::instance()->getDocTypes();
    asort($possible_docs);
    $doc_options = array_combine($possible_docs, $possible_docs);
    $config = $this->config('forena.settings');
    unset($doc_options['drupal']);
    $doc_formats = $config->get('doc_formats');
    $key = array_search('drupal', $doc_formats);
    unset($doc_formats[$key]);

    // Load the possible document formats
    $form['doc_formats'] = [
      '#type' => 'checkboxes',
      '#title' => t('Allowed Document Types'),
      '#options' => $this->possible_docs,
      '#descriptions' => t('Document types allowed for reports. Only check one per document type'),
      '#default_value' => $doc_formats,
    ];

    // Determine the default document formats
    $form['doc_defaults'] = [
      '#type' => 'checkboxes',
      '#title' => t('Default Dcoument Types'),
      '#options' => $doc_options,
      '#description' => t('Document Types enabled if none are specified'),
      '#default_value' => $config->get('doc_defaults'),
    ];

    $form['email_override'] = array(
      '#type' => 'checkbox',
      '#title' => 'Run email merges in test mode' ,
      '#default_value' => $config->get('email_override'),
      '#description' => t('When this box is checked emails are sent to the currently logged in user.  Useful for testing environments.'),
    );

    // Determine possible input format options
    $formats = filter_formats();
    $options = ['none' => $this->t('None')];
    foreach ($formats as $format) {
      $options[$format->id()] = $format->label();
    }

    // Email Input format
    $form['email_input_format'] = [
      '#type' => 'select',
      '#title' => t('Email Text Format'),
      '#options' => $options,
      '#description' => $this->t('Process Mail merge reports reports using specified Text Formats. This can be overridden at the skin or report level.'),
      '#default_value' => $config->get('email_input_format')
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inherit}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $doc_formats = array_filter($values['doc_formats']);
    // You can't disable the drupal document format
    $doc_formats['drupal'] = 'drupal';
    $doc_defaults = array_filter($values['doc_defaults']);
    $this->config('forena.settings')
      ->set('doc_formats', array_values($doc_formats))
      ->set('doc_defaults', array_values($doc_defaults))
      ->set('email_input_format', $values['email_input_format'])
      ->set('email_override', $values['email_override'])
      ->save();

    parent::submitForm($form, $form_state);
  }


}