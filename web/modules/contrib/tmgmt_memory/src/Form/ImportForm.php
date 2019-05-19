<?php

namespace Drupal\tmgmt_memory\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the memory import form.
 */
class ImportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tmgmt_memory_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['import'] = array(
      '#type' => 'file',
      '#title' => $this->t('Translation memory file'),
      '#description' => $this->t('Allowed types: @extensions.', array('@extensions' => 'tmx')),
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Upload'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $all_files = $this->getRequest()->files->get('files', []);
    if (!empty($all_files['import'])) {
      $file_upload = $all_files['import'];
      if ($file_upload->isValid()) {
        $form_state->setValue('import', $file_upload->getRealPath());
        return;
      }
    }

    $form_state->setErrorByName('import', $this->t('The file could not be uploaded.'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\tmgmt_memory\MemoryManager $memory_manager */
    $memory_manager = \Drupal::service('tmgmt_memory.memory_manager');
    $supported_formats = ['tmx'];
    if ($file = file_save_upload('import', array('file_validate_extensions' => array(implode(' ', $supported_formats))), FALSE, 0)) {
      $dom = new \DOMDocument();
      $dom->load($file->getFileUri());
      $header = $dom->getElementsByTagName('header')->item(0);
      $source_langcode = $header->attributes->getNamedItem('srclang')->nodeValue;
      $translation_units = $dom->getElementsByTagName('tu');
      for ($i = 0; $i < $translation_units->length; $i++) {
        $translation_unit = $translation_units->item($i);
        $childs = $translation_unit->childNodes;
        $data_key = NULL;
        $segment_id = NULL;
        $sources = [];
        $translations = [];
        for ($j = 0; $j < $childs->length; $j++) {
          $child = $childs->item($j);
          if ($child->nodeName == 'tuv') {
            $target_langcode = $child->getAttribute('xml:lang');
            if ($source_langcode != $target_langcode) {
              $tuv_childs = $child->childNodes;
              for ($z = 0; $z < $tuv_childs->length; $z++) {
                $tuv_child = $tuv_childs->item($z);
                if ($tuv_child->nodeName == 'prop' && $tuv_child->getAttribute('type') == 'quality') {
                  $translations[$target_langcode]['quality'] = $tuv_child->nodeValue;
                }
                elseif ($tuv_child->nodeName == 'seg') {
                  $target_data = html_entity_decode($tuv_child->nodeValue);
                  $translations[$target_langcode]['values'][$segment_id] = $target_data;
                }
              }
            }
            else {
              $source_data = html_entity_decode($child->nodeValue);
              $sources[$segment_id] = $source_data;
            }
          }
          elseif ($child->nodeName == 'prop') {
            if ($child->getAttribute('type') == 'data-key') {
              $data_key = $child->nodeValue;
            }
            if ($child->getAttribute('type') == 'segment-id') {
              $segment_id = $child->nodeValue;
            }
          }
        }
        if ($sources) {
          foreach ($sources as $id => $source_segment) {
            $source = $memory_manager->addUsage($source_langcode, $source_segment, NULL, $data_key, $id);
            foreach ($translations as $target_langcode => $translation) {
              if (isset($translation['values'][$id])) {
                $target = $memory_manager->addUsage($target_langcode, $translation['values'][$id], NULL, $data_key, $id);
                $memory_manager->addUsageTranslation($source, $target, $translation['quality']);
              }
            }
          }
        }
      }
    }
    $form_state->setRedirect('view.tmgmt_memory.page_1');
    drupal_set_message(t('File imported successfully.'));
  }

}
