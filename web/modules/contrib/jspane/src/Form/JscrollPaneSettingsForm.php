<?php

namespace Drupal\jscrollpane\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\jscrollpane\Form
 */
class JscrollPaneSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'jscrollpane.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jscrollpane_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('jscrollpane.settings');
    $form['jsp_selectors'] = [
      '#type' => 'textarea',
      '#title' => $this->t('JScrollPane Selectors'),
      '#default_value' => !empty($config->get('jsp_selectors')) ? $config->get('jsp_selectors') : '',
      '#rows' => 5,
      '#attributes' => ['placeholder' => $this->t('Ex- .field--name-body')],
      '#description' => $this->t('Seperate elements with a comma. Example @selector </br>Add CSS in your Custom file. Example: @code', ['@code' => ".field-name-body {overflow:auto; \n height: 200px;}", '@selector' => '.field--name-body, #mydiv']),
    ];
    // Global Settings.
    $form['jsp_common'] = [
      '#type' => 'details',
      '#title' => $this->t('General options'),
      '#open' => TRUE,
    ];

    $form['jsp_common']['showArrows'] = [
      '#type' => 'select',
      '#title' => $this->t('Show Arrows'),
      '#default_value' => !empty($config->get('showArrows')) ? $config->get('showArrows') : 'true',
      '#options' => [
        'true' => $this->t('Yes'),
        'false' => $this->t('No'),
      ],
      '#description' => $this->t('Enable/Disable Jscrollpane arraow.'),
    ];

    $form['jsp_common']['arrowScrollOnHover'] = [
      '#type' => 'select',
      '#title' => $this->t('Scroll on mousehover'),
      '#default_value' => !empty($config->get('arrowScrollOnHover')) ? $config->get('arrowScrollOnHover') : 'false',
      '#options' => [
        'true' => $this->t('True'),
        'false' => $this->t('False'),
      ],
      '#description' => $this->t('Scroll element on Mouse Hover.'),
    ];
    $form['jsp_common']['autoReinitialise'] = [
      '#type' => 'select',
      '#title' => $this->t('Reinitialise scrollbar'),
      '#default_value' => !empty($config->get('autoReinitialise')) ? $config->get('autoReinitialise') : 'false',
      '#options' => [
        'true' => $this->t('True'),
        'false' => $this->t('False'),
      ],
      '#description' => $this->t('Auto Reinitialize Scrollbar. useful on AJAX enbled pages.'),
    ];
    $form['jsp_common']['mouseWheelSpeed'] = [
      '#type' => 'number',
      '#title' => $this->t('Mousewheel Speed'),
      '#default_value' => !empty($config->get('mouseWheelSpeed')) ? $config->get('mouseWheelSpeed') : 10,
      '#min' => 5,
      '#description' => $this->t("A multiplier which is used to control the amount that the scrollpane scrolls each time the mouse wheel is turned."),
    ];
    $form['jsp_common']['arrowButtonSpeed'] = [
      '#type' => 'number',
      '#title' => $this->t('Arrow buttons speed multiplier'),
      '#default_value' => !empty($config->get('arrowButtonSpeed')) ? $config->get('arrowButtonSpeed') : 10,
      '#min' => 5,
      '#description' => $this->t('A multiplier which is used to control the amount that the scrollpane scrolls each time on of the arrow buttons is pressed.'),
    ];

    // Verticle Bar Settings.
    $form['jsp_vertical'] = [
      '#type' => 'details',
      '#title' => $this->t('JscrollPane Vertical options'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#description' => $this->t('JscrollPane Vertical Scrollbar Options. <a href="@click" target="_blank"><em>Click here</em></a> to view the options', ['@click' => 'http://jscrollpane.kelvinluck.com/settings.html']),
    ];
    $form['jsp_vertical']['verticalGutter'] = [
      '#type' => 'number',
      '#title' => $this->t('Vertical Scrolling gap'),
      '#default_value' => !empty($config->get('verticalGutter')) ? $config->get('verticalGutter') : 5,
      '#min' => 5,
      '#description' => $this->t('Gap between the content and the scrollbar in px.'),
    ];
    $form['jsp_vertical']['verticalDragMinHeight'] = [
      '#type' => 'number',
      '#title' => $this->t('Vertical Drag min height'),
      '#default_value' => !empty($config->get('verticalDragMinHeight')) ? $config->get('verticalDragMinHeight') : 0,
      '#min' => 0,
      '#description' => $this->t('vertical drag Smallest height in px.'),
    ];
    $form['jsp_vertical']['verticalDragMaxHeight'] = [
      '#type' => 'number',
      '#title' => $this->t('Vertical Drag max height'),
      '#default_value' => !empty($config->get('verticalDragMaxHeight')) ? $config->get('verticalDragMaxHeight') : 99999,
      '#max' => 99999,
      '#description' => $this->t('Vertical drag maximum height in px.'),
    ];
    $form['jsp_vertical']['verticalDragMinWidth'] = [
      '#type' => 'number',
      '#title' => $this->t('Vertical Drag min width, in px'),
      '#default_value' => !empty($config->get('verticalDragMinWidth')) ? $config->get('verticalDragMinWidth') : 0,
      '#min' => 0,
      '#description' => $this->t('Vertical drag smallest width in px.'),
    ];
    $form['jsp_vertical']['verticalDragMaxWidth'] = [
      '#type' => 'number',
      '#title' => $this->t('Vertical Drag max width'),
      '#default_value' => !empty($config->get('verticalDragMaxWidth')) ? $config->get('verticalDragMaxWidth') : 99999,
      '#max' => 99999,
      '#description' => $this->t('Vertical drag maximum width in px.'),
    ];

    // JSP Horizontal.
    $form['jsp_horizontial'] = [
      '#type' => 'details',
      '#title' => $this->t('JscrollPane Horizontial options'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#description' => $this->t('JscrollPane Horizontal Scrollbar Options. <a href="@click" target="_blank"><em>Click here</em></a> to view the options', ['@click' => 'http://jscrollpane.kelvinluck.com/settings.html']),
    ];
    $form['jsp_horizontial']['horizontialGutter'] = [
      '#type' => 'number',
      '#title' => $this->t('Horizontial scrolling gap'),
      '#default_value' => !empty($config->get('horizontialGutter')) ? $config->get('horizontialGutter') : 5,
      '#min' => 5,
      '#description' => $this->t('Gap between the content and the scrollbar in px.'),
    ];
    $form['jsp_horizontial']['horizontialDragMinHeight'] = [
      '#type' => 'number',
      '#title' => $this->t('Horizontial Drag min height'),
      '#default_value' => !empty($config->get('horizontialDragMinHeight')) ? $config->get('horizontialDragMinHeight') : 0,
      '#min' => 0,
      '#description' => $this->t('Horizontial drag smallest height in px.'),
    ];
    $form['jsp_horizontial']['horizontialDragMaxHeight'] = [
      '#type' => 'number',
      '#title' => $this->t('Horizontial Drag max height'),
      '#default_value' => !empty($config->get('horizontialDragMaxHeight')) ? $config->get('horizontialDragMaxHeight') : 99999,
      '#max' => 99999,
      '#description' => $this->t('Horizontial drag maximum in px.'),
    ];
    $form['jsp_horizontial']['horizontialDragMinWidth'] = [
      '#type' => 'number',
      '#title' => $this->t('Horizontial Drag min width'),
      '#default_value' => !empty($config->get('horizontialDragMinWidth')) ? $config->get('horizontialDragMinWidth') : 0,
      '#min' => 0,
      '#description' => $this->t('Horizontial drag smallest width in px.'),
    ];
    $form['jsp_horizontial']['horizontialDragMaxWidth'] = [
      '#type' => 'number',
      '#title' => $this->t('Horizontial Drag max width'),
      '#default_value' => !empty($config->get('horizontialDragMaxWidth')) ? $config->get('horizontialDragMaxWidth') : 99999,
      '#max' => 99999,
      '#description' => $this->t('Horizontial drag maximum width in px.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    parent::submitForm($form, $form_state);
    // JSP Selectors.
    $this->config('jscrollpane.settings')->set('jsp_selectors', $values['jsp_selectors'])->save();
    // JSP Common.
    $this->config('jscrollpane.settings')->set('showArrows', $values['showArrows'])->save();
    $this->config('jscrollpane.settings')->set('arrowScrollOnHover', $values['arrowScrollOnHover'])->save();
    $this->config('jscrollpane.settings')->set('mouseWheelSpeed', $values['mouseWheelSpeed'])->save();
    $this->config('jscrollpane.settings')->set('arrowButtonSpeed', $values['arrowButtonSpeed'])->save();
    $this->config('jscrollpane.settings')->set('autoReinitialise', $values['autoReinitialise'])->save();
    // JSP Vertical Bar.
    $this->config('jscrollpane.settings')->set('verticalGutter', $values['verticalGutter'])->save();
    $this->config('jscrollpane.settings')->set('verticalDragMinHeight', $values['verticalDragMinHeight'])->save();
    $this->config('jscrollpane.settings')->set('verticalDragMaxHeight', $values['verticalDragMaxHeight'])->save();
    $this->config('jscrollpane.settings')->set('verticalDragMinWidth', $values['verticalDragMinWidth'])->save();
    $this->config('jscrollpane.settings')->set('verticalDragMaxWidth', $values['verticalDragMaxWidth'])->save();
    $this->config('jscrollpane.settings')->set('verticalArrowPositions', $values['verticalArrowPositions'])->save();
    // JSP Horizontal Bar.
    $this->config('jscrollpane.settings')->set('horizontialGutter', $values['horizontialGutter'])->save();
    $this->config('jscrollpane.settings')->set('horizontialDragMinHeight', $values['horizontialDragMinHeight'])->save();
    $this->config('jscrollpane.settings')->set('horizontialDragMaxHeight', $values['horizontialDragMaxHeight'])->save();
    $this->config('jscrollpane.settings')->set('horizontialDragMinWidth', $values['horizontialDragMinWidth'])->save();
    $this->config('jscrollpane.settings')->set('horizontialDragMaxWidth', $values['horizontialDragMaxWidth'])->save();
    $this->config('jscrollpane.settings')->set('horizontialArrowPositions', $values['horizontialArrowPositions'])->save();
  }

}
