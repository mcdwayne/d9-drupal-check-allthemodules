<?php

namespace Drupal\formatter_suite\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\link\LinkItemInterface;

use Drupal\formatter_suite\Branding;

/**
 * Formats a link field as one or more links.
 *
 * The link field itself includes title text, a URL, and possible URL options.
 * The field may be configured to allow internal, external, or both URL types,
 * and a single value or multiple values.
 *
 * This formatter supports:
 *   - Showing a title:
 *     - Using the link field's title text.
 *     - Using the URL in plain text.
 *     - Using manually entered text.
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
 *   id          = "formatter_suite_general_link",
 *   label       = @Translation("Formatter Suite - General link"),
 *   weight      = 1000,
 *   field_types = {
 *     "link",
 *   }
 * )
 */
class GeneralLinkFormatter extends FormatterBase {

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
      'text_from_link' => t("Use the link field's title"),
      'text_from_url'  => t("Use the link field's URL"),
      'text_custom'    => t('Use custom text'),
    ];
  }

  /**
   * Returns an array of title styles, for fields without titles.
   *
   * @return string[]
   *   Returns an associative array with internal names as keys and
   *   human-readable translated names as values.
   */
  protected static function getTitleStylesFieldNoTitle() {
    return [
      'text_from_url'  => t("Use the link field's URL"),
      'text_custom'    => t('Use custom text'),
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
      '_self'    => t('Open the linked item in the same tab/window'),
      '_blank'   => t('Open the linked item in a new tab/window'),
      'download' => t('Download the linked item'),
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
      'alternate' => t('Alternate form of this item'),
      'author'    => t('Author information'),
      'bookmark'  => t('Bookmarkable permalink'),
      'canonical' => t('Canonical (preferred) form of this item'),
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
        'titleStyle'      => 'text_from_link',
        'titleCustomText' => '',
        'classes'         => '',
        'showLink'        => TRUE,
        'openLinkIn'      => '_self',
        'noreferrer'      => FALSE,
        'noopener'        => FALSE,
        'nofollow'        => FALSE,
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
    switch ($this->getSetting('titleStyle')) {
      case 'text_from_link':
        $summary[] = $this->t('Show field title');
        break;

      case 'text_from_url':
        $summary[] = $this->t('Show field URL');
        break;

      case 'text_custom':
        $summary[] = $this->t('Show custom text');
        break;
    }

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

      $text = '';
      if ($this->getSetting('noreferrer') === TRUE) {
        $text .= 'noreferrer ';
      }

      if ($this->getSetting('noopener') === TRUE) {
        $text .= 'noopener ';
      }

      if ($this->getSetting('nofollow') === TRUE) {
        $text .= 'nofollow ';
      }

      if (empty($text) === FALSE) {
        $summary[] = $text;
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
      return $this->t("Format field values as links that show the title or URL. Multiple field values are shown as a list on one line, bulleted, numbered, or in blocks.");
    }

    return $this->t("Format a field value as a link that shows a title or URL.");
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $formState) {
    $this->sanitizeSettings();

    //
    // Get field settings.
    //
    // - If the link's URL is required to be internal, then below we can omit
    //   form items that are only useful for external links.
    //
    // - If the link has no title, then we can adjust form items to omit using
    //   the link field's title.
    $fieldSettings = $this->getFieldSettings();
    $isMultiple = $this->fieldDefinition->getFieldStorageDefinition()->isMultiple();

    $linkIsInternalOnly = FALSE;
    if ($fieldSettings['link_type'] === LinkItemInterface::LINK_INTERNAL) {
      $linkIsInternalOnly = TRUE;
    }

    $linkTitleDisabled = FALSE;
    if ($fieldSettings['title'] === DRUPAL_DISABLED) {
      $linkTitleDisabled = TRUE;
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
    if ($linkTitleDisabled === TRUE) {
      $titleStyles = $this->getTitleStylesFieldNoTitle();
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
          'formatter_suite-general-link-title-style',
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
          'formatter_suite-general-link-title-custom-text',
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

    $elements['sectionBreak1'] = [
      '#markup' => '<div class="formatter_suite-section-break"></div>',
      '#weight' => $weight++,
    ];

    $elements['classes'] = [
      '#title'         => $this->t('Custom classes'),
      '#type'          => 'textfield',
      '#size'          => 10,
      '#default_value' => $this->getSetting('classes'),
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-general-link-classes',
        ],
      ],
    ];

    $elements['sectionBreak2'] = [
      '#markup' => '<div class="formatter_suite-section-break"></div>',
      '#weight' => $weight++,
    ];

    $elements['showLink'] = [
      '#title'         => $this->t('Link to the item'),
      '#type'          => 'checkbox',
      '#default_value' => $this->getSetting('showLink'),
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-general-link-show-link',
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
          'formatter_suite-general-link-open-link-in',
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
          'formatter_suite-general-link-link-topic',
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

    if ($linkIsInternalOnly === FALSE) {
      $elements['noreferrer'] = [
        '#title'         => $this->t('Do not pass the current site as the referrer ("noreferrer")'),
        '#type'          => 'checkbox',
        '#default_value' => $this->getSetting('noreferrer'),
        '#weight'        => $weight++,
        '#wrapper_attributes' => [
          'class'        => [
            'formatter_suite-general-link-noreferrer',
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

      $elements['noopener'] = [
        '#title'         => $this->t('Do not share the current page context ("noopener")'),
        '#type'          => 'checkbox',
        '#default_value' => $this->getSetting('noopener'),
        '#weight'        => $weight++,
        '#wrapper_attributes' => [
          'class'        => [
            'formatter_suite-general-link-noopener',
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
    }

    if ($linkIsInternalOnly === FALSE) {
      $elements['nofollow'] = [
        '#title'         => $this->t('Do not treat the link as an endorsement ("nofollow")'),
        '#type'          => 'checkbox',
        '#default_value' => $this->getSetting('nofollow'),
        '#weight'        => $weight++,
        '#wrapper_attributes' => [
          'class'        => [
            'formatter_suite-general-link-nofollow',
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
    }

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
            'formatter_suite-general-link-list-style',
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
            'formatter_suite-general-link-list-separator',
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

    $linkIsInternalOnly = FALSE;
    if ($fieldSettings['link_type'] === LinkItemInterface::LINK_INTERNAL) {
      $linkIsInternalOnly = TRUE;
    }

    $linkTitleDisabled = FALSE;
    if ($fieldSettings['title'] === DRUPAL_DISABLED) {
      $linkTitleDisabled = TRUE;
    }

    // Get current settings.
    $titleStyle = $this->getSetting('titleStyle');
    $showLink   = $this->getSetting('showLink');
    $openLinkIn = $this->getSetting('openLinkIn');
    $noreferrer = $this->getSetting('noreferrer');
    $noopener   = $this->getSetting('noopener');
    $nofollow   = $this->getSetting('nofollow');
    $linkTopic  = $this->getSetting('linkTopic');

    if ($linkIsInternalOnly === TRUE) {
      // For internal-only link fields, do not support setting noreferrer,
      // noopener, or nofollow.
      $noreferrer = FALSE;
      $noopener   = FALSE;
      $nofollow   = FALSE;
    }

    // Get setting defaults.
    $defaults = $this->defaultSettings();

    // Sanitize & validate.
    //
    // While <select> inputs constrain choices to those we define in the
    // form, it is possible to hack a form response send other values back.
    // So check all <select> choices and use the default when a value is
    // empty or unknown.
    if ($linkTitleDisabled === TRUE) {
      $titleStyles = $this->getTitleStylesFieldNoTitle();
    }
    else {
      $titleStyles = $this->getTitleStyles();
    }

    if (empty($titleStyle) === TRUE ||
        isset($titleStyles[$titleStyle]) === FALSE) {
      if ($linkTitleDisabled === TRUE) {
        $titleStyle = 'text_from_url';
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
    $showLink   = boolval($showLink);
    $noreferrer = boolval($noreferrer);
    $noopener   = boolval($noopener);
    $nofollow   = boolval($nofollow);

    $this->setSetting('showLink', $showLink);
    $this->setSetting('noreferrer', $noreferrer);
    $this->setSetting('noopener', $noopener);
    $this->setSetting('nofollow', $nofollow);

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

    $showLink = $this->getSetting('showLink');
    $entity   = $items->getEntity();

    // Prepare custom classes, if any.
    $classes = $this->getSetting('classes');
    if (empty($classes) === TRUE) {
      $classes = [];
    }
    else {
      // Security: Class names are entered by an administrator.
      // They may not include anything but CSS-compatible words, and
      // certainly no HTML.
      //
      // Here, the class text is stripped of HTML tags as a start.
      // A regex tosses unacceptable characters in a CSS class name.
      $classes = strip_tags($classes);
      $classes = mb_ereg_replace('[^_a-zA-Z0-9- ]', '', $classes);
      if ($classes === FALSE) {
        $classes = [];
      }

      $classes = explode(' ', $classes);
    }

    $elements = [];
    foreach ($items as $delta => $item) {
      //
      // Get the URL.
      // ------------
      // Get the URL from the field and get an initial set of options,
      // including those that may have been set in the field.
      // Build the URL from the field.
      $url = $item->getUrl();
      if (empty($url) === TRUE) {
        $url = Url::fromRoute('<none>');
      }

      $urlOptions = $item->options + $url->getOptions();
      $url->setOptions($urlOptions);

      //
      // Get the link title.
      // -------------------
      // Use custom text, text from the link, or the URL. If custom text
      // is selected, but there isn't any, fall through to text from the
      // link. If there is none of that, fall through to the URL.
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
            $title = new FormattableMarkup($title, []);
            break;
          }

          // Fall-through and use the field's title as the title text.
        default:
        case 'text_from_link':
          // Security: Link text from the link field is entered by
          // a user. It may legitimately include HTML entities and minor
          // HTML, but it should not include dangerous HTML.
          //
          // Because it may include HTML, we cannot pass it directly as the
          // '#title' on a link, which will escape the HTML.
          //
          // We therefore use an Xss strict filter to remove any egreggious
          // HTML (such as scripts and styles, broken HTML, etc), and then
          // FormattableMarkup() to mark the resulting text as safe.
          $title = $item->title;
          if (empty($title) === FALSE) {
            $title = new FormattableMarkup($title, []);
            break;
          }

          // Fall-through and use the URL as the title text.
        case 'text_from_url':
          // Use the URL as entered in the field, BEFORE this formatter adds
          // additional attributes.
          //
          // Security: URL text from the link field is entered by a user.
          // It should be strictly a valid URL.
          //
          // Below we pass it as the '#title' on a link, which will escape
          // any HTML the URL might contain.
          $title = $url->toString();
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

        if ($this->getSetting('noreferrer') === TRUE) {
          $rel .= 'noreferrer ';
        }

        if ($this->getSetting('noopener') === TRUE) {
          $rel .= 'noopener ';
        }

        if ($this->getSetting('nofollow') === TRUE) {
          $rel .= 'nofollow ';
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
          '#attributes' => [
            'class'     => $classes,
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
