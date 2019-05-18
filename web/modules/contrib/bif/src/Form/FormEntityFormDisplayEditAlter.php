<?php
/**
 * @file
 */

namespace Drupal\block_in_form\Form;

use Drupal\block_in_form\BlockInFormCommon;
use Drupal\block_in_form\BlockInFormUi;
use Drupal\Component\Utility\Html;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityDisplayBase;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\Core\Render\Element;
use Drupal\field_ui\Form\EntityDisplayFormBase;
use Drupal\rules\Exception\InvalidArgumentException;
use Drupal\rules\Exception\LogicException;

class FormEntityFormDisplayEditAlter {
  protected $form, $formState;

  use BlockInFormCommon;

  public function __construct(&$form, &$form_state) {
    $this->form = &$form;
    $this->formState = &$form_state;
  }

  public function alter() {
    $callback_object = $this->formState->getBuildInfo()['callback_object'];
    if (!$callback_object instanceof EntityDisplayFormBase) {
      throw new InvalidArgumentException('Unkown callback object.');
    }

    $display = $callback_object->getEntity();

    $params = $this->fieldUiFormParams($this->form, $display);
    $this->form['#blocksinform'] = array_keys($params->blocks);
    $this->form['#context'] = $display;

    $table = &$this->form['fields'];
    $this->formState_values = $this->formState->getValues();
    $bif_form_state = $this->formState->get('block_in_form');

    if ($bif_form_state == NULL) {
      $bif_form_state = $params->blocks;
    }

    $table['#parent_options'] = [];

    // Extend available parenting options.
    foreach ($bif_form_state as $name => $block) {
      $table['#parent_options'][$name] = $block->label ? $block->label : 'temporary label';
    }

    // Update existing rows accordingly to the parents.
    foreach (Element::children($table) as $name) {
      $table[$name]['parent_wrapper']['parent']['#options'] = $table['#parent_options'];
      // Inherit the value of the parent when default value is empty.
      if (empty($table[$name]['parent_wrapper']['parent']['#default_value'])) {
        $table[$name]['parent_wrapper']['parent']['#default_value'] = isset($params->parents[$name]) ? $params->parents[$name] : '';
      }
    }

    $this->formatter_options = $this->field_group_field_formatter_options($params->context);

    $refresh_rows = isset($this->formState_values['refresh_rows']) ? $this->formState_values['refresh_rows'] : (isset($this->formState->getUserInput()['refresh_rows']) ? $this->formState->getUserInput()['refresh_rows'] : NULL);
    // Create the group rows and check actions.
    foreach ($this->form['#blocksinform'] as $name) {
      $block = &$bif_form_state[$name];

      // Check the currently selected formatter, and merge persisted values for
      // formatter settings for the group.
      // This needs to be done first, so all fields are updated before creating form elements.
      if (isset($refresh_rows) && $refresh_rows == $name) {
        $settings = isset($this->formState_values['fields'][$name]) ? $this->formState_values['fields'][$name] : (isset($this->formState->getUserInput()['fields'][$name]) ? $this->formState->getUserInput()['fields'][$name] : NULL);
        if (array_key_exists('settings_edit', $settings)) {
          $block = $bif_form_state[$name];
        }
        $this->field_group_formatter_row_update($block, $settings);
      }

      // Save the group when the configuration is submitted.
      if (!empty($this->formState_values[$name . '_plugin_settings_update'])) {
        $this->blockSettingsUpdate($block, $this->formState_values['fields'][$name]);
      }
      // After all updates are finished, let the form_state know.
      $bif_form_state[$name] = $block;

      $settings = $this->blockSettingsForm($block, $this->form, $this->formState);

      $id = strtr($name, '_', '-');
      $js_rows_data[$id] = array('type' => 'group', 'name' => $name);
      // A group cannot be selected as its own parent.
      $parent_options = $table['#parent_options'];
      unset($parent_options[$name]);
      $table[$name] = array(
        '#attributes' => array('class' => array('draggable', 'field-group'), 'id' => $id),
        '#row_type' => 'block',
        '#region_callback' => $params->region_callback,
        '#js_settings' => array('rowHandler' => 'group'),
        'human_name' => array(
          '#markup' => Html::escape(t($block->label)),
          '#prefix' => '<span class="group-label">',
          '#suffix' => '</span>',
        ),
        'weight' => array(
          '#type' => 'textfield',
          '#default_value' => $block->weight,
          '#size' => 3,
          '#attributes' => array('class' => array('field-weight')),
        ),
        'parent_wrapper' => array(
          'parent' => array(
            '#type' => 'select',
            '#options' =>  $parent_options,
            '#empty_value' => '',
            '#default_value' => isset($params->parents[$name]) ? $params->parents[$name] : '',
            '#attributes' => array('class' => array('field-parent')),
            '#parents' => array('fields', $name, 'parent'),
          ),
          'hidden_name' => array(
            '#type' => 'hidden',
            '#default_value' => $name,
            '#attributes' => array('class' => array('field-name')),
          ),
        ),
      );

      // For view settings. Add a spacer cell. We can't use colspan because of the javascript .
      if ($params->context == 'view') {
        $table[$name] += array(
          'spacer' => array(
            '#markup' => '&nbsp;'
          )
        );
      }

      $table[$name] += [
        'region' => [
          '#type' => 'select',
          '#options' => ['content', 'hidden'],
          '#default_value' => 'content',
          '#attributes' => ['class' => ['field-region']],
        ],
      ];

      $base_button = array(
        '#submit' => array(
          array($this->formState->getBuildInfo()['callback_object'], 'multistepSubmit')
        ),
        '#ajax' => array(
          'callback' => array($this->formState->getBuildInfo()['callback_object'], 'multistepAjax'),
          'wrapper' => 'field-display-overview-wrapper',
          'effect' => 'fade',
        ),
        '#field_name' => $name,
      );

      if ($this->formState->get('plugin_settings_edit') == $name) {
        $table[$name]['format']['#cell_attributes'] = array('colspan' => 2);
        $table[$name]['format']['format_settings'] = array(
          '#type' => 'container',
          '#attributes' => array('class' => array('field-plugin-settings-edit-form')),
          '#parents' => array('fields', $name, 'settings_edit_form'),
          '#weight' => -5,
          'label' => array(
            '#markup' => t('Block config:') . ' <span class="formatter-name">' . $block->format_type . '</span>',
          ),
          // Create a settings form where hooks can pick in.
          'settings' => $settings,
          'actions' => array(
            '#type' => 'actions',
            'save_settings' => $base_button + array(
                '#type' => 'submit',
                '#name' => $name . '_plugin_settings_update',
                '#value' => t('Update'),
                '#op' => 'update',
              ),
            'cancel_settings' => $base_button + array(
                '#type' => 'submit',
                '#name' => $name . '_plugin_settings_cancel',
                '#value' => t('Cancel'),
                '#op' => 'cancel',
                // Do not check errors for the 'Cancel' button.
                '#limit_validation_errors' => array(),
              ),
          ),
        );
        $table[$name]['#attributes']['class'][] = 'field-formatter-settings-editing';
        $table[$name]['format']['type']['#attributes']['class'] = array('visually-hidden');
      }
      else {
        // After saving, the settings are updated here aswell. First we create
        // the element for the table cell.
        $table[$name]['settings_summary'] = $this->blockSettingsSummary($name, $block);

        // Add the configure button.
        $table[$name]['settings_edit'] = $base_button + array(
            '#type' => 'image_button',
            '#name' => $name . '_block_settings_edit',
            '#src' => 'core/misc/icons/787878/cog.svg',
            '#attributes' => array('class' => array('field-plugin-settings-edit'), 'alt' => t('Edit')),
            '#op' => 'edit',
            // Do not check errors for the 'Edit' button, but make sure we get
            // the value of the 'plugin type' select.
            '#limit_validation_errors' => array(array('fields', $name, 'type')),
            '#prefix' => '<div class="field-plugin-settings-edit-wrapper">',
            '#suffix' => '</div>',
          );

        $delete_route = BlockInFormUi::getDeleteRoute($block);

        $table[$name]['settings_edit']['#suffix'] .= \Drupal::l(t('delete'), $delete_route);
      }

      $this->formState->set('block_in_form', $bif_form_state);
    }

    // Additional row: add new group.
    $parent_options = $table['#parent_options'];

    $this->form['#attached']['library'][] = 'field_group/field_ui';

    array_unshift($this->form['actions']['submit']['#submit'], [$this, 'submitForm']);

    // Create the settings for fieldgroup as vertical tabs (merged with DS).
    //field_group_field_ui_create_vertical_tabs($this->form, $this->formState, $params);

    // Show a warning if the user has not set up required containers
    if ($this->form['#blocksinform']) {
      $parent_requirements = array(
        'accordion-item' => array(
          'parent' => 'accordion',
          'message' => 'Each Accordion item element needs to have a parent Accordion group element.',
        ),
      );

      // On display overview tabs need to be checked.
      if ($this->field_group_get_context_from_display($display) == 'view') {
        $parent_requirements['tab'] = array(
          'parent' => 'tabs',
          'message' => 'Each tab element needs to have a parent tabs group element.',
        );
      }

      foreach ($this->form['#blocksinform'] as $block_name) {
        // temporary
        break;
        $block_check = $this->field_group_load_field_group($block_name, $params->entity_type, $params->bundle, $params->context, $params->mode);
        if (isset($parent_requirements[$block_check->format_type])) {
          if (!$block_check->parent_name || $this->field_group_load_field_group($block_check->parent_name, $params->entity_type, $params->bundle, $params->context, $params->mode)->format_type != $parent_requirements[$block_check->format_type]['parent']) {
            drupal_set_message(t($parent_requirements[$block_check->format_type]['message']), 'warning', FALSE);
          }
        }
      }
    }
  } // alter end

