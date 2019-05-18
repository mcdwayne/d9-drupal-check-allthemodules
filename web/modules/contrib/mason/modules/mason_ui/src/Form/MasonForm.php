<?php

namespace Drupal\mason_ui\Form;

use Drupal\Core\Url;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Serialization\Json;
use Drupal\mason\Entity\Mason;
use Drupal\mason\Form\MasonAdmin;
use Drupal\mason\MasonManagerInterface;

/**
 * Extends base form for mason instance configuration form.
 */
class MasonForm extends EntityForm {

  /**
   * The mason admin service.
   *
   * @var \Drupal\mason\Form\MasonAdmin
   */
  protected $admin;

  /**
   * The mason manager service.
   *
   * @var \Drupal\mason\MasonManagerInterface
   */
  protected $manager;

  /**
   * Constructs a MasonForm object.
   */
  public function __construct(MasonAdmin $admin, MasonManagerInterface $manager) {
    $this->admin = $admin;
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('mason.admin'),
      $container->get('mason.manager')
    );
  }

  /**
   * Returns the mason admin.
   */
  public function admin() {
    return $this->admin;
  }

  /**
   * Returns the mason manager.
   */
  public function manager() {
    return $this->manager;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Change page title for the duplicate operation.
    if ($this->operation == 'duplicate') {
      $form['#title'] = $this->t('<em>Duplicate mason optionset</em>: @label', ['@label' => $this->entity->label()]);
      $this->entity = $this->entity->createDuplicate();
    }

    // Change page title for the edit operation.
    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('<em>Edit mason optionset</em>: @label', ['@label' => $this->entity->label()]);
    }

    $mason     = $this->entity;
    $path      = drupal_get_path('module', 'mason');
    $tooltip   = ['class' => ['is-tooltip']];
    $options   = $mason->getOptions() ?: [];
    $readme    = Url::fromUri('base:' . $path . '/README.txt')->toString();
    $defaults  = Mason::load('default')->getOptions();
    $admin_css = $this->manager->configLoad('admin_css', 'blazy.settings');

    $form['#attributes']['class'][] = 'form--mason';
    $form['#attributes']['class'][] = 'form--slick';
    $form['#attributes']['class'][] = 'form--optionset';
    $form['#attached']['library'][] = 'mason/mason.admin';
    $form['#attached']['library'][] = 'blazy/blazy';

    if ($admin_css) {
      $form['#attached']['library'][] = 'blazy/admin';
    }

    $form['label'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Label'),
      '#default_value' => $mason->label(),
      '#maxlength'     => 255,
      '#required'      => TRUE,
      '#description'   => $this->t("Label for the Mason optionset."),
      '#attributes'    => $tooltip,
      '#prefix'        => '<div class="form__header form__half form__half--first has-tooltip clearfix">',
    ];

    // Keep the legacy CTools ID, i.e.: name as ID.
    $form['name'] = [
      '#type'          => 'machine_name',
      '#default_value' => $mason->id(),
      '#maxlength'     => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name'  => [
        'source' => ['label'],
        'exists' => '\Drupal\mason\Entity\Mason::load',
      ],
      '#attributes'    => $tooltip,
      '#disabled'      => !$mason->isNew(),
      '#suffix'        => '</div>',
    ];

    // Main JS options.
    $form['options'] = [
      '#type'        => 'details',
      '#tree'        => TRUE,
      '#open'        => TRUE,
      '#collapsed'   => FALSE,
      '#title'       => $this->t('Options'),
      '#attributes'  => ['class' => ['details--settings', 'has-tooltip']],
      '#description' => $this->t('Mason is a jQuery plugin that helps you create a perfect grid with no gaps and no ragged edges. It Works by mapping elements in a grid, finding where gaps are and filling them in as a stone mason would do. Mason is not Masonry, Isotope, Packery, Gridilicious or any of those other grid plugins. It is for creating perfect grids. Do not use quotes manually.'),
    ];

    $form['options']['debug'] = [
      '#type'  => 'checkbox',
      '#title' => $this->t('Debug'),
    ];

    $form['options']['layout'] = [
      '#type'        => 'select',
      '#title'       => $this->t('Layout'),
      '#options'     => [
        'none'  => $this->t('Fixed'),
        'fluid' => $this->t('Fluid'),
      ],
      '#description' => $this->t('There are two layouts, fluid and fixed. Mason will default to fixed. Fluid means it will be responsive.'),
    ];

    $form['options']['itemSelector'] = [
      '#type'        => 'textfield',
      '#title'       => $this->t('Item selector'),
      '#description' => $this->t('The element that makes up your grid. Use ".mason__box" to use the module template, otherwise DIY.'),
    ];

    $form['options']['ratio'] = [
      '#type'        => 'textfield',
      '#title'       => $this->t('Ratio'),
      '#description' => $this->t('The ratio is a number that is used to create the blocks based on column count and width. This is based on the number of columns requested and the browser width. The ratio will always be ( ratio x height ) to give you width.'),
    ];

    $form['options']['gutter'] = [
      '#type'         => 'textfield',
      '#title'        => $this->t('Gutter'),
      '#description'  => $this->t('Allows you to add spacing between the elements, think of this as a margin.'),
      '#field_suffix' => 'px',
    ];

    $form['options']['randomSizes'] = [
      '#type'  => 'checkbox',
      '#title' => $this->t('Random sizes'),
    ];

    $num_sizes = $form_state->get('num_sizes') ?: count($mason->getOptions('sizes'));
    if (is_null($num_sizes)) {
      $num_sizes = 1;
    }
    $form_state->set('num_sizes', $num_sizes);

    $form['options']['sizes'] = [
      '#type'        => 'details',
      '#tree'        => TRUE,
      '#open'        => TRUE,
      '#title'       => $this->t('Sizes'),
      '#description' => $this->t('Sizes are an array of sizes you wish to use in your grid. These are composed of block numbers. (e.g.: [1,1] means 1 block high, 1 block wide ).'),
      '#prefix'      => '<div id="edit-sizes-wrapper" class="form-wrapper-box">',
      '#suffix'      => '</div>',
      '#attributes'  => ['class' => ['form-wrapper--mason-grid', 'form-wrapper--mason-sizes']],
    ];

    for ($i = 0; $i < $num_sizes; $i++) {
      if (!isset($form['options']['sizes'][$i])) {
        $form['options']['sizes'][$i] = [
          '#type'          => 'textfield',
          '#title'         => $this->t('#@index', ['@index' => $i]),
          '#default_value' => (NULL !== $mason->getOptions('sizes', $i)) ? $mason->getOptions('sizes', $i) : '',
          '#size'          => 20,
        ];
      }
    }

    $form['options']['sizes']['add_sizes'] = [
      '#type'   => 'submit',
      '#value'  => $this->t('Add sizes'),
      '#name'   => 'add-sizes',
      '#submit' => [[$this, 'addSizes']],
      '#ajax'   => [
        'callback' => [$this, 'sizesCallback'],
        'wrapper'  => 'edit-sizes-wrapper',
      ],
    ];

    $num_columns = $form_state->get('num_columns') ?: count($mason->getOptions('columns'));
    if (is_null($num_columns)) {
      $num_columns = 1;
    }
    $form_state->set('num_columns', $num_columns);

    $form['options']['columns'] = [
      '#type'        => 'details',
      '#tree'        => TRUE,
      '#open'        => TRUE,
      '#title'       => $this->t('Columns'),
      '#description' => $this->t('columns are an array of break points for your columns. Think of this like media queries. start small and grow. They should be formatted as [min,max,cols].'),
      '#prefix'      => '<div id="edit-columns-wrapper" class="form-wrapper-box">',
      '#suffix'      => '</div>',
      '#attributes'  => ['class' => ['form-wrapper--mason-grid', 'form-wrapper--mason-columns']],
    ];

    for ($i = 0; $i < $num_columns; $i++) {
      if (!isset($form['options']['columns'][$i])) {
        $form['options']['columns'][$i] = [
          '#type'          => 'textfield',
          '#title'         => $this->t('#@index', ['@index' => $i]),
          '#default_value' => (NULL !== $mason->getOptions('columns', $i)) ? $mason->getOptions('columns', $i) : '',
          '#size'          => 20,
        ];
      }
    }

    $form['options']['columns']['add_columns'] = [
      '#type'   => 'submit',
      '#value'  => $this->t('Add columns'),
      '#name'   => 'add-columns',
      '#submit' => [[$this, 'addColumns']],
      '#ajax'   => [
        'callback' => [$this, 'columnsCallback'],
        'wrapper'  => 'edit-columns-wrapper',
      ],
    ];

    $num_promoted = $form_state->get('num_promoted') ?: count($mason->getOptions('promoted'));
    if (is_null($num_promoted)) {
      $num_promoted = 1;
    }
    $form_state->set('num_promoted', $num_promoted);

    $form['options']['promoted'] = [
      '#type'        => 'details',
      '#tree'        => TRUE,
      '#open'        => TRUE,
      '#title'       => $this->t('Promoted'),
      '#description' => $this->t("Use a comma separated delimeter, e.g.: box--0, 2, 1 for [CLASS-NAME, WIDTH, HEIGHT]. These items will be forced to those dimensions. This will keep the grid unchanged on refresh. Do not use quotes manually."),
      '#prefix'      => '<div id="edit-promoted-wrapper" class="form-wrapper-box">',
      '#suffix'      => '</div>',
      '#attributes'  => ['class' => ['form-wrapper--mason-grid', 'form-wrapper--mason-promoted']],
    ];

    for ($i = 0; $i < $num_promoted; $i++) {
      if (!isset($form['options']['promoted'][$i])) {
        $form['options']['promoted'][$i] = [
          '#type'          => 'textfield',
          '#title'         => $this->t('#@index', ['@index' => $i]),
          '#default_value' => (NULL !== $mason->getOptions('promoted', $i)) ? $mason->getOptions('promoted', $i) : '',
          '#size'          => 20,
        ];
      }
    }

    $form['options']['promoted']['add_promoted'] = [
      '#type'   => 'submit',
      '#value'  => $this->t('Add promoted'),
      '#name'   => 'add-promoted',
      '#submit' => [[$this, 'addPromoted']],
      '#ajax'   => [
        'callback' => [$this, 'promotedCallback'],
        'wrapper'  => 'edit-promoted-wrapper',
      ],
    ];

    $form['options']['filler'] = [
      '#type'        => 'details',
      '#tree'        => TRUE,
      '#open'        => TRUE,
      '#title'       => $this->t('Filler'),
      '#description' => $this->t('Mason works by flowing a grid of floated elements as a normal CSS layout, then measuring the dimensions of the blocks and total grid area. It then detects where gaps are and fills them. It uses fillers to fill in gaps. Fillers are elements that you can define or it will reuse elements within the grid. Do not use quotes manually.'),
      '#attributes'  => ['class' => ['form-wrapper--mason-filler']],
    ];

    $form['options']['filler']['itemSelector'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Item selector'),
      '#description'   => $this->t('This describes the elements to be used to fill in blank spaces. Use ".mason__fill" to use the module template, otherwise DIY.'),
      '#default_value' => $mason->getOptions('filler', 'itemSelector') ?: $defaults['filler']['itemSelector'],
    ];

    $form['options']['filler']['filler_class'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Filler class'),
      '#description'   => $this->t('This is a class given to filler elements within the grid, used for cleaning up if a grid set to fluid. Use "mason__filler" to match the module template classes, otherwise DIY'),
      '#default_value' => $mason->getOptions('filler', 'filler_class') ?: $defaults['filler']['filler_class'],
    ];

    $form['options']['filler']['keepDataAndEvents'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Keep data and events'),
      '#description'   => $this->t('Mason creates a clone of the filler elements before adding them to the grid, this boolean (true/false) property tells Mason to retain the events and data that have already been bound to the filler elements.'),
      '#default_value' => $mason->getOptions('filler', 'keepDataAndEvents') ?: $defaults['filler']['keepDataAndEvents'],
    ];

    $form['options']['randomFillers'] = [
      '#type'  => 'checkbox',
      '#title' => $this->t('Random fillers'),
    ];

    $excludes = ['container', 'details', 'item', 'hidden', 'submit'];
    foreach ($defaults as $name => $default) {
      if ($admin_css) {
        if ($form['options'][$name]['#type'] == 'checkbox') {
          $form['options'][$name]['#field_suffix'] = '&nbsp;';
          $form['options'][$name]['#title_display'] = 'before';
        }

        $form['options']['filler']['keepDataAndEvents']['#field_suffix'] = '&nbsp;';
        $form['options']['filler']['keepDataAndEvents']['#title_display'] = 'before';
      }

      if (in_array($form['options'][$name]['#type'], $excludes) || !isset($form['options'][$name])) {
        continue;
      }
      if (!isset($form['options'][$name]['#default_value'])) {
        $form['options'][$name]['#default_value'] = (NULL !== $mason->getOption($name)) ? $mason->getOption($name) : $default;
      }
    }

    $form['json'] = [
      '#type' => 'hidden',
      '#default_value' => $mason->getJson(),
    ];

    return $form;
  }

  /**
   * Handles adding the columns.
   */
  public function addColumns(array &$form, FormStateInterface &$form_state) {
    $num = $form_state->get('num_columns') + 1;

    $form_state->set('num_columns', $num);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Callback for adding the columns.
   */
  public function columnsCallback(array &$form, FormStateInterface &$form_state) {
    return $form['options']['columns'];
  }

  /**
   * Handles adding the promoted.
   */
  public function addPromoted(array &$form, FormStateInterface &$form_state) {
    $num = $form_state->get('num_promoted') + 1;

    $form_state->set('num_promoted', $num);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Callback for adding the promoted.
   */
  public function promotedCallback(array &$form, FormStateInterface &$form_state) {
    return $form['options']['promoted'];
  }

  /**
   * Handles adding the sizes.
   */
  public function addSizes(array &$form, FormStateInterface &$form_state) {
    $num = $form_state->get('num_sizes') + 1;

    $form_state->set('num_sizes', $num);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Callback for adding the sizes.
   */
  public function sizesCallback(array &$form, FormStateInterface &$form_state) {
    return $form['options']['sizes'];
  }

  /**
   * Convert the config into a JSON object to reduce logic at frontend.
   */
  public function jsonify($options) {
    $json     = [];
    $defaults = Mason::load('default')->getOptions();

    foreach ($defaults as $name => $items) {
      if (empty($options[$name])) {
        continue;
      }
      switch ($name) {
        case 'columns':
        case 'promoted':
        case 'sizes':
          $options[$name] = is_array($options[$name]) ? array_filter($options[$name]) : (array) $options[$name];
          if (empty($options[$name])) {
            continue;
          }

          $devider = $name == 'sizes' ? 2 : 3;
          foreach ($options[$name] as $key => $value) {
            if (strpos($value, ",") !== FALSE) {
              $value = str_replace("'", '', $value);
              $sub_items = array_map('trim', explode(",", $value, $devider));
              $sub_items = array_pad($sub_items, $devider, NULL);
              foreach ($sub_items as $k => $val) {
                $json[$name][$key][$k] = is_numeric($val) ? (int) $val : $val;
              }
            }
          }

          $json[$name] = isset($json[$name]) ? array_values($json[$name]) : '';
          break;

        case 'filler':
          foreach ($items as $key => $value) {
            if (isset($options[$name][$key])) {
              $cast = gettype($defaults[$name][$key]);
              settype($options[$name][$key], $cast);
              $json[$name][$key] = $options[$name][$key];
            }
          }
          break;

        default:
          if (isset($options[$name]) && !is_array($options[$name])) {
            $cast = gettype($defaults[$name]);
            settype($options[$name], $cast);
            $json[$name] = $options[$name];
          }
          break;
      }
    }

    return Json::encode($json);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $options = $this->entity->getOptions();
    unset($options['add_columns'], $options['add_promoted'], $options['add_sizes']);
    $form_state->setValue('json', $this->jsonify($options));

    foreach (['columns', 'promoted', 'sizes'] as $key) {
      if ($form_state->hasValue(['options', $key])) {
        $form_state->setValue(['options', $key], array_filter($form_state->getValue(['options', $key])));
      }
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    $mason = $this->entity;

    // Prevent leading and trailing spaces in mason names.
    $mason->set('label', trim($mason->label()));
    $mason->set('id', $mason->id());

    $status        = $mason->save();
    $label         = $mason->label();
    $edit_link     = $mason->link($this->t('Edit'));
    $config_prefix = $mason->getEntityType()->getConfigPrefix();
    $message       = ['@config_prefix' => $config_prefix, '%label' => $label];

    $notice = [
      '@config_prefix' => $config_prefix,
      '%label' => $label,
      'link' => $edit_link,
    ];

    if ($status == SAVED_UPDATED) {
      // If we edited an existing entity.
      // @todo #2278383.
      drupal_set_message($this->t('@config_prefix %label has been updated.', $message));
      $this->logger('mason')->notice('@config_prefix %label has been updated.', $notice);
    }
    else {
      // If we created a new entity.
      drupal_set_message($this->t('@config_prefix %label has been added.', $message));
      $this->logger('mason')->notice('@config_prefix %label has been added.', $notice);
    }

    $form_state->setRedirectUrl($mason->toUrl('collection'));
  }

}
