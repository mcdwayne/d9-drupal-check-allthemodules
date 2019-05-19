<?php

namespace Drupal\text2image\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;

/**
 * Defines a form that configures text2image module settings.
 */
class Text2ImageFontsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'text2image.fonts';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'text2image.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('text2image.settings');
    $fonts = \Drupal::service('text2image.fonts')->getInstalledFonts($config->get('font_path'), TRUE);
    drupal_set_message('Scanned for fonts', 'info');
    if (empty($fonts)) {
      drupal_set_message('0 fonts found.', 'warning');
    }
    else {
      drupal_set_message(count($fonts) . ' fonts found and cached', 'info');
      drupal_set_message('Preview font size: 16.', 'info');
    }
    $link = Link::createFromRoute('Restore default settings', 'text2image.admin_reset')->toString()->getGeneratedLink();
    $form['info'] = [
      '#markup' => '<p><strong>' . $link . '</strong></p>',
    ];
    $header = [
      'preview' => t('Preview'),
      'name' => t('Name'),
      'file' => t('File'),
    ];
    $options = [];
    foreach ($fonts as $file => $name) {
      $options[$file] = [
        'name' => $name,
        'file' => $file,
        'preview' => [
          'data' => [
            '#type' => 'markup',
            '#markup' => $this->generatePreview($file, $name),
          ],
        ],
      ];
    }
    $selected = text2image_get_selected_fonts();
    $form['fonts'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#default_value' => $selected,
      '#empty' => t('No fonts available.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Generate a preview image.
   *
   * @param string $font_file
   *   Path to font file.
   * @param string $font_name
   *   Name of font.
   *
   * @return string
   *   Return markup string.
   */
  public function generatePreview($font_file, $font_name) {
    $settings = [
      'width' => 400,
      'height' => 50,
      'fg_color' => '#eeeeee',
      'bg_color' => '#111111',
      'font_file' => $font_file,
      'font_size' => 16,
    ];
    $this->generator = \Drupal::service('text2image.generator')->setImagePath('public://text2image/previews/')->init($settings);
    $image = $this->generator->getImage($font_name);
    $url = file_create_url($image->uri);
    return '<img src="' . $url . '">';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    foreach ($values['fonts'] as $file => $checked) {
      if ($checked) {
        $filelist[$file] = basename($file, '.ttf');
      }
    }
    $config = $this->config('text2image.settings');
    $config->set('fonts_selected', serialize($filelist));
    $config->save();
  }

}
