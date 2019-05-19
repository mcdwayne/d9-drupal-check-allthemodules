<?php
/**
 * @file
 * Contains \Drupal\doc_serialization\Plugin\views\display_extender\DocSerialzation.
 */
namespace Drupal\doc_serialization\Plugin\views\display_extender;

use Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
/**
 * Doc Serialzation display extender plugin.
 *
 * @ingroup views_display_extender_plugins
 *
 * @ViewsDisplayExtender(
 *   id = "doc_serialization",
 *   title = @Translation("Doc Serialzation display extender"),
 *   help = @Translation("Settings to add MS Word template for this view."),
 *   no_ui = FALSE
 * )
 */
class DocSerialization extends DisplayExtenderPluginBase {
  /**
   * Provide the key options for this plugin.
   */
  public function defineOptionsAlter(&$options) {
    $options['doc_serialization'] =  array(
      'contains' => array(
        'title' => array('default' => ''),
        'description' => array('default' => ''),
      )
    );
  }
  /**
   * Provide the default summary for options and category in the views UI.
   */
  public function optionsSummary(&$categories, &$options) {

    $categories['doc_serialization'] = array(
      'title' => t('Doc Serialization'),
      'column' => 'second',
    );
    $doc_serialization = $this->hasValues() ? $this->getValues() : FALSE;
    if (!empty($doc_serialization)) {
      $file = File::load($doc_serialization['template_file'][0]);
    }
    $options['doc_serialization'] = array(
      'category' => 'doc_serialization',
      'title' => t('Template File'),
      'value' =>  isset($file) ? $file->getFilename() : $this->t('none'),
    );
  }
  /**
   * Provide a form to edit options for this plugin.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    if ($form_state->get('section') == 'doc_serialization') {
      $form['#title'] .= t('Doc Serialization Settings');
      $doc_serialization = $this->getValues();
      $form['doc_serialization']['#type'] = 'container';
      $form['doc_serialization']['#tree'] = TRUE;

      if (!empty($doc_serialization)) {
        $file = File::load($doc_serialization['template_file'][0]);
      }

      $form['doc_serialization']['template_file'] = [
        '#type' => 'managed_file',
        '#title' => $this->t('Template File'),
        '#description' => $this->t('Allowed extensions: docx'),
        '#upload_location' => 'public://doc_serialization/templates',
        '#multiple' => FALSE,
        '#upload_validators' => [
          'file_validate_extensions' => array('docx'),
          // 'file_validate_size' => array(25600000)
        ],
        '#default_value' => isset($file) ? [$file->id()] : [],
      ];
    }
  }
  /**
   * Validate the options form.
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
      $doc_serialization = $form_state->getValue('doc_serialization');
  }
  /**
   * Handle any special handling on the validate form.
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    if ($form_state->get('section') == 'doc_serialization') {
      $doc_serialization = $form_state->getValue('doc_serialization');
      $this->options['doc_serialization'] = $doc_serialization;

      $file = File::load($doc_serialization['template_file'][0]);
      $file->setPermanent();
      $file->save();
    }
  }
  /**
   * Set up any variables on the view prior to execution.
   */
  public function preExecute() { }
  /**
   * Inject anything into the query that the display_extender handler needs.
   */
  public function query() { }
  /**
   * Static member function to list which sections are defaultable
   * and what items each section contains.
   */
  public function defaultableSections(&$sections, $section = NULL) { }
  /**
   * Identify whether or not the current display has custom metadata defined.
   */
  public function hasValues() {
    $doc_serialization = $this->getValues();
    return !empty($doc_serialization['template_file']);
  }
  /**
   * Get the head metadata configuration for this display.
   *
   * @return array
   *   The head metadata values.
   */
  public function getValues() {
    $doc_serialization = isset($this->options['doc_serialization']) ? $this->options['doc_serialization'] : [];
    return $doc_serialization;
  }
}