  public function submitForm($form, $form_state) {
    $form_values = $form_state->getValue('fields');

    /**
     * @var \Drupal\Core\Entity\EntityDisplayBase $display
     */
    $display = $form['#context'];

    $entity_type = $display->get('targetEntityType');
    $bundle = $display->get('bundle');
    $mode = $display->get('mode');
    $context = field_group_get_context_from_display($display);

    // Collect children.
    $children = array_fill_keys($form['#blocksinform'], []);
    foreach ($form_values as $name => $value) {
      if (!empty($value['parent'])) {
        $children[$value['parent']][$name] = $name;
      }
    }

    // Update existing groups.
    $blocks = $this->infoBlocks($entity_type, $bundle, $context, $mode, TRUE);
    $bif_form_state = $form_state->get('block_in_form');
    if (!empty($bif_form_state)) {
      foreach ($form['#blocksinform'] as $block_name) {

        // Only save updated groups.
        if (!isset($bif_form_state[$block_name])) {
          continue;
        }

        $block = $blocks[$block_name];
        $block->label = $bif_form_state[$block_name]->label;
        $block->children = array_keys($children[$block_name]);
        $block->parent_name = $form_values[$block_name]['parent'];
        $block->weight = $form_values[$block_name]['weight'];

//        $old_format_type = $block->format_type;
//        $block->format_type = isset($form_values[$block_name]['format']['type']) ? $form_values[$block_name]['format']['type'] : 'visible';
//        if (isset($bif_form_state[$block_name]->format_settings)) {
//          $block->format_settings = $bif_form_state[$block_name]->format_settings;
//        }
//
//        // If the format type is changed, make sure we have all required format settings.
//        if ($block->format_type != $old_format_type) {
//          $default_formatter_settings = _field_group_get_default_formatter_settings($block->format_type, $context);
//          $block->format_settings += $default_formatter_settings;
//        }

        /** @var EntityFormInterface $entity_form */
        $entity_form = $form_state->getFormObject();

        /** @var EntityDisplayInterface $display */
        $display = $entity_form->getEntity();

        $this->blockInFormSave($block, $display);
      }
    }

    \Drupal::cache()->invalidate('block_in_form');
  }

