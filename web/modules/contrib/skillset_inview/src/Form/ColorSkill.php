<?php

namespace Drupal\skillset_inview\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;

/**
 * Class colorSkill.
 *
 * @package Drupal\skillset_inview\Form
 */
class ColorSkill extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'skillset_inview_color';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'skillset_inview.color',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('skillset_inview.color');

    $form = [
      '#attached' => [
        'library' => [
          'skillset_inview/color',
          'skillset_inview/admin',
        ],
      ],
    ];

    $color_active_default = $config->get('color-active');
    $color_bar_default = $config->get('color-bar');
    $color_bar_opacity_default = $config->get('color-bar-opacity');
    $color_back_default = $config->get('color-back');
    $color_back_opacity_default = $config->get('color-back-opacity');
    $color_border_default = $config->get('color-border');
    $color_labels_default = $config->get('color-labels');
    $color_inside_default = $config->get('color-percent-inside');
    $color_outside_default = $config->get('color-percent-outside');

    $color_bar_color = $this->hexToCssRgb($color_bar_default) . ',' . ($color_bar_opacity_default / 100);
    $color_back_color = $this->hexToCssRgb($color_back_default) . ',' . ($color_back_opacity_default / 100);

    $form['color-active'] = [
      '#type' => 'radios',
      '#options' => ['1' => $this->t('Yes'), '0' => $this->t('No')],
      '#title' => $this->t('Would you like to use your own color selections?'),
      '#default_value' => $color_active_default,
    ];

    $form['farbtastic'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => 'farbtastic-container',
      ),
      '#states' => [
        'visible' => [
          ':input[name="color-active"]' => ['value' => '1'],
        ],
      ],
    );

    $form['farbtastic']['farbtastic-box'] = [
      '#markup' => '<div class="farbtastic-element"></div>',
    ];

    $form['preview'] = [
      '#type' => 'fieldgroup',
      '#title' => 'Skillbar Preview',
      '#states' => [
        'visible' => [
          ':input[name="color-active"]' => ['value' => '1'],
        ],
      ],
      '#attributes' => [
        'class' => ['skillbar-preview', 'no-background'],
      ],
    ];

    $demo = '<h3 class="skill-label" style="color:' . $color_labels_default . '">' . $this->t('Skillset Heading') . '</h3><dl>' . PHP_EOL . '<dt class="skill-label" style="color:' . $color_labels_default . '">' . $this->t('A Skill') . '</dt>' . PHP_EOL . '<dd class="column col-xs-12 col-sm-12 col-md-8 item-1 skill-line" style="background:rgba(' . $color_back_color . ');border:1px solid ' . $color_border_default . ';">' . PHP_EOL .
      '<div class="skill-bar" style="background:rgba(' . $color_bar_color . ');">&nbsp;<span class="percent inside" style="color:' . $color_inside_default . '">78%</span></div>' . PHP_EOL .
      '<span class="percent outside" style="color:' . $color_outside_default . '">23%</span></dd>' . PHP_EOL .
      '</dl><small>' . PHP_EOL .
      $this->t('Percent appears on the outside when value is less than 25%.') . '</small>';
    $form['preview']['current-choices'] = [
      '#markup' => Markup::create($demo),
    ];

    $form['clearfix-header'] = [
      '#markup' => '<div class="clearfix"></div>',
    ];

    $form['fields'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => 'color-fields',
      ],
      '#states' => [
        'visible' => [
          ':input[name="color-active"]' => ['value' => '1'],
        ],
      ],
    ];

    $tip = '<ul class="no-bullet"><li>' . $this->t('°All color fields are expected as hexadecimal values, including hash (#) character.  Either 3 or 6 characters in length are allowed.') . '</li><script>document.write("<li>' . $this->t('To use Color Picker:  Click into inputs below, then color picker will associate to that field. Clicking on any other input will re-associate instantly.') . '</li>");</script></ul>';

    $form['fields']['hash-note'] = [
      '#markup' => Markup::create($tip),
    ];

    $form['fields']['inline-elements'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Skillbar Coloring°'),
      '#attributes' => [
        'class' => ['elements-inline', 'no-background'],
      ],
    ];

    $form['fields']['inline-elements']['bar'] = [
      '#type' => 'fieldgroup',
      '#title' => 'Skillbar Color',
    ];
    $form['fields']['inline-elements']['bar']['color-bar'] = [
      '#type' => 'textfield',
      '#size' => '7',
      '#maxlength' => '7',
      '#title' => $this->t('Select skillbar color'),
      '#title_display' => 'invisible',
      '#default_value' => $color_bar_default,
      '#attributes' => ['style' => 'background:' . $color_bar_default . ';color:' . static::getBrightness($color_bar_default)],
      '#wrapper_attributes' => [
        'class' => ['color-helper'],
      ],
    ];
    $form['fields']['inline-elements']['bar']['color-bar-opacity'] = [
      '#type' => 'range',
      '#attributes' => [
        'max' => 100,
        'min' => 0,
        'step' => 1,
        'style' => 'width:76%;',
      ],
      '#title' => $this->t('Skillbar Opacity'),
      '#field_suffix' => '<span class="visual-assist">' . $color_bar_opacity_default . '</span>%',
      '#default_value' => $color_bar_opacity_default,
      '#wrapper_attributes' => [
        'class' => ['percent-column'],
      ],
    ];

    $form['fields']['inline-elements']['back'] = [
      '#type' => 'fieldgroup',
      '#title' => 'Background Color',
    ];
    $form['fields']['inline-elements']['back']['color-back'] = [
      '#type' => 'textfield',
      '#size' => '7',
      '#maxlength' => '7',
      '#title' => $this->t('Select skillbar background color'),
      '#title_display' => 'invisible',
      '#default_value' => $color_back_default,
      '#attributes' => ['style' => 'background:' . $color_back_default . ';color:' . static::getBrightness($color_back_default)],
      '#wrapper_attributes' => [
        'class' => ['color-helper'],
      ],
    ];
    $form['fields']['inline-elements']['back']['color-back-opacity'] = [
      '#type' => 'range',
      '#attributes' => [
        'max' => 100,
        'min' => 0,
        'step' => 1,
        'style' => 'width:76%;',
      ],
      '#title' => $this->t('Background Opacity'),
      '#field_suffix' => '<span class="visual-assist">' . $color_back_opacity_default . '</span>%',
      '#default_value' => $color_back_opacity_default,
      '#wrapper_attributes' => [
        'class' => ['percent-column'],
      ],
    ];

    $form['fields']['inline-elements']['border'] = [
      '#type' => 'fieldgroup',
      '#title' => 'Background Border',
    ];
    $form['fields']['inline-elements']['border']['color-border'] = [
      '#type' => 'textfield',
      '#size' => '7',
      '#maxlength' => '7',
      '#title' => $this->t('Select skillbar border color'),
      '#title_display' => 'invisible',
      '#default_value' => $color_border_default,
      '#attributes' => ['style' => 'background:' . $color_border_default . ';color:' . static::getBrightness($color_border_default)],
      '#wrapper_attributes' => [
        'class' => ['color-helper'],
      ],
    ];

    $form['fields']['clearfix-mid'] = [
      '#markup' => '<div class="clearfix"></div>',
    ];

    $form['fields']['percent-elements'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Text Colors°'),
      '#attributes' => [
        'class' => ['elements-inline', 'no-background'],
      ],
    ];

    $form['fields']['percent-elements']['labels'] = [
      '#type' => 'fieldgroup',
      '#title' => 'Labels &amp; Header',
    ];

    $form['fields']['percent-elements']['labels']['color-labels'] = [
      '#type' => 'textfield',
      '#size' => '7',
      '#maxlength' => '7',
      '#title' => $this->t('Select color for "Skill Label" And "Header" text'),
      '#title_display' => 'invisible',
      '#default_value' => $color_labels_default,
      '#attributes' => ['style' => 'background:' . $color_labels_default . ';color:' . static::getBrightness($color_labels_default)],
      '#wrapper_attributes' => [
        'class' => ['color-helper'],
      ],
    ];

    $form['fields']['percent-elements']['inside'] = [
      '#type' => 'fieldgroup',
      '#title' => 'Inside Percent',
    ];

    $form['fields']['percent-elements']['inside']['color-percent-inside'] = [
      '#type' => 'textfield',
      '#size' => '7',
      '#maxlength' => '7',
      '#title' => $this->t('Select color for "Inside Percent" text'),
      '#title_display' => 'invisible',
      '#default_value' => $color_inside_default,
      '#attributes' => ['style' => 'background:' . $color_inside_default . ';color:' . static::getBrightness($color_inside_default)],
      '#wrapper_attributes' => [
        'class' => ['color-helper'],
      ],
    ];

    $form['fields']['percent-elements']['outside'] = [
      '#type' => 'fieldgroup',
      '#title' => 'Outside Percent',
    ];

    $form['fields']['percent-elements']['outside']['color-percent-outside'] = [
      '#type' => 'textfield',
      '#size' => '7',
      '#maxlength' => '7',
      '#title' => $this->t('Select color for "Outside Percent" text'),
      '#title_display' => 'invisible',
      '#default_value' => $color_outside_default,
      '#attributes' => ['style' => 'background:' . $color_outside_default . ';color:' . static::getBrightness($color_outside_default)],
      '#wrapper_attributes' => [
        'class' => ['color-helper'],
      ],
    ];

    $form['clearfix-footer'] = [
      '#markup' => '<div class="clearfix"></div>',
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Changes'),
    ];
    $form['actions']['save']['#dropbutton'] = 'submit';

    $form['actions']['reset'] = [
      '#type' => 'submit',
      '#dropbutton' => 'submit',
      '#value' => $this->t('Cancel Current Changes'),
      '#submit' => ['::cancelChanges'],
      '#attributes' => [
        'title' => $this->t('Clears any changes to previous saved state.'),
      ],
    ];

    $form['actions']['restore'] = [
      '#type' => 'submit',
      '#dropbutton' => 'submit',
      '#value' => $this->t('Restore Original Greys'),
      '#submit' => ['::makeGrey'],
      '#attributes' => [
        'title' => $this->t('Restore to initial skillset color theme'),
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Causes page to simply reload.
   */
  public function cancelChanges() {
    drupal_set_message($this->t('Any previous changes have been abandoned.'), 'status');
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if ($values['color-active'] == 1) {
      $watch_hexes = [
        'color-bar',
        'color-back',
        'color-border',
        'color-labels',
        'color-percent-inside',
        'color-percent-outside',
      ];
      foreach ($watch_hexes as $item) {
        if (!preg_match('/#([a-fA-F0-9]{3}){1,2}\b/', $values[$item])) {
          $form_state->setErrorByName($item, $this->t('<q>@color</q> is not a valid hexadecimal color.', ['@color' => $values[$item]]));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function makeGrey() {
    $this->config('skillset_inview.color')
      ->set('color-bar', '#333333')
      ->set('color-bar-opacity', 80)
      ->set('color-back', '#7D7D7D')
      ->set('color-back-opacity', 20)
      ->set('color-border', '#666666')
      ->set('color-labels', '#000000')
      ->set('color-percent-inside', '#FFFFFF')
      ->set('color-percent-outside', '#000000')
      ->save();
    Cache::invalidateTags(['rendered']);
    drupal_set_message($this->t('Restored original grey theme.'), 'status');
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('skillset_inview.color')
      ->set('color-active', $form_state->getValue('color-active'))
      ->set('color-bar', $form_state->getValue('color-bar'))
      ->set('color-bar-opacity', $form_state->getValue('color-bar-opacity'))
      ->set('color-back', $form_state->getValue('color-back'))
      ->set('color-back-opacity', $form_state->getValue('color-back-opacity'))
      ->set('color-border', $form_state->getValue('color-border'))
      ->set('color-labels', $form_state->getValue('color-labels'))
      ->set('color-percent-inside', $form_state->getValue('color-percent-inside'))
      ->set('color-percent-outside', $form_state->getValue('color-percent-outside'))
      ->save();
    Cache::invalidateTags(['rendered']);
    parent::submitForm($form, $form_state);
  }

  /**
   * Tool function.  Hex to rbg converter.
   * @param string $hex
   * @return string
   */
  public function hexToCssRgb($hex = '000000') {
    $rgb = static::hexToSixToRgb($hex);
    return implode(',', $rgb);
  }

  /**
   * Tool function.  determine brigness of color (for degrade text color state).
   * @param string $hex
   * @return string
   */
  public function getBrightness($hex = '000000') {
    $rgb = static::hexToSixToRgb($hex);
    $r = $g = $b = 0;
    extract($rgb, EXTR_PREFIX_SAME, "wddx");
    $value = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
    return ($value < 125) ? 'white' : 'black';
  }

  /**
   * Utility function.  takes a hex color -> to 6char -> to rgb data set.
   * @param string $hex
   * @return array
   */
  public function hexToSixToRgb($hex = '000') {
    $hex = str_replace('#', '', $hex);
    $r = $g = $b = '00';
    if (strlen($hex) == 3) {
      $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
      $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
      $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
    }
    else {
      $r = hexdec(substr($hex, 0, 2));
      $g = hexdec(substr($hex, 2, 2));
      $b = hexdec(substr($hex, 4, 2));
    }
    return array('r' => $r, 'g' => $g, 'b' => $b);
  }

}
