<?php

namespace Drupal\formatter_suite\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Xss;

use Drupal\formatter_suite\Branding;

/**
 * Formats an entity reference as one or more links.
 *
 * An entity reference indicates the entity ID of a target entity. Every
 * entity has a label.
 *
 * This formatter supports:
 *   - Showing a title:
 *     - Using the reference entity's title.
 *     - Using the reference entity's ID.
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
 *   id          = "formatter_suite_general_entity_reference",
 *   label       = @Translation("Formatter Suite - General entity reference"),
 *   weight      = 1000,
 *   field_types = {
 *     "entity_reference",
 *   }
 * )
 */
class GeneralEntityReferenceFormatter extends EntityReferenceFormatterBase {

  /*---------------------------------------------------------------------
   *
   * Configuration.
   *
   *---------------------------------------------------------------------*/

  /**
   * Returns an array of formatting styles.
   *
   * @return string[]
   *   Returns an associative array with internal names as keys and
   *   human-readable translated names as values.
   */
  protected static function getEntityReferenceStyles() {
    return [
      'id'        => t("Use the entity's ID"),
      'title'     => t("Use the entity's name"),
      'custom'    => t('Use custom text'),
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
      '_self'    => t('Open the linked entity in the same tab/window'),
      '_blank'   => t('Open the linked entity in a new tab/window'),
      'download' => t('Download the linked entity'),
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
      'alternate' => t('Alternate form of this entity'),
      'author'    => t('Author information'),
      'bookmark'  => t('Bookmarkable permalink'),
      'canonical' => t('Canonical (preferred) form of this entity'),
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
        'entityReferenceStyle' => 'title',
        'titleCustomText'      => '',
        'classes'              => '',
        'showLink'             => TRUE,
        'openLinkIn'           => '_self',
        'linkTopic'            => 'any',
        'listStyle'            => 'span',
        'listSeparator'        => ', ',
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
    switch ($this->getSetting('entityReferenceStyle')) {
      case 'title':
        $summary[] = $this->t('Show entity name');
        break;

      case 'id':
        $summary[] = $this->t('Show entity ID');
        break;

      case 'custom':
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
      return $this->t("Format field values as links that show the entity's name or ID. Multiple field values are shown as a list on one line, bulleted, numbered, or in blocks.");
    }

    return $this->t("Format a field as a link that shows the entity name or ID.");
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $formState) {
    $this->sanitizeSettings();
    $isMultiple = $this->fieldDefinition->getFieldStorageDefinition()->isMultiple();

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
    $elements = parent::settingsForm($form, $formState);
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
    $elements['entityReferenceStyle'] = [
      '#title'         => $this->t('Link title'),
      '#type'          => 'select',
      '#options'       => $this->getEntityReferenceStyles(),
      '#default_value' => $this->getSetting('entityReferenceStyle'),
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-general-entity-reference-style',
        ],
      ],
      '#attributes'    => [
        'class'        => [
          'entityReferenceStyle-' . $marker,
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
          'formatter_suite-general-entity-reference-title-custom-text',
        ],
      ],
      '#states'        => [
        'visible'      => [
          '.entityReferenceStyle-' . $marker => [
            'value'    => 'custom',
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
          'formatter_suite-general-entity-reference-classes',
        ],
      ],
    ];

    $elements['sectionBreak2'] = [
      '#markup' => '<div class="formatter_suite-section-break"></div>',
      '#weight' => $weight++,
    ];

    $elements['showLink'] = [
      '#title'         => $this->t('Link to the entity'),
      '#type'          => 'checkbox',
      '#default_value' => $this->getSetting('showLink'),
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-general-entity-reference-show-link',
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
          'formatter_suite-general-entity-reference-open-link-in',
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
          'formatter_suite-general-entity-reference-link-topic',
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
            'formatter_suite-general-entity-reference-list-style',
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
            'formatter_suite-general-entity-reference-list-separator',
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
    // Get current settings.
    $entityReferenceStyle = $this->getSetting('entityReferenceStyle');
    $showLink             = $this->getSetting('showLink');
    $openLinkIn           = $this->getSetting('openLinkIn');
    $linkTopic            = $this->getSetting('linkTopic');

    $isMultiple = $this->fieldDefinition->getFieldStorageDefinition()->isMultiple();

    // Get setting defaults.
    $defaults = $this->defaultSettings();

    // Legacy settings.
    //
    // An earlier version of this formatter supported an entity reference
    // style of "titlelink". We now treat this as "title", and $showLink TRUE.
    if ($entityReferenceStyle === 'titlelink') {
      $entityReferenceStyle = 'title';
      $showLink = TRUE;
    }

    // Sanitize & validate.
    //
    // While <select> inputs constrain choices to those we define in the
    // form, it is possible to hack a form response send other values back.
    // So check all <select> choices and use the default when a value is
    // empty or unknown.
    $entityReferenceStyles = $this->getEntityReferenceStyles();
    if (empty($entityReferenceStyle) === TRUE ||
        isset($entityReferenceStyles[$entityReferenceStyle]) === FALSE) {
      $entityReferenceStyle = $defaults['entityReferenceStyle'];
      $this->setSetting('entityReferenceStyle', $entityReferenceStyle);
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
    $this->setSetting('showLink', $showLink);

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
  protected function checkAccess(EntityInterface $entity) {
    switch ($this->getSetting('entityReferenceStyle')) {
      default:
      case 'id':
        return AccessResult::allowed();

      case 'title':
        // Make sure we have access to the title.
        return $entity->access('view label', NULL, TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $this->sanitizeSettings();

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
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      if (empty($entity->id()) === TRUE) {
        continue;
      }

      // Get the entity URL.
      // -------------------
      // Get the entity's URL, or abort if it doesn't have one yet.
      $url = NULL;
      if ($entity->isNew() === FALSE) {
        // Entity exists and should have a URL.
        try {
          $url = $entity->urlInfo();
        }
        catch (UndefinedLinkTemplateException $e) {
          // The entity type doesn't have a way of returning a URI.
        }
      }

      $urlOptions = $url->getOptions();

      //
      // Get the link title.
      // -------------------
      // Use custom text, the entity name, or the entity ID. If custom
      // text is selected, but there isn't any, fall through to the entity
      // name. If there is none of that (which should be impossible), fall
      // through to the file name.
      switch ($this->getSetting('entityReferenceStyle')) {
        case 'custom':
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

          // Fall-through and use the entity's title as the title text.
        case 'title':
          // Security: Entity names are entered by a user. They should never
          // include HTML or HTML entities.
          //
          // Passing this text as '#title' on a link will automatically
          // escape special characters and insure they do not cause harm.
          // We therefore don't need to do any special filtering here.
          $title = $entity->label();
          if (empty($title) === FALSE) {
            break;
          }

          // Fall-through and use the ID as the title text.
        case 'id':
          // Use the ID.
          $title = (string) $entity->id();
          break;
      }

      //
      // Build the link.
      // ---------------
      // If the link is disabled, show the title text within a <span>.
      // Otherwise, build a URL and create a link.
      if ($this->getSetting('showLink') === FALSE) {
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
      // Replace the 'field' theme with ours, which supports lists.
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
