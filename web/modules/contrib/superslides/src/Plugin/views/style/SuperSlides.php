<?php

namespace Drupal\superslides\Plugin\views\style;

use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Style plugin for the cards view.
 *
 * @ViewsStyle(
 *   id = "superslides",
 *   title = @Translation("Superslides"),
 *   help = @Translation("Superslides"),
 *   theme = "superslides_style",
 *   display_types = {"normal"}
 * )
 */
class SuperSlides extends StylePluginBase {

  /**
   * Does the style plugin for itself support to add fields to it's output.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  /**
   * Denotes whether the plugin has an additional options form.
   *
   * @var bool
   */
  protected $usesOptions = TRUE;

  /**
   * Does the style plugin allows to use style plugins.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Does the style plugin support custom css class for the rows.
   *
   * @var bool
   */
  protected $usesRowClass = FALSE;

  /**
   * Does the style plugin support grouping.
   *
   * @var bool
   */
  protected $usesGrouping = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    // dsm($this,'from defoptions');.
    $options['autoplay'] = ['default' => TRUE];
    $options['autoplayinterval'] = ['default' => 1000];
    $options['show_animation'] = ['default' => 'fadeIn'];
    $options['duration'] = ['default' => 0];
    $options['delay'] = ['default' => 0];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $field_labels = array_keys($this->displayHandler->getFieldLabels(TRUE));

    $form['global'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Global'),
    ];

    $form['global']['slideshow_animation'] = [
      '#type' => 'select',
      '#title' => $this->t('Slideshow Animation'),
      '#default_value' => (isset($this->options['global']['slideshow_animation'])) ?
      $this->options['global']['slideshow_animation'] : $this->options['global']['slideshow_animation'],
      '#options' => [
        'slide' => $this->t('slide'),
        'fade' => $this->t('fade'),
      ],
      '#description' => $this->t('Enable to auto play.'),
    ];

    $form['global']['autoplay'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autoplay'),
      '#default_value' => (isset($this->options['global']['autoplay'])) ?
      $this->options['global']['autoplay'] : $this->options['autoplay'],
      '#description' => $this->t('Enable to auto play.'),
    ];

    $form['global']['autoplayinterval'] = [
      '#type' => 'number',
      '#title' => $this->t('Autoplay interval'),
      '#attributes' => [
        'min' => 1000,
        'step' => 1000,
        'value' => $this->options['global']['autoplayinterval'],
      ],
      '#default_value' => (isset($this->options['global']['autoplayinterval'])) ?
      $this->options['global']['autoplayinterval'] : $this->options['autoplayinterval'],
      '#description' => $this->t('Interval (in milliseconds) to go for next slide since the previous stopped.'),
    ];

    for ($i = 0; $i < count($field_labels); $i++) {

      $form[$field_labels[$i]] = [
        '#type' => 'fieldset',
        '#title' => $field_labels[$i],
      ];

      $form[$field_labels[$i]]['show_animation'] = [
        '#type' => 'select',
        '#title' => $this->t('Animation'),
        '#description' => $this->t('Animation'),
        '#default_value' => (isset($this->options[$field_labels[$i]]['show_animation'])) ?
        $this->options[$field_labels[$i]]['show_animation'] : $this->options['show_animation'],
        '#options' => [
          '' => $this->t('- NONE -'),
          'Sliding Entrances' => [
            'slideInDown' => $this->t('slideInDown'),
            'slideInLeft' => $this->t('slideInLeft'),
            'slideInRight' => $this->t('slideInRight'),
            'slideInUp' => $this->t('slideInUp'),
          ],
          'Fading Entrances' => [
            'fadeIn' => $this->t('fadeIn'),
            'fadeInDown' => $this->t('fadeInDown'),
            'fadeInDownBig' => $this->t('fadeInDownBig'),
            'fadeInLeft' => $this->t('fadeInLeft'),
            'fadeInLeftBig' => $this->t('fadeInLeftBig'),
            'fadeInRight' => $this->t('fadeInRight'),
            'fadeInRightBig' => $this->t('fadeInRightBig'),
            'fadeInUp' => $this->t('fadeInUp'),
            'fadeInUpBig' => $this->t('fadeInUpBig'),
          ],
          'Flippers' => [
            'flip' => $this->t('flip'),
            'flipInX' => $this->t('flipInX'),
            'flipInY' => $this->t('flipInY'),
          ],
          'Bouncing Entrances' => [
            'bounce' => $this->t('bounce'),
            'bounceIn' => $this->t('bounceIn'),
            'bounceInDown' => $this->t('bounceInDown'),
            'bounceInRight' => $this->t('bounceInRight'),
            'bounceInUp' => $this->t('bounceInUp'),
            'bounceInLeft' => $this->t('bounceInLeft'),
          ],
          'Rotating Entrances' => [
            'rotateIn' => $this->t('rotateIn'),
            'rotateInDownLeft' => $this->t('rotateInDownLeft'),
            'rotateInDownRight' => $this->t('rotateInDownRight'),
            'rotateInUpLeft' => $this->t('rotateInUpLeft'),
            'rotateInUpRight' => $this->t('rotateInUpRight'),
          ],
          'Zoom Entrances' => [
            'zoomIn' => $this->t('zoomIn'),
            'zoomInUp' => $this->t('zoomInUp'),
            'zoomInDown' => $this->t('zoomInDown'),
            'zoomInLeft' => $this->t('zoomInLeft'),
            'zoomInRight' => $this->t('zoomInRight'),
          ],
          'Attention Seeker' => [
            'hinge' => $this->t('hinge'),
            'rollIn' => $this->t('rollIn'),
            'shake' => $this->t('shake'),
            'flash' => $this->t('flash'),
            'pulse' => $this->t('pulse'),
            'rubberBand' => $this->t('rubberBand'),
            'swing' => $this->t('swing'),
            'tada' => $this->t('tada'),
            'jello' => $this->t('jello'),
          ],
        ],
      ];

      $form[$field_labels[$i]]['delay'] = [
        '#type' => 'number',
        '#title' => $this->t('Delay'),
        '#attributes' => [
          'min' => 0,
          'step' => 1,
          'value' => (isset($this->options[$field_labels[$i]]['delay'])) ?
          $this->options[$field_labels[$i]]['delay'] : $this->options['delay'],
        ],
        '#description' => $this->t('Specifies delay in seconds.'),
      ];

      $form[$field_labels[$i]]['duration'] = [
        '#type' => 'number',
        '#title' => $this->t('Duration'),
        '#attributes' => [
          'min' => 0,
          'step' => 1,
          'value' => (isset($this->options[$field_labels[$i]]['duration'])) ?
          $this->options[$field_labels[$i]]['duration'] : $this->options['duration'],
        ],
        '#description' => $this->t('Specifies delay in seconds.'),
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render() {

    $field_labels = array_keys($this->displayHandler->getFieldLabels(TRUE));

    for ($i = 0; $i < count($this->view->result); $i++) {
      for ($j = 0; $j < count($field_labels); $j++) {
        $field_item_list = $this->view->result[$i]->_entity->get($field_labels[$j]);
        $field_type = $field_item_list->getFieldDefinition()->getType();

        $renderdata[$i][$field_type] = [
          'value' => $this->view->style_plugin->getField($i, $field_labels[$j]),
          'label' => $field_labels[$j],
          'options' => $this->options[$field_labels[$j]],
        ];
      }
    }

    $item = new \stdClass();

    if (isset($renderdata)) {
      $item->renderData = $renderdata;
    }

    $build = [
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#rows' => $item,
    ];

    return $build;
  }

}
