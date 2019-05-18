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
 * - classes_main: CSS Classes for the main region.
 * - classes_child: CSS Classes for the child region.
 */
class BasicChildLayout extends LayoutDefault implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'classes_container' => '',
      'classes_main' => '',
      'classes_child' => '',
      'child_width' => 'll-width-25',
      'child_float' => 'll-float-left',
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


    $form['classes_main'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Main region classes'),
      '#description' => $this->t('CSS Classes. The module does not provide any classes for the main region, and it is rendered as a normal div. The default is empty'),
      '#default_value' => $configuration['classes_main'],
    ];
    $form['child_width'] = [
      '#type' => 'select',
      '#title' => $this->t('Child region width'),
      // Other modules can alter the following list to add CSS classes.
      '#options' => [
        'll-width-20' => $this->t('20%'),
        'll-width-25' => $this->t('25%'),
        'll-width-33' => $this->t('33%'),
        'll-width-50' => $this->t('50%'),
        'll-width-auto' => $this->t('Auto'),
      ],
      '#description' => $this->t('The width of the child region, normally in % of the container.'),
      '#default_value' => $configuration['child_width'],
      '#required' => TRUE,
    ];
    $form['child_float'] = [
      '#type' => 'select',
      '#title' => $this->t('Child region floating position'),
      '#options' => [
        'll-float-left' => $this->t('Left'),
        'll-float-right' => $this->t('Right'),
      ],
      '#description' => $this->t('The floating direction of the child region.'),
      '#default_value' => $configuration['child_float'],
      '#required' => TRUE,
    ];
    $form['classes_child'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Child region classes'),
      '#description' => $this->t('Extra CSS Classes'),
      '#default_value' => $configuration['classes_child'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['classes_container'] = Xss::filter($form_state->getValue('classes_container'));
    $this->configuration['classes_main'] = Xss::filter($form_state->getValue('classes_main'));
    $this->configuration['child_width'] = Xss::filter($form_state->getValue('child_width'));
    $this->configuration['child_float'] = Xss::filter($form_state->getValue('child_float'));
    $this->configuration['classes_child'] = Xss::filter($form_state->getValue('classes_child'));
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {

  }

}
