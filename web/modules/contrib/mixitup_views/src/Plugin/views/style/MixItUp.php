<?php

namespace Drupal\mixitup_views\Plugin\views\style;

use Drupal\Core\Link;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Core\Url;

/**
 * Style plugin for MixItUp.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "mixitup_views",
 *   title = @Translation("MixItUp"),
 *   help = @Translation("Display content using MixItUp."),
 *   theme = "mixitup_views_view_mixitup",
 *   theme_file = "mixitup_views.theme.inc",
 *   display_types = {"normal"}
 * )
 */
class MixItUp extends StylePluginBase {

  /**
   * Overrides Drupal\views\Plugin\Plugin::$usesOptions.
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
  protected $usesRowClass = TRUE;

  /**
   * Mixitup service.
   *
   * @var object|null
   */
  protected $mixitupFuncService;
  /**
   * Default options.
   *
   * @var array
   */
  protected $defaultOptions;

  /**
   * Does the style plugin support grouping of rows.
   *
   * @var bool
   */
  protected $usesGrouping = FALSE;

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $mixitupFuncService) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->mixitupFuncService = $mixitupFuncService;
    $this->defaultOptions = $this->mixitupFuncService->getDefaultOptions(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('mixitup_views.func_service'));
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    // Get the default options.
    $default_options = $this->defaultOptions;
    foreach ($default_options as $option => $default_value) {
      $options[$option] = [
        'default' => $default_value,
      ];
      if (\is_int($default_value)) {
        $options[$option]['bool'] = TRUE;
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    // Add Mixitup options to views form.
    $form['mixitup'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('MixItUp Animation settings'),
    ];
    if ($this->mixitupFuncService->isMixitupInstalled()) {
      $options = $this->options;
      $form['filter_type'] = [
        '#type' => 'select',
        '#title' => t('Type of filtering'),
        '#options' => [
          'checkboxes' => t('Checkboxes'),
          'select' => t('Selectboxes'),
        ],
        '#default_value' => $options['filter_type'],
        '#description' => t('Select the preferred field type for filtering'),
      ];
      $form['agregation_type'] = [
        '#type' => 'select',
        '#title' => t('Agregation type'),
        '#options' => [
          'and' => t('AND'),
          'or' => t('OR'),
        ],
        '#default_value' => $options['agregation_type'],
        '#description' => t('The terms agregation type detects how to fetch nodes when multiple checkboxes selected.AND - node should have all of the terms to be selected, OR - one of them is enough.'),
      ];
      $form['animation_enable'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable Animation'),
        '#default_value' => $options['animation_enable'],
        '#attributes' => [
          'class' => ['animation_enable'],
        ],
      ];
      $form['animation_effects'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Effects'),
        '#description' => $this->t('The effects for all filter operations as a space-separated string.'),
        '#default_value' => $options['animation_effects'],
      ];
      $form['animation_duration'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Duration'),
        '#description' => $this->t('The duration of the animation in milliseconds.'),
        '#default_value' => $options['animation_duration'],
      ];
      $form['animation_easing'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Easing'),
        '#description' => $this->t('For a full list of accepted values, check out easings.net.'),
        '#default_value' => $options['animation_easing'],
      ];
      $form['animation_perspectiveDistance'] = [
        '#type' => 'textfield',
        '#title' => $this->t('perspectiveDistance'),
        '#description' => $this->t('The perspective value in CSS units applied to the container during animations.'),
        '#default_value' => $options['animation_perspectiveDistance'],
      ];
      $form['animation_perspectiveOrigin'] = [
        '#type' => 'textfield',
        '#title' => $this->t('perspectiveOrigin'),
        '#description' => $this->t('The perspective-origin value applied to the container during animations.'),
        '#default_value' => $options['animation_perspectiveOrigin'],
      ];
      $form['animation_queue'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Queue'),
        '#description' => $this->t('Enable queuing for all operations received while an another operation is in progress.'),
        '#default_value' => $options['animation_queue'],
        '#attributes' => ['class' => ['animation_queue']],
      ];
      $form['animation_queueLimit'] = [
        '#type' => 'textfield',
        '#title' => $this->t('queueLimit'),
        '#description' => $this->t('The maximum number of operations allowed in the queue at any time.'),
        '#default_value' => $options['animation_queueLimit'],
      ];

      foreach ($this->defaultOptions as $option => $default_value) {
        $form[$option]['#fieldset'] = 'mixitup';
        if ($option != 'animation_enable') {
          $selectors['.animation_enable'] = ['checked' => TRUE];
          if ($option == 'animation_queueLimit') {
            $selectors['.animation_queue'] = ['checked' => TRUE];
          }
          $form[$option]['#states'] = [
            'visible' => $selectors,
          ];
        }
      }
      $sorts = $this->view->displayHandlers->get($this->view->current_display)->getOption('sorts');
      $form['mixitup_sorting_settings'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('MixItUp Sorting settings'),
      ];
      $form['use_sort'] = [
        '#type' => 'checkbox',
        '#fieldset' => 'mixitup_sorting_settings',
        '#title' => $this->t('Use sorting.'),
        '#description' => $this->t('If you want to add new Sort criteria, add them under views "Sort criteria", at first.'),
        '#default_value' => $options['use_sort'],
        '#attributes' => [
          'class' => ['use_sort'],
        ],
      ];
      if ($sorts) {
        $form['sorts'] = [
          '#type' => 'div',
          '#fieldset' => 'mixitup_sorting_settings',
        ];
        foreach ($sorts as $id => $sort) {
          $sort_id = $sort['table'] . '_' . $sort['field'];
          if (isset($options)) {
            $form['sorts'][$sort_id] = [
              '#type' => 'textfield',
              '#title' => $this->t('Label for "@f"', ['@f' => $id]),
              '#description' => $this->t("If you don't want to use it, just make this field empty."),
              '#default_value' => $options['sorts'][$sort_id] ?? '',
              '#states' => [
                'visible' => [
                  '.use_sort' => ['checked' => TRUE],
                ],
              ],
            ];
          }
        }
      }

      $form['mixitup_vocab'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('MixItUp Vocabulary settings'),
      ];
      $form['restrict_vocab'] = [
        '#type' => 'checkbox',
        '#fieldset' => 'mixitup_vocab',
        '#title' => $this->t('Restrict terms to particular vocabulary.'),
        '#default_value' => $options['restrict_vocab'],
        '#attributes' => [
          'class' => ['restrict_vocab_enable'],
        ],
      ];
      // Load all vocabularies.
      $all_vocabs = Vocabulary::loadMultiple();

      $vocabulary_options = [];
      foreach ($all_vocabs as $key_vid => $vocab) {
        $vocabulary_options[$key_vid] = $vocab->get('name');
      }

      $form['restrict_vocab_ids'] = [
        '#type' => 'select',
        '#fieldset' => 'mixitup_vocab',
        '#title' => $this->t('Select vocabularies'),
        '#multiple' => TRUE,
        '#options' => $vocabulary_options,
        '#default_value' => $options['restrict_vocab_ids'],
        '#states' => [
          'visible' => [
            '.restrict_vocab_enable' => ['checked' => TRUE],
          ],
        ],
      ];

      $form['filters_settings'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('MixItUp Filters settings'),
      ];

      $form['hide_unchecked_chekboxes'] = [
        '#type' => 'checkbox',
        '#fieldset' => 'filters_settings',
        '#title' => $this->t('Hide unchecked checkboxes if one of items selected.'),
        '#description' => $this->t('If you want to hide other filters if you selected one of them, please check checkbox above.'),
        '#default_value' => $options['hide_unchecked_chekboxes'],
        '#attributes' => [
          'class' => ['hide_unchecked_chekboxes'],
        ],
      ];
    }
    else {
      $url = Url::fromUri('https://github.com/patrickkunka/mixitup/tree/v3');
      $mixitup_link = Link::fromTextAndUrl($this->t('MixItUp'), $url);
      $url_readme = Url::fromUri('base:admin/help/mixitup_views', [
        'absolute' => TRUE,
        'attributes' => ['target' => '_blank'],
      ]);
      $readme_link = Link::fromTextAndUrl($this->t('README'), $url_readme);
      // Disable Mixitup.
      $form['mixitup_disabled'] = [
        '#markup' => $this->t('Please, download !mixitup plugin to libraries/mixitup
         directory. For more information read !read. After that, you can use it.', [
           '!mixitup' => $mixitup_link,
           '!read' => $readme_link,
         ]),
        '#fieldset' => 'mixitup',
      ];
    }
  }

}
