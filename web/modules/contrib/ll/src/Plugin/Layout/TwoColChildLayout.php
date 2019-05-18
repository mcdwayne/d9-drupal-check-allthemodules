<?php

namespace Drupal\ll\Plugin\Layout;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Layout\LayoutDefault;

/**
 * Layout Plugin implementation for one column layout with a floating region.
 *
 * The plugin has two configurations:
 * - extra_classes_main: CSS Classes for the main region.
 * - extra_classes_child: CSS Classes for the child region.
 */
class TwoColChildLayout extends LayoutDefault implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'classes_container' => '',
      'classes_first' => 'll-parent-width-50',
      'classes_first_child' => 'll-width-25 ll-float-left',
      'classes_second' => 'll-parent-width-50',
      'classes_second_child' => 'll-width-25 ll-float-left',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();
    $form['classes_container'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Container classes'),
      '#description' => $this->t('CSS Classes. If you need to theme this use of this layout in a specific way, you can use this field to assign a CSS class that you define in your theme. The default is empty'),
      '#default_value' => $configuration['classes_container'],
    ];
    $form['classes_first'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First column classes'),
      '#description' => $this->t('CSS Classes. The following classes are defined by the module: <em>ll-parent-width-20, ll-parent-width-33, ll-parent-width-50, ll-parent-width-66, ll-parent-width-75, ll-parent-width-80</em>.<br /> The default is "<em>ll-parent-width-50</em>" and it is used when this field is empty'),
      '#default_value' => $configuration['classes_first'],
    ];
    $form['classes_first_child'] = [
      '#type' => 'textfield',
      '#title' => $this->t("First column's child region classes"),
      '#description' => $this->t('CSS Classes. The following classes are defined by the module: <em>ll-width-25, ll-width-20, ll-width-33, ll-width-50, ll-float-left, ll-float-right</em><br />The default value is "<em>ll-width-25 ll-float-left</em>" and it is used when this field is empty'),
      '#default_value' => $configuration['classes_first_child'],
    ];
    $form['classes_second'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Second column classes'),
      '#description' => $this->t('CSS Classes. The following classes are defined by the module: <em>ll-parent-width-20, ll-parent-width-33, ll-parent-width-50, ll-parent-width-66, ll-parent-width-75, ll-parent-width-80</em>.<br /> The default is "<em>ll-parent-width-50</em>" and it is used when this field is empty'),
      '#default_value' => $configuration['classes_second'],
    ];
    $form['classes_second_child'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Second column\'s child region classes"),
      '#description' => $this->t('CSS Classes. The following classes are defined by the module: <em>ll-width-25, ll-width-20, ll-width-33, ll-width-50, ll-float-left, ll-float-right</em><br />The default value is "<em>ll-width-25 ll-float-left</em>" and it is used when this field is empty'),
      '#default_value' => $configuration['classes_second_child'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['classes_container'] = Xss::filter($form_state->getValue('classes_container'));
    $this->configuration['classes_first'] = Xss::filter($form_state->getValue('classes_first'));
    $this->configuration['classes_first_child'] = Xss::filter($form_state->getValue('classes_first_child'));
    $this->configuration['classes_second'] = Xss::filter($form_state->getValue('classes_second'));
    $this->configuration['classes_second_child'] = Xss::filter($form_state->getValue('classes_second_child'));
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $default = $this->defaultConfiguration();
    if ($form_state->getValue('classes_first') === '') {
      // The child region must have a class.
      $form_state->setValue('classes_first', $default['classes_first']);
    }
    if ($form_state->getValue('classes_first_child') === '') {
      // The child region must have a class.
      $form_state->setValue('classes_first_child', $default['classes_first_child']);
    }
    if ($form_state->getValue('classes_second') === '') {
      // The child region must have a class.
      $form_state->setValue('classes_second', $default['classes_second']);
    }
    if ($form_state->getValue('classes_second_child') === '') {
      // The child region must have a class.
      $form_state->setValue('classes_second_child', $default['classes_second_child']);
    }
  }

}
