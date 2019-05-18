<?php

namespace Drupal\dropcap_ckeditor\Form;

use Drupal\Component\Utility\Bytes;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Provides an image dialog for text editors.
 */
class EditorDropcapDialog extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'editor_dropcap_dialog';
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\filter\Entity\FilterFormat $filter_format
   *   The filter format for which this dialog corresponds.
   */
  public function buildForm(array $form, FormStateInterface $form_state, FilterFormat $filter_format = NULL) {
    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';
    $form['#prefix'] = '<div id="editor-dropcap-dialog-form">';
    $form['#suffix'] = '</div>';
    
    $editor = editor_load($filter_format->id());
    if($filter_format->id() == 'basic_html') {
      $form['help_text'] = array(
        '#type' => 'markup',
        '#markup' => '<p>DropCap was not supported for Basic HTML Format, Please change it to Full HTML! Since Basic HTML format does not support all HTML tags.</p>',
      );
    }else {
      $form['dropcap_text'] = array(
        '#type' => 'textarea',
        '#title' => t('Enter Text:'),
        '#required' => TRUE,
      );
      
      $form['dropcap_font_size'] = array(
        '#type' => 'textfield',
        '#title' => t('Font Size (in pixel):'),
        '#required' => TRUE,
      );
      
      $form['dropcap_font_color'] = array(
        '#type' => 'textfield',
        '#title' => t('Font Color (Ex: #000000)'),
        '#required' => TRUE,
      );
      
      $form['actions']['save_modal'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Save'),
        '#submit' => array(),
        '#ajax' => array(
          'callback' => '::submitForm',
          'event' => 'click',
        ),
      );
    }

    return $form;
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $dropcapText = $form_state->getValue(array('dropcap_text', ''));
    if(!empty($dropcapText)) {
      $form_state->setValue(array('attributes', 'dropcap_text'), '');
    }
    $dropcapFontSize = $form_state->getValue(array('dropcap_font_size', ''));
    if(!empty($dropcapFontSize)) {
      $form_state->setValue(array('attributes', 'dropcap_font_size'), '');
    }
    $dropcapFontColor = $form_state->getValue(array('dropcap_font_color', ''));
    if(!empty($dropcapFontColor)) {
      $form_state->setValue(array('attributes', 'dropcap_font_color'), '');
    }
    
    if ($form_state->getErrors()) {
      unset($form['#prefix'], $form['#suffix']);
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $response->addCommand(new HtmlCommand('#editor-dropcap-dialog-form', $form));
    }
    else {
      $response->addCommand(new EditorDialogSave($form_state->getValues()));
      $response->addCommand(new CloseModalDialogCommand());
    }
    return $response;
  }
}