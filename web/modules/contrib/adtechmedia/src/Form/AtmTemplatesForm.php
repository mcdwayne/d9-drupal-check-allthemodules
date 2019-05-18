<?php

namespace Drupal\atm\Form;

use Drupal\atm\AtmHttpClient;
use Drupal\atm\Helper\AtmApiHelper;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\BaseCommand;
use Drupal\Core\Extension\ThemeHandler;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AtmTemplatesForm.
 */
class AtmTemplatesForm extends AtmAbstractForm {

  private $tabsGroup = 'atm_templates';

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
    return 'atm-templates';
  }

  /**
   * Return Form elements.
   *
   * @param string $component
   *   Suffix for element keys.
   * @param string $optionName
   *   Option name.
   * @param string $section
   *   Section identificator.
   * @param bool $sync
   *   Sync elements or not.
   *
   * @return array
   *   Form elements.
   */
  private function getStylesBlock($component = '', $optionName = '', $section = '', $sync = FALSE) {
    $elements = [
      'line1' => [
        '#type' => 'container',

        "{$component}--{$section}--color" => [
          '#type' => 'color',
          '#title' => $this->t('Color'),
          '#attributes' => [
            'data-style-name' => 'color',
          ],
        ],

        "{$component}--{$section}--font-size" => [
          '#type' => 'textfield',
          '#title' => $this->t('Font size'),
          '#attributes' => [
            'data-style-name' => 'font-size',
          ],
        ],

        "{$component}--{$section}--font-weight" => [
          '#type' => 'select',
          '#title' => $this->t('Font weight'),
          '#attributes' => [
            'data-style-name' => 'font-weight',
          ],
          '#options' => [
            'normal' => 'normal',
            'bold' => 'bold',
            'bolder' => 'bolder',
            'lighter' => 'lighter',
            '100' => '100',
            '200' => '200',
            '300' => '300',
            '400' => '400',
            '500' => '500',
            '600' => '600',
            '700' => '700',
            '800' => '800',
            '900' => '900',
          ],
        ],
      ],

      'line2' => [
        '#type' => 'container',

        "{$component}--{$section}--font-style" => [
          '#type' => 'select',
          '#title' => $this->t('Font style'),
          '#attributes' => [
            'data-style-name' => 'font-style',
          ],
          '#options' => [
            'normal' => 'normal',
            'italic' => 'italic',
            'oblique' => 'oblique',
            'inherit' => 'inherit',
          ],
        ],

        "{$component}--{$section}--text-transform" => [
          '#type' => 'select',
          '#title' => $this->t('Text transform'),
          '#attributes' => [
            'data-style-name' => 'text-transform',
          ],
          '#options' => [
            'none' => 'none',
            'capitalize' => 'capitalize',
            'lowercase' => 'lowercase',
            'uppercase' => 'uppercase',
            'inherit' => 'inherit',
          ],
        ],
      ],
    ];

    foreach ($elements as &$line) {
      foreach ($line as &$element) {
        if ($element != 'container') {
          $attributes = &$element['#attributes'];

          $styleName = $attributes['data-style-name'];
          $styleValue = $this->getHelper()->get("templates.{$component}.{$section}.{$styleName}");

          $element['#default_value'] = $styleValue;
          $element['#prefix'] = '<div class="layout-column layout-column--one-third">';
          $element['#suffix'] = '</div>';

          $attributes['data-component-name'] = $component;
          $attributes['data-option-name'] = $optionName;

          $attributes['class'][] = 'js-component-styles';
          $attributes['class'][] = "styles-component-{$component}";

          if ($sync) {
            $attributes['class'][] = 'js-sync-values';
            $attributes['class'][] = "js-{$component}-{$section}-{$styleName}";
            $attributes['data-class-sync'] = "js-{$component}-{$section}-{$styleName}";
          }
        }
      }
    }

    return $elements;
  }

  /**
   * Return Form elements.
   *
   * @param string $component
   *   Suffix for element keys.
   *
   * @return array
   *   Form elements.
   */
  private function getArrowStylesBlock($component) {
    $elements = [];

    $states = [
      'closing' => $this->t("Closing Arrow"),
      'opening' => $this->t("Opening Arrow"),
    ];

    foreach ($states as $state => $title) {
      $elements["{$state}-arrow"] = [
        "#type" => 'container',
      ];

      $container = &$elements["{$state}-arrow"];

      $container["{$component}--{$state}--arrow-icon"] = [
        "#type" => "textfield",
        "#title" => $title,
        '#default_value' => $this->getHelper()->get("templates.{$component}.{$state}.arrow-icon"),
        '#prefix' => '<div class="layout-column layout-column--half">',
        '#suffix' => '</div>',
      ];

      $container["{$component}--{$state}--arrow-color"] = [
        "#type" => "color",
        "#title" => $this->t("Color"),
        '#default_value' => $this->getHelper()->get("templates.{$component}.{$state}.arrow-color"),
        '#prefix' => '<div class="layout-column layout-column--half">',
        '#suffix' => '</div>',
      ];
    }

    return $elements;
  }

  /**
   * Return Form elements.
   *
   * @param string $component
   *   Suffix for element keys.
   *
   * @return array
   *   Form elements.
   */
  private function getButtonStyles($component) {
    $elements = [
      'line1' => [
        '#type' => 'container',
      ],
      'line2' => [
        '#type' => 'container',
      ],
      'line3' => [
        '#type' => 'container',
      ],
    ];

    $line1 = &$elements['line1'];
    $line2 = &$elements['line2'];
    $line3 = &$elements['line3'];

    $line1["{$component}--button--background-color"] = [
      '#type' => 'color',
      '#title' => $this->t('Background color'),
      '#attributes' => [
        'data-style-name' => 'background-color',
      ],
    ];

    $line1["{$component}--button--border"] = [
      '#type' => 'textfield',
      '#title' => $this->t('Border'),
      '#attributes' => [
        'data-style-name' => 'border',
      ],
    ];

    $line1["{$component}--button--font-size"] = [
      '#type' => 'textfield',
      '#title' => $this->t('Font size'),
      '#attributes' => [
        'data-style-name' => 'font-size',
      ],
    ];

    $line2["{$component}--button--font-weight"] = [
      '#type' => 'select',
      '#title' => $this->t('Font weight'),
      '#attributes' => [
        'data-style-name' => 'font-weight',
      ],
      '#options' => [
        'normal' => 'normal',
        'bold' => 'bold',
        'bolder' => 'bolder',
        'lighter' => 'lighter',
        '100' => '100',
        '200' => '200',
        '300' => '300',
        '400' => '400',
        '500' => '500',
        '600' => '600',
        '700' => '700',
        '800' => '800',
        '900' => '900',
      ],
    ];

    $line2["{$component}--button--border-radius"] = [
      '#type' => 'textfield',
      '#title' => $this->t('Border radius'),
      '#attributes' => [
        'data-style-name' => 'border-radius',
      ],
    ];

    $line2["{$component}--button--color"] = [
      '#type' => 'color',
      '#title' => $this->t('Color'),
      '#attributes' => [
        'data-style-name' => 'color',
      ],
    ];

    $line3["{$component}--button--font-style"] = [
      '#type' => 'select',
      '#title' => $this->t('Font style'),
      '#attributes' => [
        'data-style-name' => 'font-style',
      ],
      '#options' => [
        'normal' => 'normal',
        'italic' => 'italic',
        'oblique' => 'oblique',
        'inherit' => 'inherit',
      ],
    ];

    $line3["{$component}--button--text-transform"] = [
      '#type' => 'select',
      '#title' => $this->t('Text transform'),
      '#attributes' => [
        'data-style-name' => 'text-transform',
      ],
      '#options' => [
        'none' => 'none',
        'capitalize' => 'capitalize',
        'lowercase' => 'lowercase',
        'uppercase' => 'uppercase',
        'inherit' => 'inherit',
      ],
    ];

    foreach ($elements as &$line) {
      foreach ($line as $name => &$element) {
        if ($element != 'container') {
          $name = $this->prepareElementName($name);
          $element['#default_value'] = $this->getHelper()->get("templates.$name");
          $element['#prefix'] = '<div class="layout-column layout-column--one-third">';
          $element['#suffix'] = '</div>';

          $attributes = &$element['#attributes'];
          $attributes['data-component-name'] = $component;
          $attributes['data-option-name'] = 'button';

          /*$attributes['class'][] = 'js-component-styles';
          $attributes['class'][] = 'styles-component-' . $component;*/
        }
      }
    }

    return $elements;
  }

  /**
   * Return Form elements.
   *
   * @param string $component
   *   Suffix for element keys.
   *
   * @return array
   *   Form elements.
   */
  private function getInputStyles($component) {
    $elements = [
      'line1' => [
        '#type' => 'container',
      ],
      'line2' => [
        '#type' => 'container',
      ],
      'line3' => [
        '#type' => 'container',
      ],
    ];

    $line1 = &$elements['line1'];
    $line2 = &$elements['line2'];
    $line3 = &$elements['line3'];

    $line1["{$component}--input--background-color"] = [
      '#type' => 'color',
      '#title' => $this->t('Background color'),
      '#attributes' => [
        'data-style-name' => 'background-color',
      ],
    ];

    $line1["{$component}--input--border"] = [
      '#type' => 'textfield',
      '#title' => $this->t('Border'),
      '#attributes' => [
        'data-style-name' => 'border',
      ],
    ];

    $line1["{$component}--input--border-radius"] = [
      '#type' => 'textfield',
      '#title' => $this->t('Border radius'),
      '#attributes' => [
        'data-style-name' => 'border-radius',
      ],
    ];

    $line2["{$component}--input--font-size"] = [
      '#type' => 'textfield',
      '#title' => $this->t('Font size'),
      '#attributes' => [
        'data-style-name' => 'font-size',
      ],
    ];

    $line2["{$component}--input--font-weight"] = [
      '#type' => 'select',
      '#title' => $this->t('Font weight'),
      '#attributes' => [
        'data-style-name' => 'font-weight',
      ],
      '#options' => [
        'normal' => 'normal',
        'bold' => 'bold',
        'bolder' => 'bolder',
        'lighter' => 'lighter',
        '100' => '100',
        '200' => '200',
        '300' => '300',
        '400' => '400',
        '500' => '500',
        '600' => '600',
        '700' => '700',
        '800' => '800',
        '900' => '900',
      ],
    ];

    $line2["{$component}--input--color"] = [
      '#type' => 'color',
      '#title' => $this->t('Color'),
      '#attributes' => [
        'data-style-name' => 'color',
      ],
    ];

    $line3["{$component}--input--box-shadow"] = [
      '#type' => 'textfield',
      '#title' => $this->t('Box shadow'),
      '#attributes' => [
        'data-style-name' => 'box-shadow',
      ],
    ];

    $line3["{$component}--input--text-transform"] = [
      '#type' => 'select',
      '#title' => $this->t('Text transform'),
      '#attributes' => [
        'data-style-name' => 'text-transform',
      ],
      '#options' => [
        'none' => 'none',
        'capitalize' => 'capitalize',
        'lowercase' => 'lowercase',
        'uppercase' => 'uppercase',
        'inherit' => 'inherit',
      ],
    ];

    $line3["{$component}--input--text-align"] = [
      '#type' => 'select',
      '#title' => $this->t('Text align'),
      '#attributes' => [
        'data-style-name' => 'text-align',
      ],
      '#options' => [
        'inherit' => 'inherit',
        'center' => 'center',
        'justify' => 'justify',
        'left' => 'left',
        'right' => 'right',
      ],
    ];

    foreach ($elements as &$line) {
      foreach ($line as $name => &$element) {
        if ($element != 'container') {
          $name = $this->prepareElementName($name);
          $element['#default_value'] = $this->getHelper()->get("templates.$name");
          $element['#prefix'] = '<div class="layout-column layout-column--one-third">';
          $element['#suffix'] = '</div>';

          $attributes = &$element['#attributes'];
          $attributes['data-component-name'] = $component;
          $attributes['data-option-name'] = 'button';

          /*$attributes['class'][] = 'js-component-styles';
          $attributes['class'][] = 'styles-component-' . $component;*/
        }
      }
    }

    return $elements;
  }

  /**
   * Return Form elements.
   *
   * @param string $component
   *   Suffix for element keys.
   *
   * @return array
   *   Form elements.
   */
  private function getPriceStyles($component) {
    $elements = [
      'line1' => [
        '#type' => 'container',
      ],
      'line2' => [
        '#type' => 'container',
      ],
    ];

    $line1 = &$elements['line1'];
    $line2 = &$elements['line2'];

    $line1["{$component}--price--background-color"] = [
      '#type' => 'color',
      '#title' => $this->t('Background color'),
      '#attributes' => [
        'data-style-name' => 'background-color',
      ],
    ];

    $line1["{$component}--price--border"] = [
      '#type' => 'textfield',
      '#title' => $this->t('Border'),
      '#attributes' => [
        'data-style-name' => 'border',
      ],
    ];

    $line1["{$component}--price--border-radius"] = [
      '#type' => 'textfield',
      '#title' => $this->t('Border radius'),
      '#attributes' => [
        'data-style-name' => 'border-radius',
      ],
    ];

    $line2["{$component}--price--color"] = [
      '#type' => 'color',
      '#title' => $this->t('Color'),
      '#attributes' => [
        'data-style-name' => 'color',
      ],
    ];

    $line2["{$component}--button--font-style"] = [
      '#type' => 'select',
      '#title' => $this->t('Font style'),
      '#attributes' => [
        'data-style-name' => 'font-style',
      ],
      '#options' => [
        'normal' => 'normal',
        'italic' => 'italic',
        'oblique' => 'oblique',
        'inherit' => 'inherit',
      ],
    ];

    $line2["{$component}--price--font-weight"] = [
      '#type' => 'select',
      '#title' => $this->t('Font weight'),
      '#attributes' => [
        'data-style-name' => 'font-weight',
      ],
      '#options' => [
        'normal' => 'normal',
        'bold' => 'bold',
        'bolder' => 'bolder',
        'lighter' => 'lighter',
        '100' => '100',
        '200' => '200',
        '300' => '300',
        '400' => '400',
        '500' => '500',
        '600' => '600',
        '700' => '700',
        '800' => '800',
        '900' => '900',
      ],
    ];

    foreach ($elements as &$line) {
      foreach ($line as $name => &$element) {
        if ($element != 'container') {
          $name = $this->prepareElementName($name);
          $element['#default_value'] = $this->getHelper()->get("templates.$name");
          $element['#prefix'] = '<div class="layout-column layout-column--one-third">';
          $element['#suffix'] = '</div>';

          $attributes = &$element['#attributes'];
          $attributes['data-component-name'] = $component;
          $attributes['data-option-name'] = 'button';

          /*$attributes['class'][] = 'js-component-styles';
          $attributes['class'][] = 'styles-component-' . $component;*/
        }
      }
    }

    return $elements;
  }

  /**
   * Return Form elements.
   *
   * @param string $component
   *   Suffix for element keys.
   *
   * @return array
   *   Form elements.
   */
  private function getUserSection($component = 'auth') {
    $elements = [
      '#type' => 'details',
      '#title' => $this->t('User'),
      '#open' => FALSE,

      "{$component}--used-headline" => [
        '#type' => 'textfield',
        '#title' => $this->t('Connect Message'),
        '#placeholder' => $this->getHelper()->get("templates.{$component}.used-headline"),
        '#default_value' => $this->getHelper()->get("templates.{$component}.used-headline"),
        '#attributes' => [
          'class' => [
            'js-component-options',
            "options-component-{$component}",
            "js-sync-values",
            "js-{$component}-used-headline",
          ],
          'data-component-name' => $component,
          'data-option-name' => 'used-headline',
          "data-class-sync" => "js-{$component}-used-headline",
        ],
      ],

      "{$component}--logged-headline" => [
        '#type' => 'textfield',
        '#title' => $this->t('Disconnect Message'),
        '#placeholder' => $this->getHelper()->get("templates.{$component}.logged-headline"),
        '#default_value' => $this->getHelper()->get("templates.{$component}.logged-headline"),
        '#attributes' => [
          'class' => [
            'js-component-options',
            "options-component-{$component}",
            "js-sync-values",
            "js-{$component}-logged-headline",
          ],
          'data-component-name' => $component,
          'data-option-name' => 'logged-headline',
          "data-class-sync" => "js-{$component}-logged-headline",
        ],
      ],
      [
        $this->getStylesBlock($component, 'logged-headline used-headline', 'user', TRUE),
      ],
    ];

    return $elements;
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
    $form[$this->tabsGroup] = [
      '#type' => 'vertical_tabs',
    ];

    $form['pledge'] = array_merge(
      $this->getPledgeTemplateDetailsTab(), $this->getPledgeTemplateContentTab()
    );

    $form['pay'] = array_merge(
      $this->getPayTemplateDetailsTab(), $this->getPayTemplateContentTab()
    );

    $form['refund'] = array_merge(
      $this->getRefundTemplateDetailsTab(), $this->getRefundTemplateContentTab()
    );

    $form['other'] = array_merge(
      $this->getOtherTemplateDetailsTab(), $this->getOtherTemplateContentTab()
    );

    $form['templates[pledgeComponent]'] = [
      '#type' => 'hidden',
      "#disabled" => TRUE,
      '#attributes' => [
        'class' => ['templates-output', 'templates-pledge'],
      ],
    ];

    $form['templates[authComponent]'] = [
      '#type' => 'hidden',
      "#disabled" => TRUE,
      '#attributes' => [
        'class' => ['templates-output', 'templates-auth'],
      ],
    ];

    $form['templates[payComponent]'] = [
      '#type' => 'hidden',
      "#disabled" => TRUE,
      '#attributes' => [
        'class' => ['templates-output', 'templates-pay'],
      ],
    ];

    $form['templates[refundComponent]'] = [
      '#type' => 'hidden',
      "#disabled" => TRUE,
      '#attributes' => [
        'class' => ['templates-output', 'templates-refund'],
      ],
    ];

    $form['templates[otherComponent]'] = [
      '#type' => 'hidden',
      "#disabled" => TRUE,
      '#attributes' => [
        'class' => ['templates-output', 'templates-other'],
      ],
    ];

    $form['save-template'] = [
      '#type' => 'button',
      '#value' => t('Save'),
      '#ajax' => [
        'event' => 'click',
        'callback' => [$this, 'saveParams'],
      ],
      '#prefix' => '<div class="clearfix">',
      '#suffix' => '</div>',
    ];

    return $form;
  }

  /**
   * Get detail tab.
   *
   * @return array
   *   Form element.
   */
  private function getPledgeTemplateDetailsTab() {
    return [
      '#type' => 'details',
      '#title' => t('Pledge template'),
      '#group' => $this->tabsGroup,
    ];
  }

  /**
   * Get detail tab.
   *
   * @return array
   *   Form element.
   */
  private function getPayTemplateDetailsTab() {
    return [
      '#type' => 'details',
      '#title' => t('Pay template'),
      '#group' => $this->tabsGroup,
    ];
  }

  /**
   * Get detail tab.
   *
   * @return array
   *   Form element.
   */
  private function getRefundTemplateDetailsTab() {
    return [
      '#type' => 'details',
      '#title' => t('Refund template'),
      '#group' => $this->tabsGroup,
    ];
  }

  /**
   * Get detail tab.
   *
   * @return array
   *   Form element.
   */
  private function getOtherTemplateDetailsTab() {
    return [
      '#type' => 'details',
      '#title' => t('Other template'),
      '#group' => $this->tabsGroup,
    ];
  }

  /**
   * Get content fot tab.
   *
   * @return array
   *   Form element.
   */
  private function getPledgeTemplateContentTab() {
    $tab = [];

    $tab['pledge_preview'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['layout-column', 'layout-column--half'],
      ],
      'view' => [
        '#type' => 'markup',
        '#theme' => 'atm-pledge-template-preview',
      ],
    ];

    $tab['pledge_config'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['layout-column', 'layout-column--half', 'accordion-details'],
      ],

      'salutation' => [
        '#type' => 'details',
        '#title' => $this->t('Salutation'),
        '#open' => TRUE,

        'pledge--body-welcome' => [
          '#type' => 'textfield',
          '#title' => $this->t('Salutation'),
          '#placeholder' => $this->getHelper()->get('templates.pledge.body-welcome'),
          '#default_value' => $this->getHelper()->get('templates.pledge.body-welcome'),
          '#attributes' => [
            'class' => [
              'js-component-options',
              'options-component-pledge',
            ],
            'data-component-name' => 'pledge',
            'data-option-name' => 'body-welcome',
          ],
        ],
        [
          $this->getStylesBlock('pledge', 'body-welcome', 'salutation'),
        ],
      ],

      'message' => [
        '#type' => 'details',
        '#title' => $this->t('Message'),
        '#open' => FALSE,

        'pledge--body-msg-mp' => [
          '#type' => 'textfield',
          '#title' => $this->t('Message (Expanded View)'),
          '#placeholder' => $this->getHelper()->get('templates.pledge.body-msg-mp'),
          '#default_value' => $this->getHelper()->get('templates.pledge.body-msg-mp'),
          '#attributes' => [
            'class' => [
              'js-component-options',
              'options-component-pledge',
            ],
            'data-component-name' => 'pledge',
            'data-option-name' => 'body-msg-mp',
          ],
        ],

        'pledge--heading-headline' => [
          '#type' => 'textfield',
          '#title' => $this->t('Message (Collapsed View)'),
          '#placeholder' => $this->getHelper()->get('templates.pledge.heading-headline'),
          '#default_value' => $this->getHelper()->get('templates.pledge.heading-headline'),
          '#attributes' => [
            'class' => [
              'js-component-options',
              'options-component-pledge',
            ],
            'data-component-name' => 'pledge',
            'data-option-name' => 'heading-headline',
          ],
        ],
        [
          $this->getStylesBlock('pledge', 'body-msg-mp heading-headline', 'message'),
        ],
      ],

      'user' => [
        $this->getUserSection(),
      ],

      'button' => [
        '#type' => 'details',
        '#title' => $this->t('Button'),
        '#open' => FALSE,

        'pledge--button-text' => [
          '#type' => 'textfield',
          '#title' => $this->t('Micropayments Button Text'),
          '#placeholder' => $this->getHelper()->get('templates.pledge.button-text'),
          '#default_value' => $this->getHelper()->get('templates.pledge.button-text'),
          '#attributes' => [
            'class' => [
              /*'js-component-options',
              'options-component-pledge',*/
            ],
            'data-component-name' => 'pledge',
            'data-option-name' => 'button-text',
          ],
          '#prefix' => "<div class='layout-column layout-column--half'>",
          '#suffix' => "</div>",
        ],

        'pledge--button-icon' => [
          '#type' => 'textfield',
          '#title' => $this->t('Micropayments Button Icon'),
          '#placeholder' => $this->getHelper()->get('templates.pledge.button-icon'),
          '#default_value' => $this->getHelper()->get('templates.pledge.button-icon'),
          '#attributes' => [
            'class' => [
              /*'js-component-options',
              'options-component-pledge',*/
            ],
            'data-component-name' => 'pledge',
            'data-option-name' => 'button-icon',
          ],
          '#prefix' => "<div class='layout-column layout-column--half'>",
          '#suffix' => "</div>",
        ],
        [
          $this->getButtonStyles('pledge'),
        ],
      ],

      'arrow' => [
        '#type' => 'details',
        '#title' => $this->t('Arrow'),
        '#open' => FALSE,
        [
          $this->getArrowStylesBlock('pledge'),
        ],
      ],
    ];

    return $tab;
  }

  /**
   * Get content fot tab.
   *
   * @return array
   *   Form element.
   */
  private function getPayTemplateContentTab() {
    $tab = [];

    $tab['pay_preview'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['layout-column', 'layout-column--half'],
      ],
      'view' => [
        '#type' => 'markup',
        '#theme' => 'atm-pay-template-preview',
      ],
    ];

    $tab['pay_config'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['layout-column', 'layout-column--half', 'accordion-details'],
      ],

      'salutation' => [
        '#type' => 'details',
        '#title' => $this->t('Salutation'),
        '#open' => TRUE,

        'pay--body-salutation' => [
          '#type' => 'textfield',
          '#title' => $this->t('Salutation'),
          '#placeholder' => $this->getHelper()->get('templates.pay.body-salutation'),
          '#default_value' => $this->getHelper()->get('templates.pay.body-salutation'),
          '#attributes' => [
            'class' => [
              'js-component-options',
              'options-component-pay',
            ],
            'data-component-name' => 'pay',
            'data-option-name' => 'body-salutation',
          ],
        ],
        [
          $this->getStylesBlock('pay', 'body-salutation', 'salutation'),
        ],
      ],

      'message' => [
        '#type' => 'details',
        '#title' => $this->t('Message'),
        '#open' => FALSE,

        'pay--body-msg-mp' => [
          '#type' => 'textfield',
          '#title' => $this->t('Message (Expanded View)'),
          '#placeholder' => $this->getHelper()->get('templates.pay.body-msg-mp'),
          '#default_value' => $this->getHelper()->get('templates.pay.body-msg-mp'),
          '#attributes' => [
            'class' => [
              'js-component-options',
              'options-component-pay',
            ],
            'data-component-name' => 'pay',
            'data-option-name' => 'body-msg-mp',
          ],
        ],

        'pay--heading-headline-setup' => [
          '#type' => 'textfield',
          '#title' => $this->t('Message (Collapsed View)'),
          '#placeholder' => $this->getHelper()->get('templates.pay.heading-headline-setup'),
          '#default_value' => $this->getHelper()->get('templates.pay.heading-headline-setup'),
          '#attributes' => [
            'class' => [
              'js-component-options',
              'options-component-pay',
            ],
            'data-component-name' => 'pay',
            'data-option-name' => 'heading-headline-setup',
          ],
        ],
        [
          $this->getStylesBlock('pay', 'body-msg-mp heading-headline-setup', 'message'),
        ],
      ],

      'user' => [
        $this->getUserSection(),
      ],

      'input' => [
        '#type' => 'details',
        '#title' => $this->t('Input'),
        '#open' => FALSE,
        [
          $this->getInputStyles('pay'),
        ],
      ],

      'button' => [
        '#type' => 'details',
        '#title' => $this->t('Button'),
        '#open' => FALSE,

        'pay--button-text' => [
          '#type' => 'textfield',
          '#title' => $this->t('Pay Button Text'),
          '#placeholder' => $this->getHelper()->get('templates.pay.button-text'),
          '#default_value' => $this->getHelper()->get('templates.pay.button-text'),
          '#attributes' => [
            'class' => [
              /*'js-component-options',
              'options-component-pledge',*/
            ],
            'data-component-name' => 'pay',
            'data-option-name' => 'button-text',
          ],
          '#prefix' => "<div class='layout-column layout-column--half'>",
          '#suffix' => "</div>",
        ],

        'pay--button-icon' => [
          '#type' => 'textfield',
          '#title' => $this->t('Pay Button Icon'),
          '#placeholder' => $this->getHelper()->get('templates.pay.button-icon'),
          '#default_value' => $this->getHelper()->get('templates.pay.button-icon'),
          '#attributes' => [
            'class' => [
              /*'js-component-options',
              'options-component-pledge',*/
            ],
            'data-component-name' => 'pay',
            'data-option-name' => 'button-icon',
          ],
          '#prefix' => "<div class='layout-column layout-column--half'>",
          '#suffix' => "</div>",
        ],
        'pay--setup-button-text' => [
          '#type' => 'textfield',
          '#title' => $this->t('Setup Button Text'),
          '#placeholder' => $this->getHelper()->get('templates.pay.setup-button-text'),
          '#default_value' => $this->getHelper()->get('templates.pay.setup-button-text'),
          '#attributes' => [
            'class' => [
              /*'js-component-options',
              'options-component-pledge',*/
            ],
            'data-component-name' => 'pay',
            'data-option-name' => 'setup-button-text',
          ],
          '#prefix' => "<div class='layout-column layout-column--half'>",
          '#suffix' => "</div>",
        ],

        'pay--setup-button-icon' => [
          '#type' => 'textfield',
          '#title' => $this->t('Setup Button Icon'),
          '#placeholder' => $this->getHelper()->get('templates.pay.setup-button-icon'),
          '#default_value' => $this->getHelper()->get('templates.pay.setup-button-icon'),
          '#attributes' => [
            'class' => [
              /*'js-component-options',
              'options-component-pledge',*/
            ],
            'data-component-name' => 'pay',
            'data-option-name' => 'setup-button-icon',
          ],
          '#prefix' => "<div class='layout-column layout-column--half'>",
          '#suffix' => "</div>",
        ],
        [
          $this->getButtonStyles('pay'),
        ],
      ],

      'arrow' => [
        '#type' => 'details',
        '#title' => $this->t('Arrow'),
        '#open' => FALSE,
        [
          $this->getArrowStylesBlock('pay'),
        ],
      ],
    ];

    return $tab;
  }

  /**
   * Get content fot tab.
   *
   * @return array
   *   Form element.
   */
  private function getRefundTemplateContentTab() {
    $tab = [];

    $tab['refund_preview'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['layout-column', 'layout-column--half'],
      ],
      'view' => [
        '#type' => 'markup',
        '#theme' => 'atm-refund-template-preview',
      ],
    ];

    $tab['refund_config'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['layout-column', 'layout-column--half', 'accordion-details'],
      ],

      'message' => [
        '#type' => 'details',
        '#title' => $this->t('Message'),
        '#open' => TRUE,

        'refund--body-msg' => [
          '#type' => 'textfield',
          '#title' => $this->t('Message (Expanded View)'),
          '#placeholder' => $this->getHelper()->get('templates.refund.body-msg'),
          '#default_value' => $this->getHelper()->get('templates.refund.body-msg'),
          '#attributes' => [
            'class' => [
              'js-component-options',
              'options-component-refund',
            ],
            'data-component-name' => 'refund',
            'data-option-name' => 'body-msg',
          ],
        ],

        'refund--heading-headline' => [
          '#type' => 'textfield',
          '#title' => $this->t('Message (Collapsed View)'),
          '#placeholder' => $this->getHelper()->get('templates.refund.heading-headline'),
          '#default_value' => $this->getHelper()->get('templates.refund.heading-headline'),
          '#attributes' => [
            'class' => [
              'js-component-options',
              'options-component-refund',
            ],
            'data-component-name' => 'refund',
            'data-option-name' => 'heading-headline',
          ],
        ],
        [
          $this->getStylesBlock('refund', 'body-msg heading-headline', 'message'),
        ],
      ],

      'mood' => [
        '#type' => 'details',
        '#title' => $this->t('Mood'),
        '#open' => FALSE,

        'refund--body-feeling' => [
          '#type' => 'textfield',
          '#title' => $this->t('Message'),
          '#placeholder' => $this->getHelper()->get('templates.refund.body-feeling'),
          '#default_value' => $this->getHelper()->get('templates.refund.body-feeling'),
          '#attributes' => [
            'class' => [
              'js-component-options',
              'options-component-refund',
            ],
            'data-component-name' => 'refund',
            'data-option-name' => 'body-feeling',
          ],
        ],
        [
          $this->getStylesBlock('refund', 'body-feeling', 'mood'),
        ],
        [
          'body-feeling-1' => [
            '#type' => 'container',
            '#attributes' => ['class' => ['clearfix']],

            'refund--body-feeling-happy--text' => [
              '#type' => 'textfield',
              '#title' => $this->t('Happy Mood Text'),
              '#placeholder' => $this->getHelper()->get('templates.refund.body-feeling-happy.text'),
              '#default_value' => $this->getHelper()->get('templates.refund.body-feeling-happy.text'),
              '#prefix' => '<div class="layout-column layout-column--half">',
              '#suffix' => '</div>',
              '#attributes' => [
                'class' => [
                  'js-component-options',
                  'options-component-refund',
                ],
                'data-component-name' => 'refund',
                'data-option-name' => 'body-feeling-happy',
              ],
            ],

            'refund--body-feeling-happy--color' => [
              '#type' => 'color',
              '#title' => $this->t('Happy Mood Color'),
              '#placeholder' => $this->getHelper()->get('templates.refund.body-feeling-happy.color'),
              '#default_value' => $this->getHelper()->get('templates.refund.body-feeling-happy.color'),
              '#prefix' => '<div class="layout-column layout-column--half">',
              '#suffix' => '</div>',
              '#attributes' => [
                'class' => [
                  'js-component-styles',
                  'styles-component-refund',
                ],
                'data-style-name' => 'color',
                'data-component-name' => 'refund',
                'data-option-name' => 'body-feeling-happy',
              ],
            ],
          ],

          'body-feeling-2' => [
            '#type' => 'container',
            '#attributes' => ['class' => ['clearfix']],

            'refund--body-feeling-not-happy--text' => [
              '#type' => 'textfield',
              '#title' => $this->t('Not happy Mood Text'),
              '#placeholder' => $this->getHelper()->get('templates.refund.body-feeling-not-happy.text'),
              '#default_value' => $this->getHelper()->get('templates.refund.body-feeling-not-happy.text'),
              '#prefix' => '<div class="layout-column layout-column--half">',
              '#suffix' => '</div>',
              '#attributes' => [
                'class' => [
                  'js-component-options',
                  'options-component-refund',
                ],
                'data-component-name' => 'refund',
                'data-option-name' => 'body-feeling-not-happy',
              ],
            ],

            'refund--body-feeling-not-happy--color' => [
              '#type' => 'color',
              '#title' => $this->t('Not happy Mood Color'),
              '#placeholder' => $this->getHelper()->get('templates.refund.body-feeling-not-happy.color'),
              '#default_value' => $this->getHelper()->get('templates.refund.body-feeling-not-happy.color'),
              '#prefix' => '<div class="layout-column layout-column--half">',
              '#suffix' => '</div>',
              '#attributes' => [
                'class' => [
                  'js-component-styles',
                  'styles-component-refund',
                ],
                'data-style-name' => 'color',
                'data-component-name' => 'refund',
                'data-option-name' => 'body-feeling-not-happy',
              ],
            ],
          ],
          'body-feeling-3' => [
            '#type' => 'container',
            '#attributes' => ['class' => ['clearfix']],

            'refund--body-feeling-ok--text' => [
              '#type' => 'textfield',
              '#title' => $this->t('Neutral Mood Text'),
              '#placeholder' => $this->getHelper()->get('templates.refund.body-feeling-ok.text'),
              '#default_value' => $this->getHelper()->get('templates.refund.body-feeling-ok.text'),
              '#prefix' => '<div class="layout-column layout-column--half">',
              '#suffix' => '</div>',
              '#attributes' => [
                'class' => [
                  'js-component-options',
                  'options-component-refund',
                ],
                'data-component-name' => 'refund',
                'data-option-name' => 'body-feeling-ok',
              ],
            ],

            'refund--body-feeling-ok--color' => [
              '#type' => 'color',
              '#title' => $this->t('Neutral Mood Color'),
              '#placeholder' => $this->getHelper()->get('templates.refund.body-feeling-ok.color'),
              '#default_value' => $this->getHelper()->get('templates.refund.body-feeling-ok.color'),
              '#prefix' => '<div class="layout-column layout-column--half">',
              '#suffix' => '</div>',
              '#attributes' => [
                'class' => [
                  'js-component-styles',
                  'styles-component-refund',
                ],
                'data-style-name' => 'color',
                'data-component-name' => 'refund',
                'data-option-name' => 'body-feeling-ok',
              ],
            ],
          ],
        ],
      ],

      'share' => [
        '#type' => 'details',
        '#title' => $this->t('Share'),
        '#open' => FALSE,

        'refund--body-share-experience' => [
          '#type' => 'textfield',
          '#title' => $this->t('Message'),
          '#placeholder' => $this->getHelper()->get('templates.refund.body-share-experience'),
          '#default_value' => $this->getHelper()->get('templates.refund.body-share-experience'),
          '#attributes' => [
            'class' => [
              'js-component-options',
              'options-component-refund',
            ],
            'data-component-name' => 'refund',
            'data-option-name' => 'body-share-experience',
          ],
        ],
        [
          $this->getStylesBlock('refund', 'body-share-experience', 'share'),
        ],

        'body-feeling-3' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['clearfix']],
        ],
        [
          'share-tool' => [
            '#type' => 'container',
            '#attributes' => ['class' => ['clearfix']],

            'refund--share-tool--0--icon' => [
              '#type' => 'textfield',
              '#title' => $this->t('Share Tool'),
              '#placeholder' => $this->getHelper()->get('templates.refund.share-tool.0.icon'),
              '#default_value' => $this->getHelper()->get('templates.refund.share-tool.0.icon'),
              '#prefix' => '<div class="layout-column layout-column--half">',
              '#suffix' => '</div>',
              '#attributes' => [
                'class' => [
                  /*'js-component-options',
                  'options-component-refund',*/
                ],
                'data-component-name' => 'refund',
                'data-option-name' => 'share-tool',
              ],
            ],

            'refund--share-tool--0--color' => [
              '#type' => 'color',
              '#title' => $this->t('Share Tool Color'),
              '#placeholder' => $this->getHelper()->get('templates.refund.share-tool.0.color'),
              '#default_value' => $this->getHelper()->get('templates.refund.share-tool.0.color'),
              '#prefix' => '<div class="layout-column layout-column--half">',
              '#suffix' => '</div>',
              '#attributes' => [
                'class' => [
                  /*'js-component-styles',
                  'styles-component-refund',*/
                ],
                'data-style-name' => 'color',
                'data-component-name' => 'refund',
                'data-option-name' => 'share-tool',
              ],
            ],
          ],
        ],
        [
          'share-tool' => [
            '#type' => 'container',
            '#attributes' => ['class' => ['clearfix']],

            'refund--share-tool--1--icon' => [
              '#type' => 'textfield',
              '#title' => $this->t('Share Tool'),
              '#placeholder' => $this->getHelper()->get('templates.refund.share-tool.1.icon'),
              '#default_value' => $this->getHelper()->get('templates.refund.share-tool.1.icon'),
              '#prefix' => '<div class="layout-column layout-column--half">',
              '#suffix' => '</div>',
              '#attributes' => [
                'class' => [
                 /* 'js-component-options',
                  'options-component-refund',*/
                ],
                'data-component-name' => 'refund',
                'data-option-name' => 'share-tool',
              ],
            ],

            'refund--share-tool--1--color' => [
              '#type' => 'color',
              '#title' => $this->t('Share Tool Color'),
              '#placeholder' => $this->getHelper()->get('templates.refund.share-tool.1.color'),
              '#default_value' => $this->getHelper()->get('templates.refund.share-tool.1.color'),
              '#prefix' => '<div class="layout-column layout-column--half">',
              '#suffix' => '</div>',
              '#attributes' => [
                'class' => [
                  /*'js-component-styles',
                  'styles-component-refund',*/
                ],
                'data-style-name' => 'color',
                'data-component-name' => 'refund',
                'data-option-name' => 'share-tool',
              ],
            ],
          ],
        ],
        [
          'share-tool' => [
            '#type' => 'container',
            '#attributes' => ['class' => ['clearfix']],

            'refund--share-tool--2--icon' => [
              '#type' => 'textfield',
              '#title' => $this->t('Share Tool'),
              '#placeholder' => $this->getHelper()->get('templates.refund.share-tool.2.icon'),
              '#default_value' => $this->getHelper()->get('templates.refund.share-tool.2.icon'),
              '#prefix' => '<div class="layout-column layout-column--half">',
              '#suffix' => '</div>',
              '#attributes' => [
                'class' => [
                  /*'js-component-options',
                  'options-component-refund',*/
                ],
                'data-component-name' => 'refund',
                'data-option-name' => 'share-tool',
              ],
            ],

            'refund--share-tool--2--color' => [
              '#type' => 'color',
              '#title' => $this->t('Share Tool Color'),
              '#placeholder' => $this->getHelper()->get('templates.refund.share-tool.2.color'),
              '#default_value' => $this->getHelper()->get('templates.refund.share-tool.2.color'),
              '#prefix' => '<div class="layout-column layout-column--half">',
              '#suffix' => '</div>',
              '#attributes' => [
                'class' => [
                  /*'js-component-styles',
                  'styles-component-refund',*/
                ],
                'data-style-name' => 'color',
                'data-component-name' => 'refund',
                'data-option-name' => 'share-tool',
              ],
            ],
          ],
        ],
        [
          'share-tool' => [
            '#type' => 'container',
            '#attributes' => ['class' => ['clearfix']],

            'refund--share-tool--3--icon' => [
              '#type' => 'textfield',
              '#title' => $this->t('Share Tool'),
              '#placeholder' => $this->getHelper()->get('templates.refund.share-tool.3.icon'),
              '#default_value' => $this->getHelper()->get('templates.refund.share-tool.3.icon'),
              '#prefix' => '<div class="layout-column layout-column--half">',
              '#suffix' => '</div>',
              '#attributes' => [
                'class' => [
                  /*'js-component-options',
                  'options-component-refund',*/
                ],
                'data-component-name' => 'refund',
                'data-option-name' => 'share-tool',
              ],
            ],

            'refund--share-tool--3--color' => [
              '#type' => 'color',
              '#title' => $this->t('Share Tool Color'),
              '#placeholder' => $this->getHelper()->get('templates.refund.share-tool.3.color'),
              '#default_value' => $this->getHelper()->get('templates.refund.share-tool.3.color'),
              '#prefix' => '<div class="layout-column layout-column--half">',
              '#suffix' => '</div>',
              '#attributes' => [
                'class' => [
                  /*'js-component-styles',
                  'styles-component-refund',*/
                ],
                'data-style-name' => 'color',
                'data-component-name' => 'refund',
                'data-option-name' => 'share-tool',
              ],
            ],
          ],
        ],
      ],

      'button' => [
        '#type' => 'details',
        '#title' => $this->t('Button'),
        '#open' => FALSE,

        'refund--button-text' => [
          '#type' => 'textfield',
          '#title' => $this->t('Refund Button Text'),
          '#placeholder' => $this->getHelper()->get('templates.refund.button-text'),
          '#default_value' => $this->getHelper()->get('templates.refund.button-text'),
          '#attributes' => [
            'class' => [
              /*'js-component-options',
              'options-component-pledge',*/
            ],
            'data-component-name' => 'refund',
            'data-option-name' => 'button-text',
          ],
          '#prefix' => "<div class='layout-column layout-column--half'>",
          '#suffix' => "</div>",
        ],

        'refund--button-icon' => [
          '#type' => 'textfield',
          '#title' => $this->t('Refund Button Icon'),
          '#placeholder' => $this->getHelper()->get('templates.refund.button-icon'),
          '#default_value' => $this->getHelper()->get('templates.refund.button-icon'),
          '#attributes' => [
            'class' => [
              /*'js-component-options',
              'options-component-pledge',*/
            ],
            'data-component-name' => 'refund',
            'data-option-name' => 'button-icon',
          ],
          '#prefix' => "<div class='layout-column layout-column--half'>",
          '#suffix' => "</div>",
        ],

        [
          $this->getButtonStyles('refund'),
        ],
      ],

      'arrow' => [
        '#type' => 'details',
        '#title' => $this->t('Arrow'),
        '#open' => FALSE,
        [
          $this->getArrowStylesBlock('refund'),
        ],
      ],
    ];

    return $tab;
  }

  /**
   * Get content fot tab.
   *
   * @return array
   *   Form element.
   */
  private function getOtherTemplateContentTab() {
    $tab = [];

    $tab['unlock-view'] = [
      '#type' => 'fieldset',
      '#title' => t('Unlock view'),

      'unlock-view-preview' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['layout-column', 'layout-column--half'],
        ],
        'view' => [
          '#type' => 'markup',
          '#theme' => 'atm-unlock-view-template-preview',
        ],
      ],

      'unlock-view-config' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['layout-column', 'layout-column--half'],
        ],
        'button' => [
          '#type' => 'details',
          '#title' => $this->t('Button'),
          '#open' => TRUE,

          'unlock--button-text' => [
            '#type' => 'textfield',
            '#title' => $this->t('Unlock Button Text'),
            '#placeholder' => $this->getHelper()->get('templates.unlock.button-text'),
            '#default_value' => $this->getHelper()->get('templates.unlock.button-text'),
            '#attributes' => [
              'class' => [
                /*'js-component-options',
                'options-component-pledge',*/
              ],
              'data-component-name' => 'other',
              'data-option-name' => 'button-text',
            ],
            '#prefix' => "<div class='layout-column layout-column--half'>",
            '#suffix' => "</div>",
          ],

          'unlock--button-icon' => [
            '#type' => 'textfield',
            '#title' => $this->t('Unlock Button Icon'),
            '#placeholder' => $this->getHelper()->get('templates.unlock.button-icon'),
            '#default_value' => $this->getHelper()->get('templates.unlock.button-icon'),
            '#attributes' => [
              'class' => [
                /*'js-component-options',
                'options-component-pledge',*/
              ],
              'data-component-name' => 'other-unlock',
              'data-option-name' => 'button-icon',
            ],
            '#prefix' => "<div class='layout-column layout-column--half'>",
            '#suffix' => "</div>",
          ],
          [
            $this->getButtonStyles('other-unlock'),
          ],
        ],
      ],
    ];

    $tab['price-view'] = [
      '#type' => 'fieldset',
      '#title' => t('Price view'),

      'price-view-preview' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['layout-column', 'layout-column--half'],
        ],
        'view' => [
          '#type' => 'markup',
          '#theme' => 'atm-price-view-template-preview',
        ],
      ],

      'price-view-config' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['layout-column', 'layout-column--half'],
        ],

        'price' => [
          '#type' => 'details',
          '#title' => $this->t('Price'),
          '#open' => TRUE,

          'price' => [
            '#type' => 'textfield',
            '#title' => $this->t('Price'),
            '#placeholder' => $this->getHelper()->get('templates.price'),
            '#default_value' => $this->getHelper()->get('templates.price'),
            '#attributes' => [
              'class' => [
                /*'js-component-options',
                'options-component-pledge',*/
              ],
              'data-component-name' => 'other',
              'data-option-name' => 'price',
            ],
          ],
          [
            $this->getPriceStyles('other-price'),
          ],
        ],
      ],
    ];

    return $tab;
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
   *   Ajax Response.
   */
  public function saveParams(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    foreach ($form_state->getValues() as $elementName => $value) {
      if (!in_array($elementName, $form_state->getCleanValueKeys())) {
        $elementName = $this->prepareElementName($elementName);
        $this->getHelper()->set('templates.' . $elementName, $value);
      }
    }

    $inputs = $form_state->getUserInput();

    $templates = [];

    foreach ($inputs['templates'] as $componentName => $rendered) {
      $component = Json::decode($rendered);
      $templates[$componentName] = base64_encode($component);
    }

    $this->getAtmHttpClient()->propertyUpdateConfig($templates);

    $errors = drupal_get_messages('error');
    if ($errors) {
      $response->addCommand(
        new BaseCommand('showNoty', [
          'options' => [
            'type' => 'error',
            'text' => implode("<br>", $errors),
            'maxVisible' => 1,
            'timeout' => 5000,
          ],
        ])
      );
    }
    else {
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
    }

    return $response;
  }

}
