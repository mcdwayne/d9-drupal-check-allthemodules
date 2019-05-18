<?php

namespace Drupal\gated_file\Plugin\Field\FieldWidget;

use Drupal\file\Plugin\Field\FieldWidget\FileWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\contact\Entity\ContactForm;
use Drupal\gated_file\Entity\GatedFile;

/**
 * Plugin implementation of the 'gated_file' widget.
 *
 * @FieldWidget(
 *   id = "gated_file",
 *   label = @Translation("Gated file"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class GatedFileWidget extends FileWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'form' => NULL,
      'form_per_file' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $default_value = $this->getSetting('form') ? ContactForm::load($this->getSetting('form')) : NULL;

    $element['form'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Default Form'),
      '#target_type' => 'contact_form',
      '#default_value' => $default_value,
      '#description' => $this->t('This is the default value when a new file is created.'),
    ];

    $element['form_per_file'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow to specify a form per file'),
      '#default_value' => $this->getSetting('form_per_file'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = $this->t('Contact Form: @form', ['@form' => $this->getSetting('form')]);
    $summary[] = $this->t('Allow choosing a different form per file: @form_per_file', ['@form_per_file' => $this->getSetting('form_per_file') ? 'Yes' : 'No']);

    return $summary;
  }

  /**
   * Add the input to allow the user to select the form to display.
   *
   * This method is assigned as a #process callback in formElement() method.
   *
   * @return array
   *   The elements with parents fields.
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    if ($element['#files']) {
      /** @var \Drupal\file\Entity\File $file */
      foreach ($element['#files'] as $file) {
        // Display only if form_per_file is true.
        if (!$element['#form_per_file']) {
          parent::process($element, $form_state, $form);
        }

        $id = $file->id();
        // @todo inject the entityquery service.
        $query = \Drupal::entityQuery('gated_file')
          ->condition('fid', $id);

        if ($gatedFileId = $query->execute()) {
          $gatedFile = GatedFile::load(array_pop($gatedFileId));
          $default_value = ContactForm::load($gatedFile->getFormId());
        }
        else if($element['#default_form']) {
          $default_value = ContactForm::load($element['#default_form']);
        } else {
          $default_value = NULL;
        }

        $element['form_wrapper'] = [
          '#type' => 'details',
          '#title' => 'Gated File',
        ];
        $element['form_wrapper']['form'] = [
          '#type' => 'entity_autocomplete',
          '#title' => t('Form'),
          '#target_type' => 'contact_form',
          '#default_value' => $default_value,
          '#description' => t('This form will need to be filled in order to download the file.'),
        ];
      }
    }

    return parent::process($element, $form_state, $form);
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['#form_per_file'] = $this->getSetting('form_per_file');
    $element['#default_form'] = $this->getSetting('form');
    return parent::formElement($items, $delta, $element, $form, $form_state);

  }

}
