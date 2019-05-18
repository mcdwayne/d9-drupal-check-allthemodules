<?php

namespace Drupal\md_fontello\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\md_fontello\Archiver\MDZip;

/**
 * Class MDFontelloForm.
 *
 * @package Drupal\md_fontello\Form
 */
class MDFontelloForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $md_fontello = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $md_fontello->label(),
      '#description' => $this->t("Label for the MDFontello."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $md_fontello->id(),
      '#machine_name' => [
        'exists' => '\Drupal\md_fontello\Entity\MDFontello::load',
      ],
      '#disabled' => !$md_fontello->isNew(),
      '#required' => TRUE,
    ];

    $form['import_font'] = [
      '#type' => 'file',
      '#title' => $this->t('Import'),
      '#description' => $this->t('File zip font include from fontello'),
      '#requires' => TRUE
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $file_upload = $this->getRequest()->files->get('files[import_font]', NULL, TRUE);
    if ($file_upload && $file_upload->isValid()) {
      $form_state->setValue('import_fontello', $file_upload->getRealPath());
    }
    else {
      $form_state->setErrorByName('import_fontello', $this->t('The file could not be uploaded.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $md_fontello = $this->entity;
    $folder = $form_state->getValue('id');
    $path = $form_state->getValue('import_fontello');
    $data = $this->importFiles($folder, $path);
    $md_fontello->set('files', serialize($data['files']));
    $md_fontello->set('classes', serialize($data['classes']));
    $status = $md_fontello->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label MDFontello.', [
          '%label' => $md_fontello->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label MDFontello.', [
          '%label' => $md_fontello->label(),
        ]));
    }
    \Drupal::service('library.discovery')->clearCachedDefinitions();
    $form_state->setRedirectUrl($md_fontello->urlInfo('collection'));
  }

  /**
   * Import files css to public files
   * @param $folder
   * @param $path
   * @return array|bool
   */
  protected function importFiles($folder, $path) {
    try {
      $destination_dir = 'public://md-icon';
      file_prepare_directory($destination_dir, FILE_CREATE_DIRECTORY);
      $archiver = new MDZip($path);
      $files = $archiver->listContents();
      $css_files = [];
      $classes = [];
      foreach ($files as &$file) {
        $new_name = preg_replace("/(fontello-\w+\/)/", $folder . "/", $file);
        $archiver->renameFile($file, $new_name);
        preg_match('/(\.html|\.txt|\.json|\.css)/', $file, $matches);
        if (count($matches) > 0) {
          if ($matches[0] == '.json') {
            $data = $archiver->getContent($new_name);
            $data = Json::decode($data);
            $prefix = $data['css_prefix_text'];
            foreach ($data['glyphs'] as $index => $glyph) {
              $classes[] = $prefix . $glyph['css'];
            }
          }
          if ($matches[0] == '.css') {
            $css_files[] = $new_name;
          }
          if ($matches[0] == '.html' || $matches[0] == '.txt') {
            $archiver->remove($new_name);
          }
        }
      }

      $archiver->extract($destination_dir, $archiver->listContents());
      return [
        'classes' => $classes,
        'files' => $css_files,
      ];
    } catch (\Exception $e) {
      return FALSE;
    }
  }

}
