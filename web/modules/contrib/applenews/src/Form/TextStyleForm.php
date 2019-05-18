<?php

namespace Drupal\applenews\Form;

use Drupal\applenews\AppleNewsRequestDataTrait;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for text style add and edit forms.
 */
class TextStyleForm extends EntityForm {

  use AppleNewsRequestDataTrait;

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\applenews\Entity\ApplenewsTextStyle
   */
  protected $entity;

  /**
   * The text style entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Constructs a class for apple news text style add and edit forms.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The text style entity storage.
   */
  public function __construct(EntityStorageInterface $storage) {
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('applenews_text_style')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\applenews\Entity\ApplenewsTextStyle $entity */
    $entity = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text style name'),
      '#default_value' => $entity->label(),
      '#required' => TRUE,
    ];
    $form['name'] = [
      '#type' => 'machine_name',
      '#machine_name' => [
        'exists' => [$this->storage, 'load'],
      ],
      '#default_value' => $entity->id(),
      '#required' => TRUE,
    ];
    $form['fontName'] = [
      '#type' => 'select',
      '#title' => $this->t('Font name'),
      '#options' => $this->getFontNames(),
      '#default_value' => $entity->get('fontName'),
      '#description' => $this->t('The font family to use for text rendering, for example Gill Sans. Using a combination of fontFamily, fontWeight, and fontStyle you can define the appearance of the text. News automatically selects the appropriate font variant from the available variants in that family.'),
    ];
    $form['fontSize'] = [
      '#type' => 'number',
      '#title' => $this->t('Font size'),
      '#default_value' => $entity->get('fontSize'),
      '#description' => $this->t('The size of the font, in points. As a best practice, try not to go below 16 points for body text.'),
    ];
    // @todo: Dymanically update per font Family from $this->getFontData()
    $form['fontWidth'] = [
      '#type' => 'number',
      '#title' => $this->t('Font width'),
      '#default_value' => $entity->get('fontWidth'),
      '#description' => $this->t('The font width (known in CSS as font-stretch), defines the width characteristics of a font variant between normal, condensed and expanded.'),
    ];
    $form['fontWeight'] = [
      '#type' => 'number',
      '#title' => $this->t('Font weight'),
      '#default_value' => $entity->get('fontWeight'),
      '#description' => $this->t('The font weight to apply for font selection.'),
    ];
    $form['fontStyle'] = [
      '#type' => 'select',
      '#title' => $this->t('Font style'),
      '#options' => [
        'normal' => $this->t('Normal'),
        'italic' => $this->t('Italic'),
        'oblique' => $this->t('Oblique'),
      ],
      '#default_value' => $entity->get('fontStyle'),
      '#description' => $this->t('The font style to apply.'),
    ];

    $form['textColor'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text color'),
      '#default_value' => $entity->get('textColor'),
      '#description' => $this->t('The text color, defined as a 3- to 8-character RGBA hexadecimal string; e.g., #000 for black or #FF00007F for red with an alpha (opacity) of 50%.'),
      '#size' => 8,
      '#maxlength' => 8,
    ];

