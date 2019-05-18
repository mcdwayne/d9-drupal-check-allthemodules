<?php

/**
 * @file
 * Contains \Drupal\orgmode\Form\ImportForm.
 */

namespace Drupal\orgmode\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\orgmode\Utils\ParserPHPOrg;
use Drupal;

/**
 * Class ImportForm.
 *
 * @package Drupal\orgmode\Form
 */
class ImportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $content_types = Drupal::entityTypeManager()->getStorage('node_type')->loadMultiple();
    $content_types_list = [];
    foreach ($content_types as $type) {

      $access = Drupal::entityTypeManager()->getAccessControlHandler('node')->createAccess($type->id(), NULL, [], TRUE);
      if ($access->isAllowed()) {
        $content_types_list[$type->id()] = $type->label();
      }

    }

    $form['type'] = array(
      '#type' => 'select',
      '#title' => $this->t('Destination content type'),
      '#required' => TRUE,
      '#options' => $content_types_list,
    );

    $form['file'] = array(
      '#type' => 'file',
      '#title' => $this->t('ORG file'),
      '#required' => FALSE,

    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $file = file_save_upload('file', array('file_validate_extensions' => array('org')), FALSE, 0);
    if ($file) {
      $form_state->setValue("file_upload", $file);
    }
    else {
      $form_state->setErrorByName('file', $this->t('No file was uploaded.'));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $user = \Drupal::currentUser();

    $file = $form_state->getValue('file_upload');

    $parser = new ParserPHPOrg();
    $content = $parser->orgToNode($file->getFileUri());

    if (!$content['title']) {
      $content['title'] = 'Dummy ' . basename($file->getFileUri());
      drupal_set_message($this->t('The org file has not title. Set the title: @title. Please revised it.',
        array('@title' => $content['title'])), 'warning');
    }

    $config = \Drupal::config('orgmode.settings');

    $node = Node::create([
      'type' => $form_state->getValue('type'),
      'created' => REQUEST_TIME,
      'changed' => REQUEST_TIME,
      'uid' => $user->id(),
      'title' => $content['title'],
      'status' => $config->get('published') ? $config->get('published') : 'unpublished',
      'sticky' => $config->get('sticky') ? $config->get('sticky') : 'unsticky',
      'promote' => $config->get('promote') ? TRUE : FALSE,
      'body' => [
        'summary' => $content['teaser'],
        'value' => $content['body'],
        'format' => 'basic_html',
      ],
    ]);

    $node->save();

    drupal_set_message($this->t('Node created'));
  }

}
