<?php

namespace Drupal\landingpage_export\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\Yaml\Yaml;
use Drupal\Core\Archiver\Zip;

/**
 * Class LandingPageExportForm.
 *
 * @package Drupal\landingpage_export\Form
 */
class LandingPageExportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'landingpage_export_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $node = \Drupal::routeMatch()->getParameter('node');

    $targets = $node->get('field_landingpage_paragraphs')->getValue();
    $stuff = _landingpage_export_parser($targets);
    $files = $stuff['files'];    
    $paragraphs = $stuff['paragraphs'];
    $filedir = 'public://landingpage';
    
    // Create a demo directory in the public dir.
    // file_prepare_directory checks, creates and sets permissions on a directory path.
    file_prepare_directory($filedir, FILE_MODIFY_PERMISSIONS | FILE_CREATE_DIRECTORY);
    $filename = 'landingpage_' . str_replace(" ", "_", $node->label()) . ".zip"; // TODO: more safe file name
    $archive_name = $filedir . '/' . $filename;
    if (file_exists($archive_name)) {
      file_unmanaged_delete($archive_name);
    }
    file_unmanaged_save_data('', $archive_name);
    $zip = new Zip(drupal_realpath($archive_name));
    foreach ($files as $file) {
      $zip->add(drupal_realpath($file));
    }
    $array = array(
      //'uuid' => $node->get('uuid')->value, 
      'langcode' => $node->get('langcode')->value, 
      'title' => $node->label(),           
      'type' => 'landingpage',
      'uid' => \Drupal::currentUser()->id(),
      'status' => $node->isPublished(),
      'promote' => $node->get('promote')->value,
      'field_landingpage_theme' => $node->get('field_landingpage_theme')->value,
      'field_landingpage_paragraphs' => $paragraphs,
    );
    $parser = new Yaml();
    $yml = $parser->dump($array, 4);
    $form['yml'] = [
      '#title' => 'Please use YAML code below',
      '#type' => 'textarea',
      '#default_value' => $yml,
      '#rows' => 23,
      '#description' => $this->t('You can create [you_module_name].landingpage.yml for the futher import. Download <a href="@href">archive</a> with images and put them to /image folder in your module folder.', array('@href' => file_create_url($archive_name))),
    ];

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
  }

}
