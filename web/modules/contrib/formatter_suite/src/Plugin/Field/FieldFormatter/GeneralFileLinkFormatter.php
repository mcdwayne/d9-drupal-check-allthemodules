<?php

namespace Drupal\formatter_suite\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\file\Entity\File;
use Drupal\Core\Url;

use Drupal\formatter_suite\Branding;
use Drupal\formatter_suite\Utilities;

/**
 * Formats a file field as one or more links.
 *
 * The file field itself includes the entity ID of the File entity, an
 * optional description, and a flag indicating whether the file should be
 * displayed when viewing the entity containing this field.
 *
 * The File entity referenced by the file field includes a file URI to
 * the file, a file name, a MIME type, a size (in bytes), and a flag indicating
 * if the file is temporary or permanent. Like all entities, a File entity
 * also has a creation time and owner ID.
 *
 * This formatter supports:
 *   - Showing a title:
 *     - Using the referenced file's file name.
 *     - Using the file field's description text.
 *     - Using manually entered text.
 *   - Showing file information:
 *     - A MIME-type based file icon (depends on theme).
 *     - The file's size.
 *   - Adding manually entered classes.
 *   - Showing a link:
 *     - Adding selected standard "rel" and "target" options.
 *
 * The "rel" and "target" options are grouped and presented as menu choices
 * and checkboxes.
 *
 * @ingroup formatter_suite
 *
 * @FieldFormatter(
 *   id          = "formatter_suite_general_file_link",
 *   label       = @Translation("Formatter Suite - General file link"),
 *   weight      = 1000,
 *   field_types = {
 *     "file",
 *   }
 * )
 */
class GeneralFileLinkFormatter extends FormatterBase {

  /*---------------------------------------------------------------------
   *
   * Configuration.
   *
   *---------------------------------------------------------------------*/

  /**
   * Returns an array of title styles, for fields with titles.
   *
   * @return string[]
   *   Returns an associative array with internal names as keys and
   *   human-readable translated names as values.
   */
  protected static function getTitleStyles() {
    return [
      'text_from_filename' => t("Use the file's name"),
      'text_from_link'     => t("Use the file field's description"),
      'text_custom'        => t('Use custom text'),
    ];
  }

  /**
   * Returns an array of title styles, for fields without descriptions.
   *
   * @return string[]
   *   Returns an associative array with internal names as keys and
   *   human-readable translated names as values.
   */
  protected static function getTitleStylesFieldNoDescription() {
    return [
      'text_from_filename' => t("Use the file's name"),
      'text_custom'        => t('Use custom text'),
    ];
  }

  /**
   * Returns an array of choices for how to open a link.
   *
   * @return string[]
   *   Returns an associative array with internal names as keys and
   *   human-readable translated names as values.
   */
  protected static function getOpenLinkInValues() {
    return [
      '_self'    => t('Open the file in the same tab/window'),
      '_blank'   => t('Open the file in a new tab/window'),
      'download' => t('Download the file'),
    ];
  }

  /**
   * Returns an array of link topic annotation.
   *
   * @return string[]
   *   Returns an associative array with internal names as keys and
   *   human-readable translated names as values.
   */
  protected static function getLinkTopicValues() {
    return [
      'any'       => t('- Unspecified -'),
      'alternate' => t('Alternate form of this file'),
      'author'    => t('Author information'),
      'bookmark'  => t('Bookmarkable permalink'),
      'canonical' => t('Canonical (preferred) form of this file'),
      'help'      => t('Help information'),
      'license'   => t('License information'),
    ];
  }

