<?php

namespace Drupal\outlayer_ui\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\outlayer\OutlayerDefault;
use Drupal\outlayer\Entity\Outlayer;
use Drupal\outlayer\Form\OutlayerAdmin;
use Drupal\outlayer\OutlayerManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Extends base form for outlayer instance configuration form.
 */
class OutlayerForm extends EntityForm {

  /**
   * The outlayer admin service.
   *
   * @var \Drupal\outlayer\Form\OutlayerAdmin
   */
  protected $admin;

  /**
   * The outlayer manager service.
   *
   * @var \Drupal\outlayer\OutlayerManagerInterface
   */
  protected $manager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Constructs a OutlayerForm object.
   */
  public function __construct(Messenger $messenger, OutlayerAdmin $admin, OutlayerManagerInterface $manager) {
    $this->messenger = $messenger;
    $this->admin = $admin;
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('outlayer.admin'),
      $container->get('outlayer.manager')
    );
  }

  /**
   * Returns the outlayer admin.
   */
  public function admin() {
    return $this->admin;
  }

  /**
   * Returns the outlayer manager.
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
      $form['#title'] = $this->t('<em>Duplicate outlayer optionset</em>: @label', ['@label' => $this->entity->label()]);
      $this->entity = $this->entity->createDuplicate();
    }

    // Change page title for the edit operation.
    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('<em>Edit outlayer optionset</em>: @label', ['@label' => $this->entity->label()]);
    }

    $optionset = $this->entity;
    $tooltip   = ['class' => ['is-tooltip']];
    $options   = $optionset->getOptions() ?: [];
    $admin_css = $this->manager->configLoad('admin_css', 'blazy.settings');

    $form['#attributes']['class'][] = 'form--outlayer form--slick form--optionset has-tooltip';
    if ($admin_css) {
      $form['#attached']['library'][] = 'outlayer/admin';
    }

    $form['label'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Label'),
      '#default_value' => $optionset->label(),
      '#maxlength'     => 255,
      '#required'      => TRUE,
      '#description'   => $this->t("Label for the Outlayer optionset."),
      '#attributes'    => $tooltip,
      '#prefix'        => '<div class="form__header form__half form__half--first has-tooltip clearfix">',
    ];

    // Keep the legacy CTools ID, i.e.: name as ID.
    $form['name'] = [
      '#type'          => 'machine_name',
      '#default_value' => $optionset->id(),
      '#maxlength'     => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name'  => [
        'source' => ['label'],
        'exists' => '\Drupal\outlayer\Entity\Outlayer::load',
      ],
      '#attributes'    => $tooltip,
      '#disabled'      => !$optionset->isNew(),
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
      '#description' => $this->t('Outlayer is a jQuery plugin that helps you create a perfect grid with no gaps and no ragged edges. It Works by mapping elements in a grid, finding where gaps are and filling them in as a stone outlayer would do. Outlayer is not Outlayerry, Outlayer, Packery, Gridilicious or any of those other grid plugins. It is for creating perfect grids. Do not use quotes manually.'),
    ];

    $detected_libraries = OutlayerDefault::checkExtraLibraries();
    $layouts_extras = array_keys(OutlayerDefault::extraLayouts());
    $layouts_extras = array_combine($layouts_extras, $layouts_extras);

    $layouts_defaults = [
      'masonry',
      'fitRows',
      'vertical',
    ];

    $layouts_defaults = array_combine($layouts_defaults, $layouts_defaults);

    $form['options'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
    ];

    $layout_mode_description = $this->t("<ol><li><strong>cellsByRow</strong>: A grid layout where items are centered inside each cell. The grid is defined by columnWidth and rowHeight options.</li><li><strong>fitRows</strong>: Items are arranged into rows. Rows progress vertically. Similar to what you would expect from a layout that uses CSS floats. fitRows is ideal for items that have the same height.</li><li><strong>vertical</strong>: Items are stacked vertically. Useful for simple lists and table-like views.</li><li><strong>packery</strong>: The packery layout mode uses a bin-packing algorithm. This is a fancy way of saying 'it fills empty gaps.'</li><li><strong>horizontal</strong>: Horizontal layout modes (masonryHorizontal, fitColumns, cellsByColumn, and horizontal) need a container that has a height value. Be sure that your CSS has height set.</li><li><strong>masonry</strong>: The default layout mode. Items are arranged in a vertically cascading grid.</li><li><strong>masonryHorizontal</strong>: Horizontal version of masonry. Items are arranged in a horizontally cascading grid.</li><li><strong>fitColumns</strong>: Items are arranged into columns. Columns progress horizontally. fitColumns is ideal for items that have the same width. fitColumns does not have any options.</li><li><strong>cellsByColumn</strong>: A horizontal grid layout where items are centered inside each cell. The grid is defined by columnWidth and rowHeight options.</li></ol><a href=':url' target='_blank'>See more about layout</a>.", [':url' => '//outlayer.metafizzy.co/layout-modes.html#layout-mode-options']);

    $form['options']['layoutMode'] = [
      '#type' => 'select',
      '#options' => array_merge($layouts_defaults, $layouts_extras),
      '#title' => $this->t('Layout Mode'),
      '#description' => $this->t('If an option is <b>NOT FOUND</b>, install it first, else JS error. Please refer to installation instructions, and clear cache.'),
      '#default_value' => $options['layoutMode'],
    ];

    // Provides hint when the libraries not found.
    foreach ($layouts_extras as $key => $value) {
      if (!in_array($key, $detected_libraries)) {
        $form['options']['layoutMode']['#options'][$key] = $key . ' ' . $this->t('(NOT FOUND)');
      }
    }

    $form['options']['stagger'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Stagger'),
      '#description' => $this->t("Staggers item transitions, so items transition incrementally after one another. Set as a CSS time format, '0.03s', or as a number in milliseconds, 30."),
      '#default_value' => isset($options['stagger']) ? $options['stagger'] : '0.03s',
      '#states' => [
        'invisible' => [
          ['select[name*="[layoutMode]"]' => ['value' => 'fitColumns']],
        ],
      ],
    ];

    $form['options']['transitionDuration'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Transition duration'),
      '#description' => $this->t("Duration of the transition when items change position or appearance, set in a CSS time format. To disable all transitions, set transitionDuration: 0. Default: 0.4s"),
      '#default_value' => isset($options['transitionDuration']) ? $options['transitionDuration'] : '',
      '#states' => [
        'invisible' => [
          ['select[name*="[layoutMode]"]' => ['value' => 'fitColumns']],
        ],
      ],
    ];

    $form['options']['horizontalAlignment'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Horizontal alignment'),
      '#description' => $this->t('Aligns items horizontally. 0 will align the origin edge. 1 will align the opposite edge. 0.5 will align center. Decimal number 0 to 1.'),
      '#default_value' => $options['horizontalAlignment'],
      '#states' => [
        'invisible' => [
          ['select[name*="[layoutMode]"]' => ['value' => 'vertical']],
          ['select[name*="[layoutMode]"]' => ['value' => 'fitColumns']],
        ],
      ],
    ];

    $form['options']['verticalAlignment'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Vertical alignment'),
      '#description' => $this->t('Aligns items vertically. 0 will align the origin edge. 1 will align the opposite edge. 0.5 will align center. Decimal number 0 to 1.'),
      '#default_value' => $options['verticalAlignment'],
      '#states' => [
        'invisible' => [
          ['select[name*="[layoutMode]"]' => ['value' => 'horizontal']],
          ['select[name*="[layoutMode]"]' => ['value' => 'fitColumns']],
        ],
      ],
    ];

    $form['options']['percentPosition'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Percent position'),
      '#description' => $this->t('Sets item positions in percent values, rather than pixel values. percentPosition: true works well with percent-width items, as items will not transition their position on resize.'),
      '#default_value' => $options['percentPosition'],
      '#states' => [
        'invisible' => [
          ['select[name*="[layoutMode]"]' => ['value' => 'fitColumns']],
        ],
      ],
    ];

    $form['options']['layout'] = [
      '#type' => 'details',
      '#title' => $this->t('Layout'),
      '#open' => TRUE,
      '#states' => [
        'invisible' => [
          ['select[name*="[layoutMode]"]' => ['value' => 'fitColumns']],
        ],
      ],
      '#attributes' => ['class' => ['form-wrapper--wide']],
    ];

    $form['options']['layout']['columnWidth'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Column width'),
      '#description' => $this->t('Accepts integer, or valid CSS selector, e.g.: .outlayer__column. Aligns items to the width of a column of a horizontal grid. Unlike masonry layout, packery layout does not require columnWidth. Setting columnWidth with element sizing is recommended if you are using percentage widths. Must define its CSS rule yourself. If trouble, lLeave it empty if you define grid sizes via Outlayer Views UI, or GridStack.'),
      '#default_value' => isset($options['layout']['columnWidth']) ? $options['layout']['columnWidth'] : '',
      '#states' => [
        'invisible' => [
          ['select[name*="[layoutMode]"]' => ['value' => 'packery']],
        ],
      ],
    ];

    $form['options']['layout']['rowHeight'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Row height'),
      '#description' => $this->t('Accepts integer, or valid CSS selector, e.g.: .outlayer__row-height. Aligns items to the height of a row of a vertical grid. If set to an Element or Selector String, Outlayer will use the height of that element. Setting rowHeight with element sizing is recommended if you are using media queries. Must define its CSS rule yourself.'),
      '#default_value' => isset($options['layout']['rowHeight']) ? $options['layout']['rowHeight'] : '',
    ];

    $form['options']['layout']['gutter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Gutter'),
      '#description' => $this->t('Accepts integer, or valid CSS selector, e.g.: .outlayer__gutter. The space between item elements, both vertically and horizontally. If set to an Element or Selector String, Outlayer will use the width of that element. Must define its CSS rule yourself.'),
      '#default_value' => isset($options['layout']['gutter']) ? $options['layout']['gutter'] : '',
    ];

    $form['options']['layout']['horizontal'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Horizontal'),
      '#description' => $this->t('Arranges items horizontally instead of vertically. Set a container height for horizontal layouts.'),
      '#default_value' => isset($options['layout']['horizontal']) ? $options['layout']['horizontal'] : FALSE,
      '#states' => [
        'invisible' => [
          ['select[name*="[layoutMode]"]' => ['value' => 'packery']],
          ['select[name*="[layoutMode]"]' => ['value' => 'masonry']],
        ],
      ],
    ];

    $form['options']['layout']['horizontalOrder'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Horizontal order'),
      '#description' => $this->t('Lays out items to (mostly) maintain horizontal left-to-right order.'),
      '#default_value' => isset($options['layout']['horizontalOrder']) ? $options['layout']['horizontalOrder'] : FALSE,
      '#states' => [
        'visible' => [
          ['select[name*="[layoutMode]"]' => ['value' => 'masonry']],
        ],
      ],
    ];

    $form['options']['layout']['fitWidth'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Fit width'),
      '#description' => $this->t("Sets the width of the container to fit the available number of columns, based the size of container's parent element. When enabled, you can center the container with CSS. fitWidth does not work with element sizing and percentage width. Either columnWidth needs to be set to a fixed size, like columnWidth: 120, or items need to have a fixed size in pixels, like width: 120px. Otherwise, the container and item widths will collapse on one another."),
      '#default_value' => isset($options['layout']['fitWidth']) ? $options['layout']['fitWidth'] : FALSE,
    ];

    $form['json'] = [
      '#type' => 'hidden',
      '#default_value' => $optionset->getJson(),
    ];

    $form['layout_description'] = [
      '#type' => 'item',
      '#markup' => $layout_mode_description,
    ];

    return $form;
  }

  /**
   * Handles adding the grids.
   */
  public function addBox(array &$form, FormStateInterface &$form_state) {
    $num = $form_state->get('num_boxes') + 1;

    $form_state->set('num_boxes', $num);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Callback for adding the grids.
   */
  public function addBoxCallback(array &$form, FormStateInterface &$form_state) {
    return $form['grids']['boxes'];
  }

  /**
   * Handles removing the grids.
   */
  public function removeBox(array &$form, FormStateInterface &$form_state) {
    $num = $form_state->get('num_boxes');
    if ($num > 0) {
      $num = $num - 1;
      $form_state->set('num_boxes', $num);
    }

    $form_state->setRebuild(TRUE);
  }

  /**
   * Callback for removing the grids.
   */
  public function removeBoxCallback(array &$form, FormStateInterface &$form_state) {
    return $form['grids']['boxes'];
  }

  /**
   * Convert the config into a JSON object to reduce logic at frontend.
   *
   * @todo
   */
  public function jsonify($options) {
    $json     = [];
    $defaults = Outlayer::load('default')->getOptions();

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
              $json[$name][$key] = $value;
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

    $optionset = $this->entity;
    $options = $optionset->getOptions();

    // @todo $form_state->setValue('json', $this->jsonify($options));
    // The fitColumns does not have any options.
    if ($options['layoutMode'] == 'fitColumns') {
      $options = [];
      $options['layoutMode'] = 'fitColumns';

      $form_state->setValue(['options'], []);
      $form_state->setValue(['options', 'layoutMode'], 'fitColumns');
    }

    if ($options['layoutMode'] == 'packery') {
      $form_state->setValue(['options', 'layout', 'columnWidth'], '');
    }
    if ($options['layoutMode'] == 'masonry') {
      $form_state->unsetValue(['options', 'layout', 'horizontal']);
    }
    else {
      $form_state->unsetValue(['options', 'layout', 'horizontalOrder']);
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);

    $optionset = $this->entity;

    // Prevent leading and trailing spaces in outlayer names.
    $optionset->set('label', trim($optionset->label()));
    $optionset->set('id', $optionset->id());

    $status        = $optionset->save();
    $label         = $optionset->label();
    $edit_link     = $optionset->toLink($this->t('Edit'), 'edit-form')->toString();
    $config_prefix = $optionset->getEntityType()->getConfigPrefix();
    $message       = ['@config_prefix' => $config_prefix, '%label' => $label];

    $notice = [
      '@config_prefix' => $config_prefix,
      '%label' => $label,
      'link' => $edit_link,
    ];

    if ($status == SAVED_UPDATED) {
      // If we edited an existing entity.
      // @todo #2278383.
      $this->messenger->addMessage($this->t('@config_prefix %label has been updated.', $message));
      $this->logger('outlayer')->notice('@config_prefix %label has been updated.', $notice);
    }
    else {
      // If we created a new entity.
      $this->messenger->addMessage($this->t('@config_prefix %label has been added.', $message));
      $this->logger('outlayer')->notice('@config_prefix %label has been added.', $notice);
    }

    $form_state->setRedirectUrl($optionset->toUrl('collection'));
  }

}