  /**
   * Creates a summary for the field format configuration summary.
   * @param String $group_name The name of the group
   * @param Object $group The group object
   * @return Array ready to be rendered.
   */
  private function blockSettingsSummary($block_name, $block) {
    $summary = [
      'label: '. $block->block_settings['label'],
      'label display: '. $block->block_settings['label_display'],
      'id: ' . $block_name,
      'plugin_id: '. $block->plugin_id,
    ];

    return array(
      '#markup' => '<div class="field-plugin-summary">' . implode('<br />', $summary) . '</div>',
      '#cell_attributes' => array('class' => array('field-plugin-summary-cell')),
    );
  }

  private function field_group_formatter_row_update(& $group, $settings) {
    // if the row has changed formatter type, update the group object
    if (!empty($settings['format']['type']) && $settings['format']['type'] != $group->format_type) {
      $group->format_type = $settings['format']['type'];
      $this->blockSettingsUpdate($group, $settings);
    }
  }

  /**
   * Update handler for field_group configuration settings.
   * @param Object $group The group object
   * @param Array $settings Configuration settings
   */
  private function blockSettingsUpdate(&$block, $settings) {

    return;
    // for format changes we load the defaults.
    if (empty($settings['settings_edit_form']['settings'])) {
      $group->format_settings = _field_group_get_default_formatter_settings($group->format_type, $group->context);
    }
    else {
      $group->format_type = $settings['format']['type'];
      $group->label = $settings['settings_edit_form']['settings']['label'];
      $group->format_settings = $settings['settings_edit_form']['settings'];
    }
  }

