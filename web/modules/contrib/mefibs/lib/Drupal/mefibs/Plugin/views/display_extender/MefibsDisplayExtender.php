<?php

/**
 * @file
 * Contains \Drupal\mefibs\Plugin\views\display_extender\MefibsDisplayExtender.
 */

namespace Drupal\mefibs\Plugin\views\display_extender;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\views\Plugin\Annotation\ViewsDisplayExtender;
use Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase;

/**
 * @ingroup views_display_extender
 *
 * @ViewsDisplayExtender(
 *   id = "mefibs",
 *   title = @Translation("Mefibs"),
 *   help = @Translation("Provides additional exposed filter blocks for this view."),
 *   no_ui = FALSE
 * )
 */
class MefibsDisplayExtender extends DisplayExtenderPluginBase {
  
  /**
   * Retrieve enabled blocks.
   *
   * @return array
   *   An array with all additional blocks, keys are the machine_names and the
   * values the block titles.
   */
  public function getEnabledBlocks() {
    $blocks = array();
    $settings = $this->displayHandler->getOption('mefibs');
    if (!isset($settings['blocks']) || !count($settings['blocks'])) {
      return array();
    }
    foreach ($settings['blocks'] as $block) {
      $blocks[$block['machine_name']] = $block['name'];
    }
    return $blocks;
  }
  
  /**
   * Overrides Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase::defineOptionsAlter().
   */
  public function defineOptionsAlter(&$options) {
    $options['mefibs'] = array(
      'default' => array(
        'blocks' => array(),
      ),
      'translatable' => TRUE,
    );
    return $options;
  }
  
