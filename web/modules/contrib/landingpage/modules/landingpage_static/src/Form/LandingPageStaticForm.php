<?php

namespace Drupal\landingpage_static\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Archiver\Zip;
use Drupal\Core\StreamWrapper\PublicStream;

/**
 * Class LandingPageStaticForm.
 *
 * @package Drupal\landingpage_static\Form
 */
class LandingPageStaticForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'landingpage_static_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;
    $node = \Drupal::routeMatch()->getParameter('node');

    $targets = $node->get('field_landingpage_paragraphs')->getValue();
    $stuff = _landingpage_static_parser($targets);
    $files = $stuff['files'];
    $public = PublicStream::basePath();

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
    $html = file_get_contents($base_url . "/node/" . $node->id());
    foreach ($files as $key => $file) {
      $file = str_replace("public://", "", $file);
      $html = str_replace("/" . $public . "/" . $file, "images/" . $file, $html);
    }
    $form['html'] = [
      '#title' => 'Please save HTML code below in index.html',
      '#type' => 'textarea',
      '#default_value' => $html,
      '#rows' => 23,
      '#description' => $this->t('Download <a href="@href">images</a> and put them to "/images" folder next to your index.html.', array('@href' => file_create_url($archive_name))),
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
