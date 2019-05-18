<?php

namespace Drupal\micon\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Plugin implementation of the 'file_micon' formatter.
 *
 * @FieldFormatter(
 *   id = "file_micon",
 *   label = @Translation("Micon"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class FileMiconFormatter extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = [
      'title' => 'View',
      'icon' => 'fa-file',
      'position' => 'before',
      'target' => '',
      'text_only' => '',
    ];
    foreach (self::mimeGroups() as $id => $data) {
      $key = 'icon_' . $id;
      $settings[$key] = $data['icon'];
    }
    return $settings + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = t('Link title as @title', ['@title' => $this->getSetting('title') ? $this->getSetting('title') : 'Default']);
    if ($position = $this->getSetting('position')) {
      $summary[] = t('Icon position: @value', ['@value' => ucfirst($position)]);
    }
    if ($this->getSetting('text_only')) {
      $summary[] = t('Text only');
    }
    else {
      if ($this->getSetting('target')) {
        $summary[] = t('Open link in new window');
      }
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link title'),
      '#default_value' => $this->getSetting('title'),
    ];
    $elements['text_only'] = [
      '#type' => 'checkbox',
      '#title' => t('Text only'),
      '#default_value' => $this->getSetting('text_only'),
    ];
    foreach (self::mimeGroups() as $id => $data) {
      $key = 'icon_' . $id;
      $elements['icon_' . $key] = [
        '#type' => 'micon',
        '#title' => $this->t('Icon for %type', ['%type' => $data['label']]),
        '#default_value' => $this->getSetting($key),
      ];
    }
    $elements['position'] = [
      '#type' => 'select',
      '#title' => $this->t('Icon position'),
      '#options' => ['before' => $this->t('Before'), 'after' => $this->t('After')],
      '#default_value' => $this->getSetting('position'),
      '#required' => TRUE,
    ];
    $elements['target'] = [
      '#type' => 'checkbox',
      '#title' => t('Open link in new window'),
      '#return_value' => '_blank',
      '#default_value' => $this->getSetting('target'),
      '#states' => [
        'invisible' => [
          ':input[name*="text_only"]' => ['checked' => TRUE],
        ],
      ],
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      $item = $file->_referringItem;
      $url = file_create_url($file->getFileUri());
      $options = [];
      if ($this->getSetting('target')) {
        $options['attributes']['target'] = '_blank';
      }

      $link_text = $this->getSetting('title') ? $this->getSetting('title') : $item->description;
      $position = $this->getSetting('position');
      $icon = $this->mimeMap($file->getMimeType());
      $link_text = micon($link_text)->setIcon($this->mimeMap($file->getMimeType()));
      if ($position == 'after') {
        $link_text->setIconAfter();
      }
      if ($this->getSetting('text_only')) {
        $elements[$delta]['#markup'] = $link_text;
      }
      else {
        $elements[$delta] = Link::fromTextAndUrl($link_text, Url::fromUri($url, $options))->toRenderable();
      }
      $elements[$delta]['#cache']['tags'] = $file->getCacheTags();
      // Pass field item attributes to the theme function.
      if (isset($item->_attributes)) {
        $elements[$delta] += ['#attributes' => []];
        $elements[$delta]['#attributes'] += $item->_attributes;
        // Unset field item attributes since they have been included in the
        // formatter output and should not be rendered in the field template.
        unset($item->_attributes);
      }
    }

    return $elements;
  }

  /**
   * Get mime groups.
   */
  public static function mimeGroups() {
    return [
      'default' => [
        'label' => t('Default'),
        'icon' => 'fa-file',
      ],
      'image' => [
        'label' => t('Image'),
        'icon' => 'fa-file-image',
      ],
      'document' => [
        'label' => t('Document'),
        'icon' => 'fa-file-word',
      ],
      'spreadsheet' => [
        'label' => t('Spreadsheet'),
        'icon' => 'fa-file-excel',
      ],
      'presentation' => [
        'label' => t('Presentation'),
        'icon' => 'fa-file-powerpoint',
      ],
      'archive' => [
        'label' => t('Archive'),
        'icon' => 'fa-file-archive',
      ],
      'script' => [
        'label' => t('Script'),
        'icon' => 'fa-file-code',
      ],
      'html' => [
        'label' => t('HTML'),
        'icon' => 'fa-file-code',
      ],
      'executable' => [
        'label' => t('Executable'),
        'icon' => 'fa-file-exclamation',
      ],
      'pdf' => [
        'label' => t('PDF'),
        'icon' => 'fa-file-pdf',
      ],
    ];
  }

  /**
   * Mime icon options.
   */
  protected function mimeMap($mime_type) {
    switch ($mime_type) {
      // Image types.
      case 'image/jpeg':
      case 'image/png':
      case 'image/gif':
      case 'image/bmp':
        return $this->getSetting('icon_image');

      // Word document types.
      case 'application/msword':
      case 'application/vnd.ms-word.document.macroEnabled.12':
      case 'application/vnd.oasis.opendocument.text':
      case 'application/vnd.oasis.opendocument.text-template':
      case 'application/vnd.oasis.opendocument.text-master':
      case 'application/vnd.oasis.opendocument.text-web':
      case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
      case 'application/vnd.stardivision.writer':
      case 'application/vnd.sun.xml.writer':
      case 'application/vnd.sun.xml.writer.template':
      case 'application/vnd.sun.xml.writer.global':
      case 'application/vnd.wordperfect':
      case 'application/x-abiword':
      case 'application/x-applix-word':
      case 'application/x-kword':
      case 'application/x-kword-crypt':
        return $this->getSetting('icon_document');

      // Spreadsheet document types.
      case 'application/vnd.ms-excel':
      case 'application/vnd.ms-excel.sheet.macroEnabled.12':
      case 'application/vnd.oasis.opendocument.spreadsheet':
      case 'application/vnd.oasis.opendocument.spreadsheet-template':
      case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
      case 'application/vnd.stardivision.calc':
      case 'application/vnd.sun.xml.calc':
      case 'application/vnd.sun.xml.calc.template':
      case 'application/vnd.lotus-1-2-3':
      case 'application/x-applix-spreadsheet':
      case 'application/x-gnumeric':
      case 'application/x-kspread':
      case 'application/x-kspread-crypt':
        return $this->getSetting('icon_spreadsheet');

      // Presentation document types.
      case 'application/vnd.ms-powerpoint':
      case 'application/vnd.ms-powerpoint.presentation.macroEnabled.12':
      case 'application/vnd.oasis.opendocument.presentation':
      case 'application/vnd.oasis.opendocument.presentation-template':
      case 'application/vnd.openxmlformats-officedocument.presentationml.presentation':
      case 'application/vnd.stardivision.impress':
      case 'application/vnd.sun.xml.impress':
      case 'application/vnd.sun.xml.impress.template':
      case 'application/x-kpresenter':
        return $this->getSetting('icon_presentation');

      // Compressed archive types.
      case 'application/zip':
      case 'application/x-zip':
      case 'application/stuffit':
      case 'application/x-stuffit':
      case 'application/x-7z-compressed':
      case 'application/x-ace':
      case 'application/x-arj':
      case 'application/x-bzip':
      case 'application/x-bzip-compressed-tar':
      case 'application/x-compress':
      case 'application/x-compressed-tar':
      case 'application/x-cpio-compressed':
      case 'application/x-deb':
      case 'application/x-gzip':
      case 'application/x-java-archive':
      case 'application/x-lha':
      case 'application/x-lhz':
      case 'application/x-lzop':
      case 'application/x-rar':
      case 'application/x-rpm':
      case 'application/x-tzo':
      case 'application/x-tar':
      case 'application/x-tarz':
      case 'application/x-tgz':
        return $this->getSetting('icon_archive');

      // Script file types.
      case 'application/ecmascript':
      case 'application/javascript':
      case 'application/mathematica':
      case 'application/vnd.mozilla.xul+xml':
      case 'application/x-asp':
      case 'application/x-awk':
      case 'application/x-cgi':
      case 'application/x-csh':
      case 'application/x-m4':
      case 'application/x-perl':
      case 'application/x-php':
      case 'application/x-ruby':
      case 'application/x-shellscript':
      case 'text/vnd.wap.wmlscript':
      case 'text/x-emacs-lisp':
      case 'text/x-haskell':
      case 'text/x-literate-haskell':
      case 'text/x-lua':
      case 'text/x-makefile':
      case 'text/x-matlab':
      case 'text/x-python':
      case 'text/x-sql':
      case 'text/x-tcl':
        return $this->getSetting('icon_script');

      // HTML aliases.
      case 'application/xhtml+xml':
        return $this->getSetting('icon_html');

      // Executable types.
      case 'application/x-macbinary':
      case 'application/x-ms-dos-executable':
      case 'application/x-pef-executable':
        return $this->getSetting('icon_executable');

      // Acrobat types.
      case 'application/pdf':
      case 'application/x-pdf':
      case 'applications/vnd.pdf':
      case 'text/pdf':
      case 'text/x-pdf':
        return $this->getSetting('icon_pdf');

      default:
        return $this->getSetting('icon_default');
    }
  }

}