    $form['hasTextShadow'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Text Shadow'),
      '#description' => $this->t('The text shadow for this style.'),
      '#default_value' => $entity->get('textShadow') ? TRUE : FALSE,
      '#tree' => TRUE,
    ];
    $form['textShadow'] = [
      '#type' => 'details',
      '#title' => $this->t('Text Shadow'),
      '#description' => $this->t('The text shadow for this style.'),
      '#collapsed' => FALSE,
      '#states' => [
        'visible' => [
          'input[name="hasTextShadow"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['textShadow']['radius'] = [
      '#type' => 'number',
      '#title' => $this->t('Radius'),
      '#description' => $this->t("The shadow's radius as a value between 0 and 100 in points."),
    ];
    $form['textShadow']['opacity'] = [
      '#type' => 'number',
      '#title' => $this->t('Opacity'),
      '#description' => $this->t('Opacity of the shadow as a value between 0 and 1.'),
    ];
    $form['textShadow']['color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Color'),
      '#size' => 8,
      '#maxlength' => 8,
      '#description' => $this->t('The stroke color, defined as a 3- to 8-character RGBA hexadecimal string; e.g., #000 for black or #FF00007F for red with an alpha (opacity) of 50%.'),
    ];
    $form['textShadow']['offset'] = [
      '#type' => 'details',
      '#title' => $this->t('Offset'),
      '#description' => $this->t("The shadow's offset as a value between -50 and 50 in points."),
    ];
    $form['textShadow']['offset']['x'] = [
      '#type' => 'number',
      '#title' => $this->t('X'),
      '#description' => $this->t('The x offset, as a value between -50.0 and 50.0.'),
    ];
    $form['textShadow']['offset']['y'] = [
      '#type' => 'number',
      '#title' => $this->t('Y'),
    ];

    $form['textTransform'] = [
      '#type' => 'select',
      '#title' => $this->t('Text transform'),
      '#options' => [
        'none' => $this->t('None'),
        'uppercase' => $this->t('Uppercase'),
        'lowercase' => $this->t('Lowercase'),
        'capitalize' => $this->t('Capitalize'),
      ],
      '#default_value' => '',
    ];
    $form['underline'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Underline'),
      '#default_value' => '',
      '#description' => $this->t('The text underlining. This style can be used for links. Set underline to true to use the text color as the underline color, or provide a text decoration with a different color.'),
    ];
    $form['strikethrough'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Strikethrough'),
      '#default_value' => '',
      '#description' => $this->t('The text strikethrough. Set strikethrough to true to use the text color inherited from the textColor property as the strikethrough color, or provide a text decoration definition with a different color.'),
    ];
    $form['hasStroke'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Stroke'),
      '#default_value' => '',
      '#description' => $this->t('The stroke style for the text. By default, stroke will be omitted. See <a target="_blank" href="https://developer.apple.com/documentation/apple_news/text_stroke_style">documentation</a> for more details.'),
    ];
    $form['backgroundColor'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Background color'),
      '#default_value' => '',
      '#size' => 8,
      '#maxlength' => 8,
      '#description' => $this->t('The background color for text lines, defined as a 3- to 8-character RGBA hexadecimal string; e.g., #000 for black or #FF00007F for red with an alpha (opacity) of 50%.'),
    ];
    $form['verticalAlignment'] = [
      '#type' => 'select',
      '#title' => $this->t('Vertical alignment'),
      '#options' => [
        'baseline' => 'Baseline',
        'superscript' => 'Superscript',
        'subscript' => 'Subscript',
      ],
      '#default_value' => '',
      '#description' => $this->t('The vertical alignment of the text. You can use this property for superscripts and subscripts.'),
    ];
    $form['tracking'] = [
      '#type' => 'number',
      '#title' => $this->t('Tracking'),
      '#default_value' => $this->entity->label(),
      '#description' => $this->t('The amount of tracking (spacing between characters) in text, as a percentage of the fontSize. The actual spacing between letters is determined by combining information from the font and font size. Example: Set tracking to 0.5 to make the distance between characters increase by 50% of the fontSize. With a font size of 10, the additional space between characters is 5 points.'),
    ];

    $form['textAlignment'] = [
      '#type' => 'select',
      '#title' => $this->t('Text alignment'),
      '#options' => [
        'none' => 'None',
        'left' => 'Left',
        'center' => 'Center',
        'right' => 'Right',
        'justified' => 'Justified',
      ],
      '#default_value' => '',
    ];
    $form['lineHeight'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Line height'),
      '#default_value' => '',
      '#description' => $this->t('The default line height, in points.'),
    ];
    $form['dropCapStyle'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Drop cap style'),
      '#default_value' => '',
      '#description' => $this->t('Defines the style of drop cap to apply to the first paragraph of the component.'),
    ];
    $form['linkStyle'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Line style'),
      '#default_value' => '',
      '#description' => $this->t('Text styling for all links within a text component.'),
    ];
    $form['hyphenation'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hyphenation'),
      '#default_value' => '',
      '#description' => $this->t('Indicates whether text should be hyphenated when necessary.'),
    ];

    return parent::form($form, $form_state);
  }

  /**
   * Provides list supported font names.
   */
  protected function getFontNames() {
    $fonts = array_keys($this->getFontData());
    return array_combine($fonts, $fonts);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // @todo: make sure to avoid saving default values.
    parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->entity->urlInfo('collection'));
  }

}
