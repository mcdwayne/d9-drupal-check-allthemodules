<?php

namespace Drupal\outlayer\Form;

use Drupal\gridstack\Form\GridStackAdmin;

/**
 * Provides resusable admin functions or form elements.
 */
class OutlayerAdmin extends GridStackAdmin {

  /**
   * Returns the outlayer form elements.
   */
  public function outlayerForm(array &$form, $definition = []) {
    $form['outlayer'] = [
      '#type'        => 'select',
      '#title'       => $this->t('Outlayer optionset'),
      '#options'     => $this->blazyAdmin()->getOptionsetOptions('outlayer'),
      '#description' => $this->t("Choose an optionset to layout the grids. Be sure the relevant libraries are installed, else JS error."),
      '#enforced'    => TRUE,
      '#required'    => TRUE,
      '#weight'      => -109,
    ];

    $form['grid_custom'] = [
      '#type'        => 'textfield',
      '#title'       => $this->t('Grid custom'),
      '#description' => $this->t('Grid sizes which defines aspect ratio for large monitors, best with irregular sizes. If you need regular sizes, consider Blazy Grid instead. Use a space separated value, or WIDTHxHEIGHT pair for best results, at max 12, e.g.: <br><code>4x4 4x3 2x2 2x4 2x2 2x3 2x3 4x2 4x2</code> <br>This will resemble GridStack optionset <b>Outlayer Tagore</b>. Use a little math to have gapless grids. Affected by layout option, <a href=":url">read more</a>. E.g.: fitColumns requires equal width grid. Use custom CSS media queries to get responsive, override <b>outlayer.ungridstack.css</b>. Inputting one pair will repeat. Avoid odd numbers.', [':url' => 'http://isotope.metafizzy.co/layout-modes.html#layout-mode-options']),
      '#enforced'    => TRUE,
      '#weight'      => -39,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function mainForm(array &$form, $definition = []) {
    $definition['opening_class'] = 'form--outlayer';

    if (isset($form['optionset'])) {
      $form['optionset']['#title'] = $this->t('GridStack optionset');
      $form['optionset']['#description'] .= ' ' . $this->t("Currently supports GridStack js-driven only, not css-driven.");
    }

    $this->outlayerForm($form, $definition);

    parent::mainForm($form, $definition);
  }

  /**
   * Returns the filter form elements.
   */
  public function filterSortForm(array &$form, $definition = []) {
    $form['outlayer'] = [
      '#type'        => 'select',
      '#title'       => $this->t('Outlayer Isotope'),
      '#options'     => empty($definition['outlayers']) ? [] : $definition['outlayers'],
      '#description' => $this->t('Select an <strong>Outlayer Isotope</strong> view to associate with this display. Be sure to create one <strong>Outlayer Isotope</strong> view first if empty.'),
      '#weight'      => -70,
      '#required'    => TRUE,
    ];
  }

  /**
   * Returns the filter form elements.
   */
  public function filterForm(array &$form, $definition = []) {
    $form['filter_reset'] = [
      '#type'        => 'textfield',
      '#title'       => $this->t('Filter reset'),
      '#description' => $this->t('E.g. "Reset", "View All", "All", etc. Required to rebuild the original grid display.'),
      '#required'    => TRUE,
    ];

    $form['searchable'] = [
      '#type'        => 'textfield',
      '#title'       => $this->t('Search placeholder'),
      '#description' => $this->t('E.g. "Search", "Type to search", etc. Only title and category are searchable for now. Be sure to have them at the main Grid display. Leave empty to not use searchable.'),
    ];

    $form['search_reset'] = [
      '#type'        => 'textfield',
      '#title'       => $this->t('Search reset'),
      '#description' => $this->t('E.g. "Reset", "View All", etc. Leave empty to not use a reset.'),
    ];

    $this->filterSortForm($form, $definition);

    $form['filters'] = [
      '#type'        => 'select',
      '#options'     => empty($definition['classes']) ? [] : $definition['classes'],
      '#title'       => $this->t('Filter field'),
      '#description' => $this->t('Select field for filtering.'),
    ];
  }

  /**
   * Returns the filter form elements.
   */
  public function sorterForm(array &$form, $definition = []) {
    $form['sort_by'] = [
      '#type'        => 'textfield',
      '#title'       => $this->t('The original sort option'),
      '#description' => $this->t('E.g.: "Original order", "Reset". Leave blank for no original sort option.'),
    ];

    $this->filterSortForm($form, $definition);

    $form['sorters'] = [
      '#type'        => 'checkboxes',
      '#options'     => empty($definition['classes']) ? [] : $definition['classes'],
      '#title'       => $this->t('Sorter fields'),
      '#description' => $this->t('Select fields for sorting. Multiple fields will combine.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function gridForm(array &$form, $definition = []) {
    $this->blazyAdmin->gridForm($form, $definition);

    if (isset($form['grid'])) {
      $form['grid']['#description'] = $this->t('The amount of block grid columns for large monitors 64.063em+. <br /><strong>Requires</strong>:<ol><li>Display style.</li><li>A reasonable amount of contents.</li></ol>Leave empty to DIY, or to not build grids.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function closingForm(array &$form, $definition = []) {
    if (isset($form['background'])) {
      $form['background']['#weight'] = 100;
    }

    parent::closingForm($form, $definition);

    $admin_css = $this->manager()->configLoad('admin_css', 'blazy.settings');
    if ($admin_css) {
      $form['closing']['#attached']['library'][] = 'outlayer/admin';
    }
  }

}