  /**
   * @param $type
   * @return array
   */
  private function field_group_field_formatter_options($type) {
    return [];
  }

  /**
   * @param $block_name
   * @param $entity_type
   * @param $bundle
   * @param $context
   * @param $mode
   * @return mixed
   */
  private function field_group_load_field_group($block_name, $entity_type, $bundle, $context, $mode) {
    $blocks = $this->field_group_info_groups($entity_type, $bundle, $context, $mode);
    if (isset($blocks[$block_name])) {
      return $blocks[$block_name];
    }
  }

  /**
   * @param $form
   * @param \Drupal\Core\Entity\EntityDisplayBase $display
   * @return \stdClass
   */
  public function fieldUiFormParams($form, EntityDisplayBase $display) {

    $params = new \stdClass();
    $params->entity_type = $display->getTargetEntityTypeId();
    $params->bundle = $display->getTargetBundle();
    $params->region_callback = [$this, 'blockDisplayRowRegion'];
    $params->mode = $display->getMode();
    $params->context = $this->field_group_get_context_from_display($display);
    $params->blocks = $this->infoBlocks($params->entity_type, $params->bundle, $params->context, $params->mode);

    // Gather parenting data.
    $params->parents = array();
    foreach ($params->blocks as $name => $block) {
      foreach ($block->children as $child) {
        $params->parents[$child] = $name;
      }
    }

    return $params;
  }

  /**
   * Creates a form for field_group formatters.
   * @param Object $group The FieldGroup object.
   */
  private function blockSettingsForm(&$block, $form, $form_state) {
    // Create a block entity.
    $plugin_id = $block->plugin_id;
    $entity_type_id = $block->entity_type;
    $bundle = $block->bundle;
    $entity = \Drupal::entityManager()->getStorage('block')
      ->create(
        [
          'plugin' => $plugin_id,
          'entity_type_id' => $entity_type_id,
          'bundle' => $bundle
        ]
      );

    $settings_form = [];
    $subform_state = SubformState::createForSubform($settings_form, $form, $form_state);
    $settings_form = $this->getPluginForm($entity->getPlugin())->buildConfigurationForm($settings_form, $subform_state);

    return $settings_form;
  }

  protected function getPluginForm(BlockPluginInterface $block) {
    if ($block instanceof PluginWithFormsInterface) {
      $plugin_form_factory = \Drupal::service('plugin_form.factory');
      return $plugin_form_factory->createInstance($block, 'configure');
    }
    return $block;
  }

  /**
   * Helper function to get context from entity display.
   *
   * @param \Drupal\Core\Entity\EntityDisplayBase $display
   *
   * @return string
   */
  private function field_group_get_context_from_display(EntityDisplayBase $display) {
    if ($display instanceof EntityFormDisplayInterface) {
      return 'form';
    }
    elseif ($display instanceof EntityViewDisplayInterface) {
      return 'view';
    }

    throw new LogicException('Unknown display object.');
  }

  /**
   * Returns the region to which a row in the 'Manage display' screen belongs.
   * @param Array $row A field or field_group row
   * @return String the current region.
   */
  public static function blockDisplayRowRegion($row) {
    if ('block' == $row['#row_type']) {
      return $row['region']['#value'] == 'hidden' ? 'hidden' : 'content';
    }
  }
}