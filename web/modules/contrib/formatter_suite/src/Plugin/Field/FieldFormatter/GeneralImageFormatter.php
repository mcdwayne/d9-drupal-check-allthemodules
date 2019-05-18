<?php

namespace Drupal\formatter_suite\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Component\Utility\Html;

use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;

use Drupal\formatter_suite\Branding;
use Drupal\formatter_suite\Utilities;

/**
 * Formats an image.
 *
 * This class supports the display of an image field's styled image along
 * with a caption that may be shown above or below the image and include:
 * - The title from the image field.
 * - The file name.
 * - The file size.
 * - The image width and height.
 * - The image MIME type.
 *
 * The image and caption may be linked to the entity or image file, and
 * link attributes may be set to open the link in the current tab/window,
 * a new tab/window, or to download the item.
 *
 * @ingroup formatter_suite
 *
 * @FieldFormatter(
 *   id          = "formatter_suite_general_image",
 *   label       = @Translation("Formatter Suite - General image"),
 *   weight      = 1000,
 *   field_types = {
 *     "image",
 *   }
 * )
 */
class GeneralImageFormatter extends ImageFormatter {

  /*---------------------------------------------------------------------
   *
   * Configuration.
   *
   *---------------------------------------------------------------------*/

  /**
   * Returns an array of caption locations.
   *
   * @return string[]
   *   Returns an associative array with internal names as keys and
   *   human-readable translated names as values.
   */
  protected static function getCaptionLocations() {
    return [
      'none'  => t('Do not display an image caption'),
      'above' => t('Display an image caption above the image'),
      'below' => t('Display an image caption below the image'),
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
      '_self'    => t('Open in the same tab/window'),
      '_blank'   => t('Open in a new tab/window'),
      'download' => t('Download the entity or image file'),
    ];
  }