  public function buildOptionsForm(&$form, &$form_state) {
    if ($form_state['type'] != 'mefibs') {
      return;
    }
    $view = $form_state['view'];
    $display_id = $form_state['display_id'];

    // Add javascript needed for ajax form behaviors.
    drupal_add_js(drupal_get_path('module', 'mefibs') . '/js/mefibs_admin.js');
    drupal_add_css(drupal_get_path('module', 'mefibs') . '/css/mefibs_admin.css');

    // Get the settings.
    $settings = $this->displayHandler->getOption('mefibs');
    $blocks = $settings['blocks'];

    // Find out if we are in an edit context here.
    $edit = FALSE;
    if (isset($form_state['storage']['action']) && $form_state['storage']['action'] == 'edit') {
      $edit = $form_state['storage']['id'];
    }

    if (isset($view->form_cache['blocks'])) {
      $blocks = $view->form_cache['blocks'];
      if ($edit === FALSE && $blocks != $settings['blocks']) {
        drupal_set_message('* ' . t('All changes are stored temporarily. Click Apply to make your changes permanent. Click Cancel to discard your changes.'), 'warning', FALSE);
      }
    }

    $form['mefibs'] = array(
      '#prefix' => '<div id="mefibs-display-extender-blocks-wrapper-outer">',
      '#suffix' => '</div>',
      '#blocks' => $blocks,
      '#tree' => TRUE,
    );
    $form['#title'] .= t('Enable additional blocks for exposed elements');
    
    $form['mefibs']['description'] = array(
      '#type' => 'markup',
      '#markup' => t('Choose which blocks are available for exposed input fields for this display.'),
      '#prefix' => '<div class="description">',
      '#suffix' => '</div>',
    );
    $form['mefibs']['blocks'] = array(
      '#prefix' => '<div id="mefibs-display-extender-blocks-wrapper">',
      '#suffix' => '</div>',
      '#theme' => 'mefibs_views_ui_block_list',
      '#view' => $this->view,
    );

    for ($id = 0; $id < count($blocks); $id++) {
      if ($edit !== FALSE && $edit === $id) {
        $form['mefibs']['blocks'][$id]['name'] = array(
          '#type' => 'textfield',
          '#default_value' => $blocks[$id]['name'],
          '#size' => 12,
        );
        $form['mefibs']['blocks'][$id]['machine_name'] = array(
          '#type' => 'machine_name',
          '#machine_name' => array(
            'label' => t('Machine name'),
            'source' => array('options', 'mefibs', 'blocks', $id, 'name'),
            'exists' => 'mefibs_block_machine_name_exists',
          ),
          '#required' => FALSE,
          '#default_value' => $blocks[$id]['machine_name'],
          '#size' => 12,
        );
      }
      else {
        $form['mefibs']['blocks'][$id]['name'] = array(
          '#type' => 'markup',
          '#markup' => $blocks[$id]['name'],
        );
        $form['mefibs']['blocks'][$id]['machine_name'] = array(
          '#type' => 'markup',
          '#markup' => $blocks[$id]['machine_name'],
        );
      }

      $items = mefibs_get_expected_items_for_exposed_form_block($this->view, $blocks[$id]['machine_name']);
      $form['mefibs']['blocks'][$id]['filters'] = array(
        '#type' => 'markup',
        '#markup' => count($items['filter']),
        '#access' => $edit !== $id,
      );
      $form['mefibs']['blocks'][$id]['sorts'] = array(
        '#type' => 'markup',
        '#markup' => count($items['sort']),
        '#access' => $edit !== $id,
      );

      $actions = array(
        'edit' => t('Edit'),
        'save' => t('Save'),
        'cancel' => t('Cancel'),
        'remove' => t('Remove'),
      );
      foreach ($actions as $type => $label) {
        $form['mefibs']['blocks'][$id]['actions'][$type] = array(
          '#type' => 'submit',
          '#default_value' => $label,
          '#name' => 'edit-mefibs-block-action-' . $type . '-' . $id,
          '#group' => $id,
          '#access' => $edit === FALSE || $edit === $id,
          '#ajax' => array(
            'path' => current_path(),
          ),
        );
      }
      if ($edit !== FALSE && $edit === $id) {
        $form['mefibs']['blocks'][$id]['actions']['edit']['#prefix'] = '<div style="display: none;">';
        $form['mefibs']['blocks'][$id]['actions']['edit']['#suffix'] = '</div>';
        $form['mefibs']['blocks'][$id]['actions']['edit']['#weight'] = 100;
        $form['mefibs']['blocks'][$id]['actions']['remove']['#prefix'] = '<div style="display: none;">';
        $form['mefibs']['blocks'][$id]['actions']['remove']['#suffix'] = '</div>';
        $form['mefibs']['blocks'][$id]['actions']['remove']['#weight'] = 100;
      }
      else {
        $form['mefibs']['blocks'][$id]['actions']['save']['#prefix'] = '<div style="display: none;">';
        $form['mefibs']['blocks'][$id]['actions']['save']['#suffix'] = '</div>';
        $form['mefibs']['blocks'][$id]['actions']['save']['#weight'] = 100;
        $form['mefibs']['blocks'][$id]['actions']['cancel']['#prefix'] = '<div style="display: none;">';
        $form['mefibs']['blocks'][$id]['actions']['cancel']['#suffix'] = '</div>';
        $form['mefibs']['blocks'][$id]['actions']['cancel']['#weight'] = 100;
      }
    }

    // The "add new block".
    $form['mefibs']['add_block'] = array(
      '#type' => 'fieldset',
      '#title' => t('Add new block'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $form['mefibs']['add_block']['name'] = array(
      '#type' => 'textfield',
      '#title' => t('New block'),
      '#default_value' => '',
      '#size' => 20,
    );
    $form['mefibs']['add_block']['machine_name'] = array(
      '#type' => 'machine_name',
      '#machine_name' => array(
        'label' => t('Machine name'),
        'source' => array('options', 'mefibs', 'add_block', 'name'),
        'exists' => 'mefibs_block_machine_name_exists',
      ),
      '#required' => FALSE,
      '#default_value' => '',
    );
    $form['mefibs']['add_block']['submit'] = array(
      '#type' => 'submit',
      '#default_value' => t('Add block'),
      '#attributes' => array(
        'class' => array('mefibs-add-block'),
      ),
      // This is convenient to decide on the triggered action in
      // options_submit().
      '#parents' => array('mefibs', 'blocks', NULL, 'add'),
      '#group' => 'add',
      '#ajax' => array(
        'path' => current_path(),
      ),
    );
  }

  /**
   * Handle any special handling on the form submission.
   */
  public function validateOptionsForm(&$form, &$form_state) {
    if ($form_state['type'] != 'mefibs') {
      return;
    }
    $values = $form_state['values'];
    $add_block = $values['mefibs']['add_block'];
    $button = $form_state['triggering_element'];
    if (isset($button['#group']) && $button['#group'] === 'add') {
      // New block should be added, check for submitted values
      if (empty($add_block['name'])) {
        form_set_error('mefibs][add_block][name', t('Please enter a name for the block.'));
      }
    }
  }
  
  public function submitOptionsForm(&$form, &$form_state) {
    if ($form_state['type'] != 'mefibs') {
      return;
    }
    $values = $form_state['values'];
    $blocks = $form['mefibs']['#blocks'];
    $view = $form_state['view'];
    $display_id = $form_state['display_id'];
    $display = $view->getExecutable()->displayHandlers->get($display_id);

    // If the #group property is set on the clicked button, that means we are
    // modifying a block, not actually updating the settings.
    $button = $form_state['triggering_element'];
    if (isset($button['#group'])) {
      $action = array_pop($button['#parents']);
      array_pop($button['#parents']);
      $id = array_pop($button['#parents']);
      
      // Store the action arguments to have them accessible in
      // options_form().
      $form_state['storage']['action'] = $action;
      $form_state['storage']['id'] = $id;

      switch ($action) {
        case 'add':
          // New block to be added.
          $blocks[] = array(
            'name' => $values['mefibs']['add_block']['name'],
            'machine_name' => $values['mefibs']['add_block']['machine_name'],
          );
          break;

        case 'remove':
          // Block to be removed.
          unset($blocks[$id]);
          break;
          
        case 'edit':
          // Block to be edited.
          break;
        
        case 'save':
          // Block to be saved.
          $blocks[$id] = array(
            'name' => $form_state['input']['mefibs']['blocks'][$id]['name'],
            'machine_name' => $form_state['input']['mefibs']['blocks'][$id]['machine_name'],
          );
          break;
      }
      
      $form_state['rerender'] = TRUE;
      $form_state['rebuild'] = TRUE;
      $view->form_cache = array(
        'key' => 'display',
        'blocks' => array_values($blocks),
      );
      
    }
    else {
      // Save settings.
      $settings = array(
        'blocks' => array_values($blocks),
      );
      
      $mefibs = $display->getOption('mefibs');
      if ($mefibs) {
        $settings = $settings + $mefibs;
      }
      $display->setOption('mefibs', $settings);
    }
  }
  
  /**
   * Overrides Drupal\views\Plugin\views\display\DisplayPluginBase::optionsSummary().
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);
    if ($this->displayHandler->getOption('exposed_block')) {
      $value = t('Default');
      $blocks = $this->getEnabledBlocks();
      if (count($blocks)) {
        $value = t('Default + !count', array('!count' => count($blocks)));
      }
      $options['mefibs'] = array(
        'category' => 'exposed',
        'title' => t('Exposed form blocks'),
        'value' => $value,
        'desc' => t('Control which blocks can be used for exposed forms.'),
      );
    }
  }
  
  /**
   * Set up any variables on the view prior to execution.
   */
  public function preExecute() {
    $view = $this->view;
    $display_id = $view->current_display;
    $display = $view->displayHandlers->get($display_id);

    // Get the submitted form values
    $filters = $view->getExposedInput();

    // Get old filters from the session.
    $old_filters = array();
    if (isset($_SESSION['views'][$view->storage->id()][$display_id]['mefibs'])) {
      $old_filters = $_SESSION['views'][$view->storage->id()][$display_id]['mefibs'];
    }

    // Get enabled blocks.
    $mefibs_blocks = $display->extender['mefibs']->getEnabledBlocks();

    if (!count($mefibs_blocks)) {
      // No blocks, nothing to do.
      return;
    }
    
    // Find out which block has been submitted. In non-ajax context we should
    // have a filter with key mefibs_block_id, in ajax context though, this is
    // prefixed.
    $block_id = 'default';
    foreach ($filters as $filter => $value) {
      if (strpos($filter, 'mefibs_block_id') !== FALSE) {
        $block_id = $value;
        break;
      }
    }
    
    // Get the expected items for this block.
    $expected_items = mefibs_get_expected_items_for_exposed_form_block($view, $block_id);

    // Select the final filters we want to apply according to the submitted
    // block.
    $valid_filters = array();
    $block_id_clean = str_replace('_', '-', $block_id);
    foreach ($filters as $filter => $value) {
      if ($block_id != 'default') {
        // One of the additional forms has been used.
        if (strpos($filter, MEFIBS_VIEW_DISPLAY_PREFIX . '-' . $block_id_clean . '-') === FALSE) {
          continue;
        }
      }
      // In Ajax context the filter names will be in the form
      // 'mefibs-form-block_id-filter_name, in non-ajax context the filter
      // names are simply the original filter names. So replaceing the form
      // prefix and the block id with nothing should work in all cases.
      $filter_name = str_replace(MEFIBS_VIEW_DISPLAY_PREFIX . '-' . $block_id_clean . '-', '', $filter);
      
      // Check filters and extra stuff like items_per_page and offset.
      foreach (array('filter', 'other') as $type) {
        if (($type != 'filter' && in_array($filter_name, $expected_items[$type])) || $type == 'filter' && isset($expected_items[$type][$filter_name])) {
          // This is an expected argument.
          $valid_filters[$filter_name] = $value;
        }
      }

      // Check sort options.
      if ($filter_name == 'sort_by' && count($expected_items['sort']) && in_array($value, $expected_items['sort'])) {
        $valid_filters['sort_by'] = $value;
      }
      if ($filter_name == 'sort_order' && count($expected_items['sort'])) {
        $valid_filters['sort_order'] = $value;
      }
    }

    // Unset all old filters of expected items that should have been submitted if
    // they had been set.
    foreach ($expected_items as $type => $items) {
      foreach ($items as $key => $item) {
        if (isset($old_filters[$key])) {
          unset($old_filters[$key]);
        }
        // This is important for things like sort and items per page.
        if (isset($_GET[$key])) {
          unset($_GET[$key]);
        }
      }
    }

    // Fill in values from previous query.
    $filters = $valid_filters + $old_filters;

    // Allow other modules to alter the filter values.
    drupal_alter('mefibs_filter', $filters);

    // Pass the filters on to the view.
    $view->setExposedInput($filters);

    // And save them in the session for later reference.
    $_SESSION['views'][$view->storage->id()][$display_id]['mefibs'] = $filters;

    // Support for exposed items per page.
    if (isset($filters['items_per_page'])) {
      $view->setItemsPerPage($filters['items_per_page']);
    }
    if (isset($filters['offset'])) {
      $view->setOffset($filters['offset']);
    }
  }
  
  /**
   * Render a mefibs form.
   *
   * This is essentially a pimped version of
   * ExposedFormPluginBase::renderExposedForm().
   */
  public function renderExposedForm($block_id) {
    $js_settings = &drupal_static(__function__);
    $view = $this->view;

    // return;
    $display_id = $view->current_display;
    $display = $view->displayHandlers->get($display_id);
    
    if (!mefibs_display_is_mefibs_enabled($display)) {
      return;
    }
  
    // Our custom form prefix, used to alter the form ids.
    $form_prefix = MEFIBS_VIEW_DISPLAY_PREFIX . '-' . str_replace('_', '-', $block_id);
  
    // Add javascript needed for ajax form behaviors.
    drupal_add_js(drupal_get_path('module', 'mefibs') . '/js/mefibs.js');

    // Add necessary info to JS settings.
    if (!isset($js_settings['mefibs'])) {
      $js_settings = array(
        'mefibs' => array(
          'forms' => array(),
        ),
      );
    }
    $js_settings['mefibs']['forms'][] = array(
      'view_name' => $this->view->storage->id(),
      'view_display_id' => $display_id,
      'form_prefix' => $form_prefix,
    );
    
    drupal_add_js($js_settings, 'setting');
    
    // We need to initiate the handlers otherwhise there are a lot of warnings
    // thrown in views_exposed_form_validate() and views_exposed_form_submit().
    $this->view->initHandlers();
    
    // Deal with any exposed filters we may have, before building.
    $form_state = array(
      'view' => &$this->view,
      'display' => &$this->view->display_handler->display,
      'method' => 'get',
      'rerender' => TRUE,
      'no_redirect' => TRUE,
      'always_process' => TRUE,
      'exposed_form_override' => TRUE,  // Custom property.
      'mefibs_block_id' => $block_id,   // Custom property.
      // Needed? Useful?
      // 'build_info' => array(
      //   'args' => array(),
      //   'files' => array(),
      //   'base_form_id' => 'views_exposed_form',
      // ),
    );

    // Some types of displays (eg. attachments) may wish to use the exposed
    // filters of their parent displays instead of showing an additional
    // exposed filter form for the attachment as well as that for the parent.
    if (!$display->displaysExposed()) {
      unset($form_state['rerender']);
    }

    if (!empty($this->ajax)) {
      $form_state['ajax'] = TRUE;
    }

    $form_state['exposed_form_plugin'] = $display->getPlugin('exposed_form');
    $form = drupal_build_form('views_exposed_form', $form_state);

    // This is important! Change the form id, otherwhise strange things are
    // going to happen.
    $form['#id'] = $form['#id'] . '-' . $form_prefix;
    mefibs_set_form_id_recursive($form, MEFIBS_VIEW_DISPLAY_PREFIX . '-' . $form_prefix);

    if (!$display->displaysExposed()) {
      return array();
    }
    else {
      return $form;
    }
  }
  
  /**
   * Hide form elements that should not show up for the given block id.
   */
  function hideExposedFormItems(&$form, $block_id) {
    $elements = mefibs_get_expected_items_for_exposed_form_block($this->view, $block_id);

    $display_id = $this->view->current_display;
    $display = $this->view->displayHandlers->get($display_id);
  
    $form_keys = array();
    foreach ($form['#info'] as $key => $definition) {
      list($type, $item) = explode('-', $key);
      if (in_array($item, $elements[$type])) {
        if (!isset($form['#info']['filter-' . $item]['value'])) {
          $key = $item;
        }
        else {
          $key = $form['#info']['filter-' . $item]['value'];
        }
        $form_keys[] = $key;
        if (isset($definition['operator']) && !empty($definition['operator'])) {
          $form_keys[] = $definition['operator'];
        }
      }
    }
    
    $mefibs_options = $display->getOption('mefibs');

    if (count($elements['sort']) && $mefibs_options[$display_id]['sort_block'] == $block_id) {
      $form_keys[] = 'sort_by';
      $form_keys[] = 'sort_order';
    }

    if (count($elements['other'])) {
      $form_keys = array_merge($form_keys, $elements['other']);
    }
  
    $form_keys = array_merge($form_keys, array(
      'actions',
      'form_build_id',
      'form_id',
      'form_token',
    ));

    $context = array(
      'view' => clone $this->view,
      'display_id' => $display_id,
      'block_id' => $block_id,
      'type' => 'hide_items',
    );
    drupal_alter('mefibs_elements', $form_keys, $context);

    // Do some magic: hide all other elements.
    $prefix = '<div style="display: none;">';
    $suffix = '</div>';
    mefibs_set_form_property_recursive($form, 'prefix', $prefix, $form_keys);
    mefibs_set_form_property_recursive($form, 'suffix', $suffix, $form_keys);

    // Also hide the labels of hidden filters.
    $mefibs_options = $display->getOption('mefibs');

    foreach ($form['#info'] as $id => $info) {
      list($type, $item) = explode('-', $id);
      if (!isset($mefibs_options[$display_id][$type])) {
        $form['#info'][$id]['label'] = '';
        continue;
      }
      if ((isset($mefibs_options[$display_id][$type][$item]) && $block_id !=  $mefibs_options[$display_id][$type][$item]) || (!isset($mefibs_options[$display_id][$type][$item]) && $block_id != 'default')) {
        $form['#info'][$id]['label'] = '';
      }
    }
  }

  /**
   * Set default values for the exposed items of a block.
   */
  function setDefaultValues(&$form, $block_id) {
    $elements = mefibs_get_expected_items_for_exposed_form_block($this->view, $block_id);

    $view_name = $this->view->storage->id();
    $display_id = $this->view->current_display;

    if (!isset($_SESSION['views'][$view_name][$display_id]['mefibs'])) {
      return;
    }

    $items = array_keys($elements['filter']);
    $items = array_merge($items, $elements['other']);
    if (count($elements['sort'])) {
      $items[] = 'sort_by';
      $items[] = 'sort_order';
    }

    $current_filters = $_SESSION['views'][$view_name][$display_id]['mefibs'];
    foreach (element_children($form) as $element) {    
      if (in_array($element, $items) && isset($current_filters[$element])) {
        $form[$element]['#default_value'] = $current_filters[$element];
      }
      if (count(element_children($form[$element]))) {
        $this->setDefaultValues($form[$element], $block_id);
      }
    }
  }
  
}
