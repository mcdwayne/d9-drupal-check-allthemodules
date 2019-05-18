<?php

namespace Drupal\node_layout_builder\Form;

/**
 * @file
 * AddElementForm.php
 */

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node_layout_builder\Helpers\NodeLayoutBuilderHelper;
use Drupal\node_layout_builder\NodeLayoutBuilderEditor;
use Drupal\node_layout_builder\NodeLayoutBuilderStyle;
use Drupal\node_layout_builder\Services\NodeLayoutBuilderManager;

/**
 * Class AddElementForm.
 *
 * @package Drupal\node_layout_builder\Form
 */
class AddElementForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_element_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $args = $this->getFormArgs($form_state);

    $type = isset($args['type']) ? $args['type'] : NULL;
    $parent = isset($args['parent']) ? $args['parent'] : [];
    $id_element = isset($args['id_element']) ? $args['id_element'] : NULL;
    $nid = isset($args['nid']) ? $args['nid'] : NULL;
    $isUpdate = isset($args['update']) ? $args['update'] : NULL;
    $element = [];

    $form['configue'] = ['#tree' => TRUE];

    if (isset($type)) {

      $data = NodeLayoutBuilderManager::loadDataElement($nid);

      if ($isUpdate) {
        $pathresult = NodeLayoutBuilderHelper::getkeypath($data, $id_element);
        krsort($pathresult);

        $element_data = NodeLayoutBuilderHelper::getElementFromArrayData($data, $pathresult);
        $configs_element = $element_data['#data'];
        $element['#settings'] = $configs_element;
        $element['#attributes'] = $element_data['#attributes'];
        $element['#styles'] = $element_data['#styles'];
      }

      // Get Form By type.
      $form_element = NodeLayoutBuilderEditor::getFormByTypeElement($element, $type);
      if (!empty($form_element)) {
        foreach ($form_element as $key => $value) {
          $form['configue'][$key] = $value;
        }
      }

      $form['type'] = [
        '#type' => 'value',
        '#default_value' => $type,
      ];
      $form['nid'] = [
        '#type' => 'value',
        '#default_value' => $nid,
      ];
      $form['parent'] = [
        '#type' => 'value',
        '#default_value' => $parent,
      ];

    }

    $form['actions'] = [
      '#type' => 'action',
    ];
    $form['actions']['close'] = [
      '#value' => $this->t('Close'),
      '#type' => 'submit',
      '#attributes' => [
        'class' => [
          'btn',
          'btn-secondary',
          'btn-lg',
          'btn-block',
          'btn-inline',
        ],
      ],
      '#ajax' => ['callback' => '::closeModalForm'],
      '#prefix' => '<div class="row no-gutter"><div class="col-md-6">',
      '#suffix' => '</div>',
    ];
    $form['actions']['submit'] = [
      '#value' => $this->t('Save changes'),
      '#type' => 'submit',
      '#attributes' => [
        'class' => [
          'use-ajax',
          'btn',
          'btn-primary',
          'btn-lg',
          'btn-block',
          'btn-inline',
        ],
      ],
      '#ajax' => ['callback' => '::submitAjax'],
      '#prefix' => '<div class="col-md-6">',
      '#suffix' => '</div></div>',
    ];

    $form['#theme'] = 'element_settings_form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // $values = $form_state->getValues();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // $values = $form_state->getValues();
  }

  /**
   * Submit form.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   For state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function submitAjax(array &$form, FormStateInterface $form_state) {
    // Get arguments.
    $args = $this->getFormArgs($form_state);
    $values = $form_state->getValues();

    $type_element = isset($values['type']) ? $values['type'] : '';
    $parent = isset($args['parent']) ? $args['parent'] : NULL;
    $id_element = isset($args['id_element']) ? $args['id_element'] : NULL;
    $nid = $values['nid'];
    $isUpdate = isset($args['update']) ? $args['update'] : NULL;

    // Get values.
    $settings = isset($values['configue']['settings']) ? $values['configue']['settings'] : [];
    // Attributes and styles.
    $attributes = isset($values['configue']['attributes']) ? $values['configue']['attributes'] : [];
    $styles = isset($values['configue']['styles']) ? $values['configue']['styles'] : [];

    // Load data to add or update.
    $data = NodeLayoutBuilderManager::loadDataElement($nid);

    $children = '';

    // Check if is action add or update form element.
    if ($isUpdate) {

      $new_id_element = $id_element;
      $pathresult = NodeLayoutBuilderHelper::getkeypath($data, $id_element);
      krsort($pathresult);
      $path = [];
      $cur = &$path;
      foreach ($pathresult as $value) {
        $cur[$value] = [];
        $cur = &$cur[$value];
      }
      $cur = NULL;

      $item_id_string = '';
      foreach ($pathresult as $v) {
        $item_id_string .= "$v/";
      }
      $item_id_string = trim($item_id_string, '/');
      $keys = explode('/', $item_id_string);

      $temp = &$data;
      foreach ($keys as $key) {
        $temp = &$temp[$key];
      }

      // Inject diffirents data.
      $temp['#type'] = $type_element;
      $temp['#parent'] = $parent;
      $temp['#data'] = $settings;
      $temp['#attributes'] = $attributes;
      $temp['#styles'] = $styles;

      if (isset($temp['#children'])) {
        $children = NodeLayoutBuilderEditor::renderChildrenRecursive($temp['#children'], $nid);
      }

      $data_element = $data;
    }
    else {
      // Generate ID element.
      $date = date('YmdHis');
      $new_id_element = $date . uniqid();
      $parent = $id_element;

      if ($id_element == 0) {
        $data[$new_id_element] = [
          '#type' => $type_element,
          '#parent' => $parent,
          '#data' => $settings,
          '#children' => [],
          '#attributes' => $attributes,
          '#styles' => $styles,
        ];
        $data_element = $data;
      }
      else {
        $new_element = [
          '#type' => $type_element,
          '#parent' => $parent,
          '#data' => $settings,
          '#children' => [],
          '#attributes' => $attributes,
          '#styles' => $styles,
        ];
        $data_element = NodeLayoutBuilderHelper::updateDataElement($data, $id_element, $new_id_element, $new_element);
      }
    }

    // Set data of element.
    NodeLayoutBuilderHelper::setCache($nid, $data_element);

    if ($type_element == 'section') {
      $class = '';
      $tag_element = 'section';
    }
    else {
      $class = 'element ';
      $tag_element = 'div';
      if ($type_element == 'column') {
        $class .= 'col-md-' . $settings['column']['grid'] . ' ';
      }
    }

    $styles_element = NodeLayoutBuilderStyle::getStyles($styles);

    $element = [
      '#theme' => 'node_layout_builder_element',
      '#btns_actions' => NodeLayoutBuilderEditor::renderBtnActions($type_element, $nid, $new_id_element, $parent),
      '#nid' => $nid,
      '#type' => $type_element,
      '#id_element' => $new_id_element,
      '#parent' => $parent,
      '#settings' => $settings,
      '#styles' => $styles_element,
      '#content_element' => $children,
      '#editable' => 1,
      '#class' => $class,
    ];

    $prefix = '<' . $tag_element . ' class="updated ' . $class . ' ' . $type_element . ' ' . $attributes['class'] . '" id="' . $new_id_element . '" data-id="' . $new_id_element . '" data-parent="' . $parent . '" data-type="nlb_' . $type_element . '" style="' . $styles_element . '">';
    $suffix = '</' . $tag_element . '>';

    $content = $prefix . render($element) . $suffix;

    // Response.
    $response = new AjaxResponse();
    // Hide button "choose a template".
    $response->addCommand(new CssCommand('.add-templates', ['display' => 'none']));

    if ($isUpdate) {
      $response->addCommand(new ReplaceCommand('#' . $id_element, $content));
    }
    else {
      if ($id_element == 0) {
        $response->addCommand(new AppendCommand('.nlb-wrapper', $content));
      }
      else {
        if ($type_element == 'section' || $type_element == 'row') {
          $response->addCommand(new AppendCommand('#' . $id_element . ' .container-fluid:eq(0)', $content));
        }
        else {
          $response->addCommand(new AppendCommand('#' . $id_element, $content));
        }
      }
    }

    // Close Dialog.
    $response->addCommand(new CloseDialogCommand('.ui-dialog-content'));

    return $response;
  }

  /**
   * Close modal form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  public function closeModalForm() {
    $command = new CloseModalDialogCommand();
    $response = new AjaxResponse();
    $response->addCommand($command);
    return $response;
  }

  /**
   * Get arguments of form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   State form.
   *
   * @return array|mixed
   *   List arguments.
   */
  public static function getFormArgs(FormStateInterface $form_state) {
    $args = [];

    $form_build_info = $form_state->getBuildInfo();
    if (!empty($form_build_info['args'])) {
      $args = array_shift($form_build_info['args']);
    }

    return $args;
  }

}