  /**
   * Returns an array of choices for where to link to.
   *
   * @return string[]
   *   Returns an associative array with internal names as keys and
   *   human-readable translated names as values.
   */
  protected static function getLinkTypes() {
    return [
      'content'  => t("Link to the image field's entity"),
      'file'     => t("Link to the image file"),
      // An empty link type = don't link.
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array_merge(
      [
        'captionLocation'          => 'none',
        'captionIncludeTitle'      => FALSE,
        'captionIncludeFilename'   => FALSE,
        'captionIncludeSize'       => FALSE,
        'captionIncludeDimensions' => FALSE,
        'captionIncludeMime'       => FALSE,
        'classes'                  => '',
        'openLinkIn'               => '_self',
      ],
      parent::defaultSettings());
    // Parent adds:
    // - image_style: the image style name.
    // - image_link: whether what to link the image to.
    //
    // This formatter uses the same two values, for compatability, but
    // presents a better UI.
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    // Sanitize current settings.
    $this->sanitizeSettings();

    // Get the image styles. Unset the empty 'No defined styles' option.
    // Note also that the current style choice could be invalid if the
    // indicated style has gone away.
    $imageStyles = image_style_options(FALSE);
    unset($imageStyles['']);

    $styleChoice = $this->getSetting('image_style');
    $currentStyle = NULL;
    if (isset($imageStyles[$styleChoice]) === TRUE) {
      $currentStyle = $imageStyles[$styleChoice];
    }

    // Determine if the field has a title.
    $fieldDefinition = $this->fieldDefinition;
    $fieldSettings = $fieldDefinition->getSettings();
    $hasTitle = FALSE;
    if (isset($fieldSettings['title_field']) === TRUE &&
        boolval($fieldSettings['title_field']) === TRUE) {
      $hasTitle = TRUE;
    }

    // Summarize.
    $summary = [];
    if ($currentStyle === NULL) {
      $summary[] = $this->t('Original image');
    }
    else {
      $summary[] = $this->t(
        'Image style "@style"',
        [
          '@style' => $currentStyle,
        ]);
    }

    $linkChoice = $this->getSetting('image_link');
    $linkTypes = self::getLinkTypes();
    if (isset($linkTypes[$linkChoice]) === TRUE) {
      // The image is linked.
      $summary[] = $linkTypes[$linkChoice];

      $openInChoice = $this->getSetting('openLinkIn');
      $openInValues = self::getOpenLinkInValues();

      if ($openInChoice !== 'download') {
        $summary[] = $openInValues[$openInChoice];
      }
      elseif ($linkChoice !== 'file') {
        // Ignore 'download' choice if the link type is not to a file.
        // Revert to '_blank'.
        $summary[] = $openInValues['_blank'];
      }
      else {
        $summary[] = $openInValues[$openInChoice];
      }
    }

    $captionChoice = $this->getSetting('captionLocation');
    $captionLocations = self::getCaptionLocations();
    if ($captionChoice !== 'none' &&
        isset($captionLocations[$captionChoice]) === TRUE) {
      $includes = [];
      if ($hasTitle === TRUE &&
          $this->getSetting('captionIncludeTitle') === TRUE) {
        $includes[] = (string) $this->t('title');
      }
      if ($this->getSetting('captionIncludeFilename') === TRUE) {
        $includes[] = (string) $this->t('file name');
      }
      if ($this->getSetting('captionIncludeSize') === TRUE) {
        $includes[] = (string) $this->t('size');
      }
      if ($this->getSetting('captionIncludeDimensions') === TRUE) {
        $includes[] = (string) $this->t('dimensions');
      }
      if ($this->getSetting('captionIncludeMime') === TRUE) {
        $includes[] = (string) $this->t('MIME type');
      }

      if (empty($includes) === FALSE) {
        switch ($captionChoice) {
          case 'above':
            $summary[] = $this->t(
              'Caption above: @list',
              [
                '@list' => implode(', ', $includes),
              ]);
            break;

          default:
          case 'below':
            $summary[] = $this->t(
              'Caption below: @list',
              [
                '@list' => implode(', ', $includes),
              ]);
            break;
        }
      }
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
    return $this->t("Show an image using a selected style. Optionally link the image to the content entity or the image file, and optionally include a caption above or below the image.");
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $formState) {
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

    // Do not start with the parent form, which is ugly. Add that form's
    // elements directly below.
    $elements = [];

    // Add branding.
    $elements = Branding::addFieldFormatterBranding($elements);
    $elements['#attached']['library'][] =
      'formatter_suite/formatter_suite.fieldformatter';

    // Add description.
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

    $weight = 100;

    // Determine if the field has a title.
    $fieldDefinition = $this->fieldDefinition;
    $fieldSettings = $fieldDefinition->getSettings();
    $hasTitle = FALSE;
    if (isset($fieldSettings['title_field']) === TRUE &&
        boolval($fieldSettings['title_field']) === TRUE) {
      $hasTitle = TRUE;
    }

    // Get image styles and a link to the styles configuration page.
    $imageStyles = image_style_options(FALSE);
    $imageStylesPage = Link::fromTextAndUrl(
      $this->t('Configure image styles'),
      Url::fromRoute('entity.image_style.collection'));
    $imageStyleAllowed = $this->currentUser->hasPermission(
      'administer image styles');

    // Prompt for each setting.
    $elements['image_style'] = [
      '#title'         => $this->t('Image style'),
      '#type'          => 'select',
      '#options'       => $imageStyles,
      '#empty_option'  => $this->t('None (original image)'),
      '#default_value' => $this->getSetting('image_style'),
      '#description'   => $imageStylesPage->toRenderable() + [
        '#access'      => $imageStyleAllowed,
      ],
      '#weight'        => $weight++,
    ];

    $elements['classes'] = [
      '#title'         => $this->t('Custom classes'),
      '#type'          => 'textfield',
      '#size'          => 10,
      '#default_value' => $this->getSetting('classes'),
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-general-image-classes',
        ],
      ],
    ];

    $elements['sectionBreak2'] = [
      '#markup'        => '<div class="formatter_suite-section-break"></div>',
      '#weight'        => $weight++,
    ];

    $elements['image_link'] = [
      '#title'         => $this->t('Link image'),
      '#type'          => 'select',
      '#options'       => self::getLinkTypes(),
      '#empty_option'  => $this->t('Do not link the image'),
      '#default_value' => $this->getSetting('image_link'),
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-general-image-link-type',
        ],
      ],
      '#attributes'    => [
        'class'        => [
          'image_link-' . $marker,
        ],
      ],
      '#weight'        => $weight++,
    ];

    $elements['openLinkIn'] = [
      '#title'         => $this->t('Use link to'),
      '#type'          => 'select',
      '#options'       => self::getOpenLinkInValues(),
      '#default_value' => $this->getSetting('openLinkIn'),
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-general-image-open-link-in',
        ],
      ],
      '#states'        => [
        'invisible'    => [
          '.image_link-' . $marker => [
            'value'    => '',
          ],
        ],
      ],
    ];

    $elements['sectionBreak3'] = [
      '#markup'        => '<div class="formatter_suite-section-break"></div>',
      '#weight'        => $weight++,
    ];

    $elements['captionLocation'] = [
      '#title'         => $this->t('Caption location'),
      '#type'          => 'select',
      '#options'       => self::getCaptionLocations(),
      '#default_value' => $this->getSetting('captionLocation'),
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-general-image-caption-location',
        ],
      ],
      '#attributes'    => [
        'class'        => [
          'captionLocation-' . $marker,
        ],
      ],
    ];

    if ($hasTitle === TRUE) {
      $elements['captionIncludeTitle'] = [
        '#title'         => $this->t('Include title in caption'),
        '#type'          => 'checkbox',
        '#default_value' => $this->getSetting('captionIncludeTitle'),
        '#weight'        => $weight++,
        '#wrapper_attributes' => [
          'class'        => [
            'formatter_suite-general-image-caption-include-title',
          ],
        ],
        '#states'        => [
          'invisible'    => [
            '.captionLocation-' . $marker => [
              'value'    => 'none',
            ],
          ],
        ],
      ];
    }

    $elements['captionIncludeFilename'] = [
      '#title'         => $this->t('Include file name in caption'),
      '#type'          => 'checkbox',
      '#default_value' => $this->getSetting('captionIncludeFilename'),
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-general-image-caption-include-filename',
        ],
      ],
      '#states'        => [
        'invisible'    => [
          '.captionLocation-' . $marker => [
            'value'    => 'none',
          ],
        ],
      ],
    ];

    $elements['captionIncludeSize'] = [
      '#title'         => $this->t('Include file size in caption'),
      '#type'          => 'checkbox',
      '#default_value' => $this->getSetting('captionIncludeSize'),
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-general-image-caption-include-size',
        ],
      ],
      '#states'        => [
        'invisible'    => [
          '.captionLocation-' . $marker => [
            'value'    => 'none',
          ],
        ],
      ],
    ];

    $elements['captionIncludeDimensions'] = [
      '#title'         => $this->t('Include image dimensions in caption'),
      '#type'          => 'checkbox',
      '#default_value' => $this->getSetting('captionIncludeDimensions'),
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-general-image-caption-include-dimensions',
        ],
      ],
      '#states'        => [
        'invisible'    => [
          '.captionLocation-' . $marker => [
            'value'    => 'none',
          ],
        ],
      ],
    ];

    $elements['captionIncludeMime'] = [
      '#title'         => $this->t('Include MIME type in caption'),
      '#type'          => 'checkbox',
      '#default_value' => $this->getSetting('captionIncludeMime'),
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-general-image-caption-include-mime',
        ],
      ],
      '#states'        => [
        'invisible'    => [
          '.captionLocation-' . $marker => [
            'value'    => 'none',
          ],
        ],
      ],
    ];

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
    // Get current settings.
    //
    // Nothing to sanitize for the parent class's 'image_style'.
    // The style choice is checked on every use because styles come and go.
    $imageLink = $this->getSetting('image_link');
    $openLinkIn = $this->getSetting('openLinkIn');
    $captionLocation = $this->getSetting('captionLocation');
    $captionIncludeTitle = $this->getSetting('captionIncludeTitle');
    $captionIncludeFilename = $this->getSetting('captionIncludeFilename');
    $captionIncludeSize = $this->getSetting('captionIncludeSize');
    $captionIncludeDimensions = $this->getSetting('captionIncludeDimensions');
    $captionIncludeMime = $this->getSetting('captionIncludeMime');

    // Get setting defaults.
    $defaults = $this->defaultSettings();

    // Sanitize & validate.
    //
    // While <select> inputs constrain choices to those we define in the
    // form, it is possible to hack a form response and send other values back.
    // So check all <select> choices and use the default when a value is
    // empty or unknown.
    if (isset(self::getLinkTypes()[$imageLink]) === FALSE) {
      $this->setSetting('image_link', $defaults['image_link']);
    }

    if (isset($this->getOpenLinkInValues()[$openLinkIn]) === FALSE) {
      $this->setSetting('openLinkIn', $defaults['openLinkIn']);
    }

    if (isset($this->getCaptionLocations()[$captionLocation]) === FALSE) {
      $this->setSetting('captionLocation', $defaults['captionLocation']);
    }

    // Insure boolean values are boolean.
    $this->setSetting('captionIncludeTitle',
      boolval($captionIncludeTitle));
    $this->setSetting('captionIncludeFilename',
      boolval($captionIncludeFilename));
    $this->setSetting('captionIncludeSize',
      boolval($captionIncludeSize));
    $this->setSetting('captionIncludeDimensions',
      boolval($captionIncludeDimensions));
    $this->setSetting('captionIncludeMime',
      boolval($captionIncludeMime));

    // Classes are not sanitized or validated. They will be added to the link.
  }

  /*---------------------------------------------------------------------
   *
   * View.
   *
   *---------------------------------------------------------------------*/

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langCode) {
    $this->sanitizeSettings();

    $files = $this->getEntitiesToView($items, $langCode);
    if (empty($files) === TRUE) {
      return [];
    }

    //
    // Get settings.
    // -------------
    // Get the formatter configuration.
    $classes                  = $this->getSetting('classes');
    $openLinkIn               = $this->getSetting('openLinkIn');
    $captionLocation          = $this->getSetting('captionLocation');
    $captionIncludeTitle      = $this->getSetting('captionIncludeTitle');
    $captionIncludeFilename   = $this->getSetting('captionIncludeFilename');
    $captionIncludeSize       = $this->getSetting('captionIncludeSize');
    $captionIncludeDimensions = $this->getSetting('captionIncludeDimensions');
    $captionIncludeMime       = $this->getSetting('captionIncludeMime');

    // If the settings do not enable any of the possible caption components,
    // then there is no caption.
    if ($captionIncludeTitle === FALSE &&
        $captionIncludeFilename === FALSE &&
        $captionIncludeSize === FALSE &&
        $captionIncludeDimensions === FALSE &&
        $captionIncludeMime === FALSE) {
      $captionLocation = 'none';
    }

    //
    // Format each image.
    // ------------------
    // The parent image formatter does very little processing within the
    // formatter. Instead, it sets a theme template and later processing
    // of the template sets up the image's URL, including possibly use of
    // an image style.
    //
    // Let the parent class do its processing. The returned array has one
    // entry per item and a configuration that invokes the image module's
    // 'image_formatter' theme.
    $parentElements = parent::viewElements($items, $langCode);

    $classes = explode(' ', $classes);
    $classes[] = 'formatter_suite-general-image';

    //
    // Create render elements.
    // -----------------------
    // The parent elements created above only contain the field's images.
    // To this we need to add:
    // - A container wrapper that has the given classes.
    // - A caption above or below each image, if enabled.
    //   - A title, if enabled.
    //   - A file name, if enabled.
    //   - A file size, if enabled.
    //   - Image dimensions, if enabled.
    //   - A file MIME type, if enabled.
    //
    // If linking is enabled, the image and caption need to be links using
    // the indicated link attributes.
    //
    // The parent element needs to be nested within a wrapper that adds
    // the above items. If the parent element has a URL, then that URL's
    // options need to be adjusted to include the link attributes.
    $elements = [];

    foreach ($items as $delta => $item) {
      if (isset($parentElements[$delta]) !== TRUE) {
        // The parent formatter skipped this one? Skip it too.
        continue;
      }

      // Get the URL, if any, from the parent. If there is a URL, add
      // link attributes and update the parent elements.
      $url = $parentElements[$delta]['#url'];
      $mime = $files[$delta]->getMimeType();

      if ($url !== NULL) {
        $urlOptions = $url->getOptions();
        if (isset($urlOptions['attributes']) === FALSE) {
          $urlOptions['attributes'] = [];
        }

        $urlOptions['attributes']['type'] = $mime;

        switch ($openLinkIn) {
          case '_self':
            $urlOptions['attributes']['target'] = '_self';
            break;

          case '_blank':
            $urlOptions['attributes']['target'] = '_blank';
            break;

          case 'download':
            $urlOptions['attributes']['download'] = '';
            break;
        }

        $url->setOptions($urlOptions);
        $parentElements[$delta]['#url'] = $url;
      }

      // Assemble the caption's render elements.
      $imageWeight = 0;
      $captionWeight = 0;
      $itemClasses = array_merge([], $classes);
      $caption = [];
      if ($captionLocation !== 'none') {
        switch ($captionLocation) {
          case 'above':
            $captionWeight = 0;
            $imageWeight = 1000;
            $itemClasses[] = 'formatter_suite-general-image-above';
            break;

          default:
          case 'below':
            $imageWeight = 0;
            $captionWeight = 1000;
            $itemClasses[] = 'formatter_suite-general-image-below';
            break;
        }

        $title = '';
        if ($captionIncludeTitle === TRUE) {
          // Prefer the title from the field, if any. Otherwise use the
          // file's label.
          if (isset($item->title) === TRUE) {
            $title = $item->title;
          }
          else {
            // Fall back to the file entity's label/name. But this too
            // may be empty.
            $title = $files[$delta]->label();
          }

          if (empty($title) === FALSE) {
            if ($url === NULL) {
              $caption['title'] = [
                '#type'    => 'html_tag',
                '#tag'     => 'div',
                '#value'   => Html::escape($title),
                '#weight'  => $captionWeight++,
                '#attributes' => [
                  'class'     => [
                    'formatter_suite-image-caption-title',
                  ],
                ],
              ];
            }
            else {
              // Clone the URL because the render element modifies it
              // to add attributes.
              $localUrl = clone $url;
              $caption['title'] = [
                '#type'    => 'link',
                '#title'   => $title,
                '#options' => $localUrl->getOptions(),
                '#url'     => $localUrl,
                '#weight'  => $captionWeight++,
                '#attributes' => [
                  'class'     => [
                    'formatter_suite-image-caption-title',
                  ],
                ],
              ];
            }
          }
        }

        if ($captionIncludeFilename === TRUE) {
          // The name of the original image file, not the styled image.
          $filename = $files[$delta]->getFilename();

          // Only show the file name if it differs from the title, if the
          // title was shown.
          if (empty($title) === TRUE || $title !== $filename) {
            if ($url === NULL) {
              $caption['filename'] = [
                '#type'    => 'html_tag',
                '#tag'     => 'div',
                '#value'   => Html::escape($filename),
                '#weight'  => $captionWeight++,
                '#attributes' => [
                  'class'     => [
                    'formatter_suite-image-caption-filename',
                  ],
                ],
              ];
            }
            else {
              // Clone the URL because the render element modifies it
              // to add attributes.
              $localUrl = clone $url;
              $caption['filename'] = [
                '#type'    => 'link',
                '#title'   => $filename,
                '#options' => $localUrl->getOptions(),
                '#url'     => $localUrl,
                '#weight'  => $captionWeight++,
                '#attributes' => [
                  'class'     => [
                    'formatter_suite-image-caption-filename',
                  ],
                ],
              ];
            }
          }
        }

        if ($captionIncludeSize === TRUE) {
          // The size of the original image, not the styled image.
          $bytes = Utilities::formatBytes(
            $files[$delta]->getSize(),
            1000,
            FALSE,
            2);

          if ($url === NULL) {
            $caption['size'] = [
              '#type'    => 'html_tag',
              '#tag'     => 'div',
              '#value'   => $bytes,
              '#weight'  => $captionWeight++,
              '#attributes' => [
                'class'     => [
                  'formatter_suite-image-caption-size',
                ],
              ],
            ];
          }
          else {
            // Clone the URL because the render element modifies it
            // to add attributes.
            $localUrl = clone $url;
            $caption['size'] = [
              '#type'    => 'link',
              '#title'   => $bytes,
              '#options' => $localUrl->getOptions(),
              '#url'     => $localUrl,
              '#weight'  => $captionWeight++,
              '#attributes' => [
                'class'     => [
                  'formatter_suite-image-caption-size',
                ],
              ],
            ];
          }
        }

        if ($captionIncludeDimensions === TRUE) {
          // The dimensions of the original image, not the styled image.
          $x = " \u{2A09} ";
          $text = $item->width . $x . $item->height;

          if ($url === NULL) {
            $caption['dimensions'] = [
              '#type'    => 'html_tag',
              '#tag'     => 'div',
              '#value'   => $text,
              '#weight'  => $captionWeight++,
              '#attributes' => [
                'class'     => [
                  'formatter_suite-image-caption-dimensions',
                ],
              ],
            ];
          }
          else {
            // Clone the URL because the render element modifies it
            // to add attributes.
            $localUrl = clone $url;
            $caption['dimensions'] = [
              '#type'    => 'link',
              '#title'   => $text,
              '#options' => $localUrl->getOptions(),
              '#url'     => $localUrl,
              '#weight'  => $captionWeight++,
              '#attributes' => [
                'class'     => [
                  'formatter_suite-image-caption-dimensions',
                ],
              ],
            ];
          }
        }

        if ($captionIncludeMime === TRUE) {
          // The MIME type of the original image, not the styled image.
          if ($url === NULL) {
            $caption['mime'] = [
              '#type'    => 'html_tag',
              '#tag'     => 'div',
              '#value'   => Html::escape($mime),
              '#weight'  => $captionWeight++,
              '#attributes' => [
                'class'     => [
                  'formatter_suite-image-caption-mime',
                ],
              ],
            ];
          }
          else {
            // Clone the URL because the render element modifies it
            // to add attributes.
            $localUrl = clone $url;
            $caption['mime'] = [
              '#type'    => 'link',
              '#title'   => $mime,
              '#options' => $localUrl->getOptions(),
              '#url'     => $localUrl,
              '#weight'  => $captionWeight++,
              '#attributes' => [
                'class'     => [
                  'formatter_suite-image-caption-mime',
                ],
              ],
            ];
          }
        }
      }

      // Modify the image module's render elements.
      //
      // - Add a weight to order the image and caption properly.
      //
      // - Swap out the theme to go to our own theme, which is identical to
      //   the Image module's, except that it includes attributes on the
      //   link surrounding the image.
      //
      // - Add the URL options, such as the target and download attributes.
      $parentElements[$delta]['#weight'] = $imageWeight;

      $parentElements[$delta]['#theme'] =
        'formatter_suite_general_image_formatter';

      if ($url === NULL) {
        // There is no URL. Add the class to the image itself.
        $parentElements[$delta]['#item_attributes']['class'] = [
          'formatter_suite-image',
        ];
      }
      else {
        // There is a URL. Add the class to the link around the image.
        $urlOptions = $url->getOptions();

        if (isset($urlOptions['attributes']) === TRUE) {
          // Add the URL's attributes, such as target and download.
          $urlAttributes = $urlOptions['attributes'];
          $parentElements[$delta]['#attributes'] = $urlAttributes + [
            'class' => [
              'formatter_suite-image',
            ],
          ];
        }
        else {
          $parentElements[$delta]['#attributes']['class'] = [
            'formatter_suite-image',
          ];
        }
      }

      // Create a container for the image and caption.
      $elements[$delta] = [
        '#type'       => 'container',
        '#attributes' => [
          'class'     => $itemClasses,
        ],
        '#attached'   => [
          'library'   => [
            'formatter_suite/formatter_suite.usage',
          ],
        ],
        'image'       => $parentElements[$delta],
      ];

      if (empty($caption) === FALSE) {
        $elements[$delta] += $caption;
      }
    }

    return $elements;
  }

}
