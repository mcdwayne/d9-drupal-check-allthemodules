<?php

namespace Drupal\atm\Form;

use Drupal\atm\AtmHttpClient;
use Drupal\atm\Helper\AtmApiHelper;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\BaseCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Extension\ThemeHandler;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AtmOverallPositionAndStylingForm.
 */
class AtmOverallStylingAndPositionForm extends AtmAbstractForm {

  /**
   * AtmAbstractForm constructor.
   *
   * @param \Drupal\atm\Helper\AtmApiHelper $atmApiHelper
   *   Provides helper for ATM.
   * @param \Drupal\atm\AtmHttpClient $atmHttpClient
   *   Client for API.
   * @param \Drupal\Core\Extension\ThemeHandler $themeHandler
   *   Default theme handler.
   */
  public function __construct(AtmApiHelper $atmApiHelper, AtmHttpClient $atmHttpClient, ThemeHandler $themeHandler) {
    $this->atmApiHelper = $atmApiHelper;
    $this->atmHttpClient = $atmHttpClient;
    $this->themeHandler = $themeHandler;
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'atm-overall-styling-and-position';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['container_1'] = [
      '#type' => 'container',
    ];

    $form['container_2'] = [
      '#type' => 'container',
    ];

    $themeConfig = $this->getHelper()->getThemeConfig();

    $container1 = &$form['container_1'];
    $container2 = &$form['container_2'];

    $backgroundColor = $themeConfig->get('background-color') !== NULL ? $themeConfig->get('background-color') : $this->getHelper()->get('styles.target-cb.background-color');
    $border = $themeConfig->get('border') !== NULL ? $themeConfig->get('border') : $this->getHelper()->get('styles.target-cb.border');
    $fontFamily = $themeConfig->get('font-family') !== NULL ? $themeConfig->get('font-family') : $this->getHelper()->get('styles.target-cb.font-family');
    $boxShadow = $themeConfig->get('box-shadow') !== NULL ? $themeConfig->get('box-shadow') : $this->getHelper()->get('styles.target-cb.box-shadow');
    $fBackgroundColor = $themeConfig->get('footer-background-color') !== NULL ? $themeConfig->get('footer-background-color') : $this->getHelper()->get('styles.target-cb.footer-background-color');
    $fBorder = $themeConfig->get('footer-border') !== NULL ? $themeConfig->get('footer-border') : $this->getHelper()->get('styles.target-cb.footer-border');
    $sticky = $themeConfig->get('sticky') !== NULL ? $themeConfig->get('sticky') : $this->getHelper()->get('styles.target-cb.sticky');
    $width = $themeConfig->get('width') !== NULL ? $themeConfig->get('width') : $this->getHelper()->get('styles.target-cb.width');
    $offsetTop = $themeConfig->get('offset-top') !== NULL ? $themeConfig->get('offset-top') : $this->getHelper()->get('styles.target-cb.offset-top');
    $offsetLeft = $themeConfig->get('offset-left') !== NULL ? $themeConfig->get('offset-left') : $this->getHelper()->get('styles.target-cb.offset-left');
    $scrollingOffsetTop = $themeConfig->get('scrolling-offset-top') !== NULL ? $themeConfig->get('scrolling-offset-top') : $this->getHelper()->get('styles.target-cb.scrolling-offset-top');

    $container1['background-color'] = [
      '#type' => 'color',
      '#title' => t('Background Color'),
      '#default_value' => $backgroundColor,
      '#prefix' => '<div class="layout-column layout-column--one-sixth">',
      '#suffix' => '</div>',
    ];

    $container1['border'] = [
      '#type' => 'textfield',
      '#title' => t('Border'),
      '#default_value' => $border,
      '#prefix' => '<div class="layout-column layout-column--one-sixth">',
      '#suffix' => '</div>',
    ];

    $container1['font-family'] = [
      '#type' => 'textfield',
      '#title' => t('Font family'),
      '#default_value' => $fontFamily,
      '#prefix' => '<div class="layout-column layout-column--one-sixth">',
      '#suffix' => '</div>',
    ];

    $container1['box-shadow'] = [
      '#type' => 'textfield',
      '#title' => t('Box shadow'),
      '#default_value' => $boxShadow,
      '#prefix' => '<div class="layout-column layout-column--one-sixth">',
      '#suffix' => '</div>',
    ];

    $container1['footer-background-color'] = [
      '#type' => 'color',
      '#title' => t('Footer Background Color'),
      '#default_value' => $fBackgroundColor,
      '#prefix' => '<div class="layout-column layout-column--one-sixth">',
      '#suffix' => '</div>',
    ];

    $container1['footer-border'] = [
      '#type' => 'textfield',
      '#title' => t('Footer border'),
      '#default_value' => $fBorder,
      '#prefix' => '<div class="layout-column layout-column--one-sixth">',
      '#suffix' => '</div>',
    ];

    $container2['sticky'] = [
      '#type' => 'checkbox',
      '#title' => '<span class="onoffswitch-inner"></span><span class="onoffswitch-switch"></span>',
      '#default_value' => $sticky,
      '#attributes' => [
        'class' => ['onoffswitch-checkbox'],
      ],
      '#prefix' => '<div class="layout-column layout-column--one-sixth"><span class="onoffswitch-checkbox-label">' . $this->t('Sticky') . '</span><div class="onoffswitch">',
      '#suffix' => '</div></div>',
    ];

    $container2['width'] = [
      '#type' => 'textfield',
      '#title' => t('Width'),
      '#default_value' => $width,
      '#prefix' => '<div class="layout-column layout-column--one-sixth">',
      '#suffix' => '</div>',
      '#states' => [
        'enabled' => [
          ':input[name="sticky"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $container2['offset-top'] = [
      '#type' => 'textfield',
      '#title' => t('Offset top'),
      '#default_value' => $offsetTop,
      '#prefix' => '<div class="layout-column layout-column--one-sixth">',
      '#suffix' => '</div>',
      '#states' => [
        'enabled' => [
          ':input[name="sticky"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $container2['offset-left'] = [
      '#type' => 'textfield',
      '#title' => t('Offset left'),
      '#default_value' => $offsetLeft,
      '#prefix' => '<div class="layout-column layout-column--one-sixth">',
      '#suffix' => '</div>',
      '#states' => [
        'enabled' => [
          ':input[name="sticky"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $container2['scrolling-offset-top'] = [
      '#type' => 'textfield',
      '#title' => t('Scrolling Offset Top'),
      '#default_value' => $scrollingOffsetTop,
      '#prefix' => '<div class="layout-column layout-column--one-sixth">',
      '#suffix' => '</div>',
      '#states' => [
        'enabled' => [
          ':input[name="sticky"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['save-styles'] = [
      '#type' => 'button',
      '#value' => t('Save'),
      '#ajax' => [
        'event' => 'click',
        'callback' => [$this, 'saveParams'],
      ],
      '#attributes' => [
        'class' => ['form-item'],
      ],
      '#prefix' => '<div class="clearfix">',
      '#suffix' => '</div>',
    ];

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  public function saveParams(array &$form, FormStateInterface $form_state) {
    $themeConfig = $this->getHelper()->getThemeConfig(TRUE);

    foreach ($form_state->getValues() as $elementName => $value) {
      if (!in_array($elementName, $form_state->getCleanValueKeys())) {
        $themeConfig->set($elementName, $value);
      }
    }

    $themeConfig->save();

    $this->getAtmHttpClient()->propertyUpdateConfig();
    $this->getAtmHttpClient()->updateThemeConfig();

    $response = new AjaxResponse();

    $response->addCommand(
      new BaseCommand('showNoty', [
        'options' => [
          'type' => 'information',
          'text' => $this->t('Form data saved successfully'),
          'maxVisible' => 1,
          'timeout' => 2000,
        ],
      ])
    );

    $src = $this->getHelper()->get('build_path') . '?' . microtime();
    $response->addCommand(new ReplaceCommand('#atm-js', "<script src='$src' id='atm-js' />"));

    return $response;
  }

}