  /**
   * Returns an array of list styles.
   *
   * @return string[]
   *   Returns an associative array with internal names as keys and
   *   human-readable translated names as values.
   */
  protected static function getListStyles() {
    return [
      'span' => t('Single line list'),
      'ol'   => t('Numbered list'),
      'ul'   => t('Bulleted list'),
      'div'  => t('Non-bulleted block list'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array_merge(
      [
        'titleStyle'      => 'text_from_filename',
        'titleCustomText' => '',
        'classes'         => '',
        'showIcon'        => TRUE,
        'showSize'        => TRUE,
        'showLink'        => TRUE,
        'openLinkIn'      => '_self',
        'linkTopic'       => 'any',
        'listStyle'       => 'span',
        'listSeparator'   => ', ',
      ],
      parent::defaultSettings());
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    // Sanitize current settings.
    $this->sanitizeSettings();
    $isMultiple = $this->fieldDefinition->getFieldStorageDefinition()->isMultiple();

    // Summarize.
    $summary = [];
    $text = '';
    switch ($this->getSetting('titleStyle')) {
      case 'text_from_filename':
        $text .= (string) $this->t('Show filename');
        break;

      case 'text_from_link':
        $text .= (string) $this->t('Show description');
        break;

      case 'text_custom':
        $text .= (string) $this->t('Show custom text');
        break;
    }

    if ($this->getSetting('showSize') === TRUE) {
      $text .= (string) $this->t(', size');
    }

    if ($this->getSetting('showIcon') === TRUE) {
      $text .= (string) $this->t(', MIME icon');
    }

    $summary[] = $text;

    if ($this->getSetting('showLink') === FALSE) {
      $summary[] = $this->t('No link');
    }
    else {
      switch ($this->getSetting('openLinkIn')) {
        case '_self':
          $summary[] = $this->t('Open in current tab/window');
          break;

        case '_blank':
          $summary[] = $this->t('Open in new tab/window');
          break;

        case 'download':
          $summary[] = $this->t('Download');
          break;
      }
    }

    // If the field can store multiple values, then summarize list style.
    if ($isMultiple === TRUE) {
      $listStyles    = $this->getListStyles();
      $listStyle     = $this->getSetting('listStyle');
      $listSeparator = $this->getSetting('listSeparator');

      $text = $listStyles[$listStyle];
      if ($listStyle === 'span' && empty($listSeparator) === FALSE) {
        $text .= $this->t(', with separator');
      }
      $summary[] = $text;
    }

    return $summary;
  }

  /*---------------------------------------------------------------------
   *
   * Settings form.
   *
   *---------------------------------------------------------------------*/

  /**
   * Returns a brief description of the formatter.
   *
   * @return string
   *   Returns a brief translated description of the formatter.
   */
  protected function getDescription() {
    $isMultiple = $this->fieldDefinition->getFieldStorageDefinition()->isMultiple();
    if ($isMultiple === TRUE) {
      return $this->t("Format field values as links that show a file name or description, size, and MIME-type icon. Multiple field values are shown as a list on one line, bulleted, numbered, or in blocks.");
    }

    return $this->t("Format a field value as a link that shows a file name or description, size, and MIME-type icon.");
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $formState) {
    $this->sanitizeSettings();

    //
    // Get field settings.
    //
    // - If the file field has no description, then we can adjust form
    //   items to omit using the field's text.
    //
    // - If the field can store multiple values, then we can include list
    //   style options.
    $fieldSettings = $this->getFieldSettings();
    $isMultiple = $this->fieldDefinition->getFieldStorageDefinition()->isMultiple();

    $fileDescriptionDisabled = FALSE;
    if ($fieldSettings['description_field'] === FALSE) {
      $fileDescriptionDisabled = TRUE;
    }

    // Below, some checkboxes and select choices show/hide other form
    // elements. We use Drupal's obscure 'states' feature, which adds
    // Javascript to elements to auto show/hide based upon a set of
    // simple conditions.
    //
    // Those conditions need to reference the form elements to check
    // (e.g. a checkbox), but the element ID and name are automatically
    // generated by the parent form. We cannot set them, or predict them,
    // so we cannot use them. We could use a class, but this form may be
    // shown multiple times on the same page, so a simple class would not be
    // unique. Instead, we create classes for this form only by adding a
    // random number marker to the end of the class name.
    $marker = rand();

    // Add branding.
    $elements = [];
    $elements = Branding::addFieldFormatterBranding($elements);
    $elements['#attached']['library'][] =
      'formatter_suite/formatter_suite.fieldformatter';

    // Add description.
    //
    // Use a large negative weight to insure it comes first.
    $elements['description'] = [
      '#type'          => 'html_tag',
      '#tag'           => 'div',
      '#value'         => $this->getDescription(),
      '#weight'        => -1000,
      '#attributes'    => [
        'class'        => [
          'formatter_suite-settings-description',
        ],
      ],
    ];

    $weight = 0;

    // Prompt for each setting.
    if ($fileDescriptionDisabled === TRUE) {
      $titleStyles = $this->getTitleStylesFieldNoDescription();
    }
    else {
      $titleStyles = $this->getTitleStyles();
    }

    $elements['titleStyle'] = [
      '#title'         => $this->t('Link title'),
      '#type'          => 'select',
      '#options'       => $titleStyles,
      '#default_value' => $this->getSetting('titleStyle'),
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-general-file-link-title-style',
        ],
      ],
      '#attributes'    => [
        'class'        => [
          'titleStyle-' . $marker,
        ],
      ],
    ];

    $elements['titleCustomText'] = [
      '#title'         => $this->t('Custom text'),
      '#type'          => 'textfield',
      '#size'          => 10,
      '#default_value' => $this->getSetting('titleCustomText'),
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-general-file-link-title-custom-text',
        ],
      ],
      '#states'        => [
        'visible'      => [
          '.titleStyle-' . $marker => [
            'value'    => 'text_custom',
          ],
        ],
      ],
    ];

    $elements['showSize'] = [
      '#title'         => $this->t('Add the file size to the title'),
      '#type'          => 'checkbox',
      '#default_value' => $this->getSetting('showSize'),
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-general-file-link-show-size',
        ],
      ],
    ];

    $elements['sectionBreak1'] = [
      '#markup' => '<div class="formatter_suite-section-break"></div>',
      '#weight' => $weight++,
    ];

    $elements['showIcon'] = [
      '#title'         => $this->t('Add a MIME-type file icon'),
      '#type'          => 'checkbox',
      '#default_value' => $this->getSetting('showIcon'),
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-general-file-link-show-icon',
        ],
      ],
    ];

    $elements['classes'] = [
      '#title'         => $this->t('Custom classes'),
      '#type'          => 'textfield',
      '#size'          => 10,
      '#default_value' => $this->getSetting('classes'),
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-general-file-link-classes',
        ],
      ],
    ];

    $elements['sectionBreak2'] = [
      '#markup' => '<div class="formatter_suite-section-break"></div>',
      '#weight' => $weight++,
    ];

    $elements['showLink'] = [
      '#title'         => $this->t('Link to the file'),
      '#type'          => 'checkbox',
      '#default_value' => $this->getSetting('showLink'),
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-general-file-link-show-link',
        ],
      ],
      '#attributes'    => [
        'class'        => [
          'showLink-' . $marker,
        ],
      ],
    ];

    $elements['openLinkIn'] = [
      '#title'         => $this->t('Use link to'),
      '#type'          => 'select',
      '#options'       => $this->getOpenLinkInValues(),
      '#default_value' => $this->getSetting('openLinkIn'),
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-general-file-link-open-link-in',
        ],
      ],
      '#states'        => [
        'visible'      => [
          '.showLink-' . $marker => [
            'checked'  => TRUE,
          ],
        ],
      ],
    ];

    $elements['linkTopic'] = [
      '#title'         => $this->t('Annotate link as'),
      '#type'          => 'select',
      '#options'       => $this->getLinkTopicValues(),
      '#default_value' => $this->getSetting('linkTopic'),
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-general-file-link-link-topic',
        ],
      ],
      '#states'        => [
        'visible'      => [
          '.showLink-' . $marker => [
            'checked'  => TRUE,
          ],
        ],
      ],
    ];

    if ($isMultiple === TRUE) {
      $elements['sectionBreak3'] = [
        '#markup' => '<div class="formatter_suite-section-break"></div>',
        '#weight' => $weight++,
      ];

      $elements['listStyle'] = [
        '#title'         => $this->t('List style'),
        '#type'          => 'select',
        '#options'       => $this->getListStyles(),
        '#default_value' => $this->getSetting('listStyle'),
        '#weight'        => $weight++,
        '#wrapper_attributes' => [
          'class'        => [
            'formatter_suite-general-file-link-list-style',
          ],
        ],
        '#attributes'    => [
          'class'        => [
            'listStyle-' . $marker,
          ],
        ],
      ];

      $elements['listSeparator'] = [
        '#title'         => $this->t('Separator'),
        '#type'          => 'textfield',
        '#size'          => 10,
        '#default_value' => $this->getSetting('listSeparator'),
        '#weight'        => $weight++,
        '#wrapper_attributes' => [
          'class'        => [
            'formatter_suite-general-file-link-list-separator',
          ],
        ],
        '#states'        => [
          'visible'      => [
            '.listStyle-' . $marker => [
              'value'    => 'span',
            ],
          ],
        ],
      ];
    }

    return $elements;
  }

  /**
   * Sanitize settings to insure that they are safe and valid.
   *
   * @internal
   * Drupal's class hierarchy for plugins and their settings does not
   * include a 'validate' function, like that for other classes with forms.
   * Validation must therefore occur on use, rather than on form submission.
   * @endinternal
   */
  protected function sanitizeSettings() {
    // Get field settings.
    $fieldSettings = $this->getFieldSettings();
    $isMultiple = $this->fieldDefinition->getFieldStorageDefinition()->isMultiple();

    $fileDescriptionDisabled = FALSE;
    if ($fieldSettings['description_field'] === FALSE) {
      $fileDescriptionDisabled = TRUE;
    }

    // Get current settings.
    $titleStyle = $this->getSetting('titleStyle');
    $showIcon   = $this->getSetting('showIcon');
    $showSize   = $this->getSetting('showSize');
    $showLink   = $this->getSetting('showLink');
    $openLinkIn = $this->getSetting('openLinkIn');
    $linkTopic  = $this->getSetting('linkTopic');

    // Get setting defaults.
    $defaults = $this->defaultSettings();

    // Sanitize & validate.
    //
    // While <select> inputs constrain choices to those we define in the
    // form, it is possible to hack a form response and send other values back.
    // So check all <select> choices and use the default when a value is
    // empty or unknown.
    if ($fileDescriptionDisabled === TRUE) {
      $titleStyles = $this->getTitleStylesFieldNoDescription();
    }
    else {
      $titleStyles = $this->getTitleStyles();
    }

    if (empty($titleStyle) === TRUE ||
        isset($titleStyles[$titleStyle]) === FALSE) {
      if ($fileDescriptionDisabled === TRUE) {
        $titleStyle = 'text_from_filename';
      }
      else {
        $titleStyle = $defaults['titleStyle'];
      }
      $this->setSetting('titleStyle', $titleStyle);
    }

    $openLinkInValues = $this->getOpenLinkInValues();
    if (empty($openLinkIn) === TRUE ||
        isset($openLinkInValues[$openLinkIn]) === FALSE) {
      $openLinkIn = $defaults['openLinkIn'];
      $this->setSetting('openLinkIn', $openLinkIn);
    }

    $linkTopicValues = $this->getLinkTopicValues();
    if (empty($linkTopic) === TRUE ||
        isset($linkTopicValues[$linkTopic]) === FALSE) {
      $linkTopic = $defaults['linkTopic'];
      $this->setSetting('linkTopic', $linkTopic);
    }

    // Insure boolean values are boolean.
    $showLink = boolval($showLink);
    $showIcon = boolval($showIcon);
    $showSize = boolval($showSize);

    $this->setSetting('showLink', $showLink);
    $this->setSetting('showIcon', $showIcon);
    $this->setSetting('showSize', $showSize);

    $listStyle = $this->getSetting('listStyle');
    $listStyles = $this->getListStyles();

    if ($isMultiple === TRUE) {
      if (empty($listStyle) === TRUE ||
          isset($listStyles[$listStyle]) === FALSE) {
        $listStyle = $defaults['listStyle'];
        $this->setSetting('listStyle', $listStyle);
      }
    }

    // Classes and custom title text are not sanitized or validated.
    // They will be added to the link, with appropriate Xss filtering.
  }

  /*---------------------------------------------------------------------
   *
   * View.
   *
   *---------------------------------------------------------------------*/

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $this->sanitizeSettings();

    $showSize = $this->getSetting('showSize');
    $showIcon = $this->getSetting('showIcon');
    $showLink = $this->getSetting('showLink');
    $entity   = $items->getEntity();

    // Prepare custom classes, if any.
    $addClasses = $this->getSetting('classes');
    if (empty($addClasses) === TRUE) {
      $addClasses = [];
    }
    else {
      // Security: Class names are entered by an administrator.
      // They may not include anything but CSS-compatible words, and
      // certainly no HTML.
      //
      // Here, the class text is stripped of HTML tags as a start.
      // A regex tosses unacceptable characters in a CSS class name.
      $addClasses = strip_tags($addClasses);
      $addClasses = mb_ereg_replace('[^_a-zA-Z0-9- ]', '', $addClasses);
      if ($addClasses === FALSE) {
        $addClasses = [];
      }

      $addClasses = explode(' ', $addClasses);
    }

    $elements = [];
    foreach ($items as $delta => $item) {
      //
      // Get the file, URL, size, and MIME type.
      // ---------------------------------------
      // Get the File entity indicated by the file field. If the entity
      // fails to load, something has become disconnected.
      $fileId = $item->target_id;
      $file = File::load($fileId);

      if ($file === NULL) {
        $url      = Url::fromRoute('<none>');
        $fileSize = 0;
        $mime     = 'application/octet-stream';
        $filename = $this->t(
          '(Missing file @id)',
          [
            '@id' => $fileId,
          ]);
      }
      else {
        $url      = Url::fromUri(file_create_url($file->getFileUri()));
        $fileSize = $file->getSize();
        $mime     = $file->getMimeType();
        $filename = $file->getFilename();
      }

      // Format the file size.
      // - Kilo/mega/giga with a "K" unit of 1000.
      // - Use abbreviatios, not full words.
      // - Use 3 digits.
      if ($showSize === TRUE) {
        $fileSizeMarkup = ' (' . Utilities::formatBytes(
            $fileSize,
            1000,
            FALSE,
            3) . ')';
      }
      else {
        $fileSizeMarkup = '';
      }

      // Add MIME-type based icon to classes.
      $classes = $addClasses;
      if ($showIcon === TRUE) {
        $classes[] = 'file';
        $classes[] = 'file--mime-' . strtr(
          $mime,
          [
            '/'   => '-',
            '.'   => '-',
          ]);
        $classes[] = 'file--' . file_icon_class($mime);
      }

      //
      // Get the link title.
      // -------------------
      // Use custom text, text from the link, or the file name. If custom
      // text is selected, but there isn't any, fall through to text from
      // the link. If there is none of that, fall through to the file name.
      switch ($this->getSetting('titleStyle')) {
        case 'text_custom':
          // Security: A custom title is entered by an administrator.
          // It may legitimately include HTML entities and minor HTML, but
          // it should not include dangerous HTML.
          //
          // Because it may include HTML, we cannot pass it directly as
          // the '#title' on a link, which will escape the HTML.
          //
          // We therefore use an Xss admin filter to remove any egreggious
          // HTML (such as scripts and styles), and then FormattableMarkup()
          // to mark the resulting text as safe.
          $title = Xss::filterAdmin($this->getSetting('titleCustomText'));
          if (empty($title) === FALSE) {
            $title .= $fileSizeMarkup;
            $title = new FormattableMarkup($title, []);
            break;
          }

          // Fall-through and use the field's description as the title text.
        default:
        case 'text_from_link':
          // Security: Description text from the link field is entered by
          // a user. It may legitimately include HTML entities and minor
          // HTML, but it should not include dangerous HTML.
          //
          // Because it may include HTML, we cannot pass it directly as the
          // '#title' on a link, which will escape the HTML.
          //
          // We therefore use an Xss strict filter to remove any egreggious
          // HTML (such as scripts and styles, broken HTML, etc), and then
          // FormattableMarkup() to mark the resulting text as safe.
          $title = Xss::filter($item->description);
          if (empty($title) === FALSE) {
            $title .= $fileSizeMarkup;
            $title = new FormattableMarkup($title, []);
            break;
          }

          // Fall-through and use the filename as the title text.
        case 'text_from_filename':
          // Security: Filenames from the File entity are entered by a
          // user. They may include any characters that the underlying
          // file syste supports, which includes HTML '<', '>', etc.
          //
          // Passing this text as '#title' on a link will automatically
          // escape special characters and insure they do not cause harm.
          // We therefore don't need to do any special filtering here.
          $title = (string) $filename;
          $title .= $fileSizeMarkup;
          break;
      }

      //
      // Build the link.
      // ---------------
      // If the link is disabled, show the title text within a <span>.
      // Otherwise, build a URL and create a link.
      if ($showLink === FALSE) {
        $elements[$delta] = [
          '#type'       => 'html_tag',
          '#tag'        => 'span',
          '#value'      => $title,
          '#attached'   => [
            'library'   => [
              'file/drupal.file',
            ],
          ],
          '#attributes' => [
            'class'     => $classes,
          ],
          '#cache'      => [
            'tags'      => $entity->getCacheTags(),
          ],
        ];
      }
      else {
        $rel = '';
        $target = '';
        $download = FALSE;

        switch ($this->getSetting('openLinkIn')) {
          case '_self':
            $target = '_self';
            break;

          case '_blank':
            $target = '_blank';
            break;

          case 'download':
            $download = TRUE;
            break;
        }

        $topic = $this->getSetting('linkTopic');
        if ($topic !== 'any') {
          $rel .= $topic;
        }

        if (empty($rel) === FALSE) {
          $urlOptions['attributes']['rel'] = $rel;
        }

        if (empty($target) === FALSE) {
          $urlOptions['attributes']['target'] = $target;
        }

        if ($download === TRUE) {
          $urlOptions['attributes']['download'] = '';
        }

        $url->setOptions($urlOptions);

        $elements[$delta] = [
          '#type'       => 'link',
          '#title'      => $title,
          '#options'    => $url->getOptions(),
          '#url'        => $url,
          '#attached'   => [
            'library'   => [
              'file/drupal.file',
            ],
          ],
          '#attributes' => [
            'class'     => $classes,
            'type'      => $mime . '; length=' . $fileSize,
            'title'     => $filename,
          ],
          '#cache'      => [
            'tags'      => $entity->getCacheTags(),
          ],
        ];

        if (empty($items[$delta]->_attributes) === FALSE) {
          // There are item attributes. Add them to the link's options.
          $elements[$delta]['#options'] += [
            'attributes' => $items[$delta]->_attributes,
          ];

          // And remove them from the item since they are now included
          // on the link.
          unset($items[$delta]->_attributes);
        }
      }
    }

    //
    // Add multi-value field processing.
    // ---------------------------------
    // If the field has multiple values, redirect to a theme and pass
    // the list style and separator.
    $isMultiple = $this->fieldDefinition->getFieldStorageDefinition()->isMultiple();
    if ($isMultiple === TRUE) {
      // Replace the 'field' theme with one that supports lists.
      $elements['#theme'] = 'formatter_suite_field_list';

      // Set the list style.
      $elements['#list_style'] = $this->getSetting('listStyle');

      // Set the list separator.
      //
      // Security: The list separator is entered by an administrator.
      // It may legitimately include HTML entities and minor HTML, but
      // it should not include dangerous HTML.
      //
      // Because it may include HTML, we cannot pass it as-is and let a TWIG
      // template use {{ }}, which will process the text and corrupt any
      // entered HTML or HTML entities.
      //
      // We therefore use an Xss admin filter to remove any egreggious HTML
      // (such as scripts and styles), and then FormattableMarkup() to mark the
      // resulting text as safe.
      $listSeparator = Xss::filterAdmin($this->getSetting('listSeparator'));
      $elements['#list_separator'] = new FormattableMarkup($listSeparator, []);
    }

    return $elements;
  }

}
