<?php

namespace Drupal\tipsy\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AdminSettingsForm.
 *
 * @package Drupal\tipsy\Form
 */
class AdminSettingsForm extends ConfigFormBase {

  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactory $configFactory) {
    $this->configFactory = $configFactory->getEditable('tipsy.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tipsy_admin';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'tipsy.settings',
    ];
  }

  /**
   * Function to add one option.
   */
  public function tipsyAddOneOption(array $form, FormStateInterface $form_state) {
    $options_count = $this->configFactory->get("total_options");
    $options_count++;
    $this->configFactory->set('total_options', $options_count);
    $form_state->setRebuild();
  }

  /**
   * Function to return form with existing form fields.
   */
  public function tipsyAddMoreCallback(array $form, FormStateInterface $form_state) {
    return $form['custom_selectors'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $total_options = $this->configFactory->get("total_options");

    $settings = _tipsy_get_settings();
    $form = array();
    $form['#tree'] = TRUE;

    // Add admin js and admin css files.
    $form['#attached']['library'][] = 'tipsy/tipsy_admin';

    $form['drupal_forms'] = array(
      '#type' => 'details',
      '#title' => $this->t('Drupal forms general settings'),
      '#weight' => -5,
      '#open' => TRUE,
    );
    $form['drupal_forms']['forms'] = array(
      '#type' => 'checkbox',
      '#default_value' => $this->configFactory->get("wide_settings")['drupal_forms']['forms'],
      '#title' => $this->t('Apply Tipsy for form items descriptions on all Drupal forms.'),
      '#description' => $this->t('This will automatically enable Tipsy tooltips to form elements descriptions.'),
    );
    $form['drupal_forms']['wrapper'] = array(
      '#tree' => TRUE,
      '#weight' => 0,
      '#prefix' => '<div class="clear-block" id="tipsy-drupal-forms-wrapper">',
      '#suffix' => '</div>',
    );

    $form['drupal_forms']['wrapper']['options'] = $this->tipsyOptionsForm($settings['drupal_forms'], TRUE);

    $form['custom_selectors'] = array(
      '#type' => 'details',
      '#title' => $this->t('Custom selectors'),
      '#description' => '<div class="my_selector">
    <div class="contents">Tooltip HTML that can\'t really fit into an attribute</div>Ordinary text that the user sees</div>',
      '#prefix' => '<div id="names-fieldset-wrapper">',
      '#suffix' => '</div>',
      '#open' => TRUE,
    );

    // Variable $total_options determine the number of textfields to build.
    $custom_selectors = $this->configFactory->get('wide_settings')['custom_selectors'];

    for ($i = 0; $i <= $total_options; $i++) {
      isset($custom_selectors[$i]) ?: $custom_selectors[$i] = $this->configFactory->get('new_rule_settings');
      $form['custom_selectors'][$i]['selector'] = array(
        '#type' => 'textarea',
        '#weight' => 0,
        '#rows' => 2,
        '#default_value' => $custom_selectors[$i]['selector'],
      );

      $form['custom_selectors'][$i]['options']['fade'] = array(
        '#type' => 'checkbox',
        '#default_value' => $custom_selectors[$i]['options']['fade'],
        '#description' => $this->t('This will make the tooltip fade.'),
        '#title' => $this->t('Make Tipsy tooltips fade.'),
        '#weight' => 1,
        '#prefix' => '<div class="tipsy-selector-options clear-block">',
      );

      $form['custom_selectors'][$i]['options']['gravity'] = array(
        '#type' => 'select',
        '#default_value' => $custom_selectors[$i]['options']['gravity'],
        '#title' => $this->t('Tipsy arrow position'),
        '#description' => $this->t('Specify the position of the tooltip when it appears.'),
        '#weight' => 2,
        '#options' => array(
          'nw' => $this->t('North west'),
          'n' => $this->t('North'),
          'ne' => $this->t('North east'),
          'w' => $this->t('West'),
          'e' => $this->t('East'),
          'sw' => $this->t('South west'),
          's' => $this->t('South'),
          'se' => $this->t('South east'),
          'autoNS' => $this->t('Auto detect North/South'),
          'autoWE' => $this->t('Auto detect West/East'),
        ),
      );

      $form['custom_selectors'][$i]['options']['delayIn'] = array(
        '#type' => 'textfield',
        '#default_value' => $custom_selectors[$i]['options']['delayIn'],
        '#title' => $this->t('Delay when appearing'),
        '#description' => $this->t('Amount of milliseconds for the tooltip to appear.'),
        '#size' => 5,
        '#maxlength' => 5,
        '#weight' => 3,
      );

      $form['custom_selectors'][$i]['options']['delayOut'] = array(
        '#type' => 'textfield',
        '#default_value' => $custom_selectors[$i]['options']['delayOut'],
        '#title' => $this->t('Delay when disappearing'),
        '#description' => $this->t('Amount of milliseconds for the tooltip to disappear.'),
        '#size' => 5,
        '#maxlength' => 5,
        '#weight' => 4,
      );

      $form['custom_selectors'][$i]['options']['trigger'] = array(
        '#type' => 'select',
        '#default_value' => $custom_selectors[$i]['options']['trigger'],
        '#description' => $this->t('Specify what action will make the tooltip appear.'),
        '#title' => $this->t('Tipsy trigger'),
        '#weight' => 5,
        '#options' => array(
          'focus' => $this->t('Focus'),
          'hover' => $this->t('Hover'),
        ),
      );

      $form['custom_selectors'][$i]['options']['opacity'] = array(
        '#type' => 'textfield',
        '#default_value' => $custom_selectors[$i]['options']['opacity'],
        '#title' => $this->t('Tooltip opacity'),
        '#description' => $this->t('A value between 0 and 1.'),
        '#size' => 5,
        '#maxlength' => 4,
        '#weight' => 6,
      );

      $form['custom_selectors'][$i]['options']['offset'] = array(
        '#type' => 'textfield',
        '#default_value' => $custom_selectors[$i]['options']['offset'],
        '#title' => $this->t('Tooltip offset'),
        '#description' => $this->t('Number of pixels in which the tooltip will distance from the element.'),
        '#size' => 5,
        '#maxlength' => 5,
        '#weight' => 7,
      );

      $form['custom_selectors'][$i]['options']['html'] = array(
        '#type' => 'checkbox',
        '#default_value' => $custom_selectors[$i]['options']['html'],
        '#description' => $this->t('This will let HTML code be parsed inside the tooltip.'),
        '#title' => $this->t('Allow HTML in tooltip content.'),
        '#weight' => 1,
      );
      $form['custom_selectors'][$i]['options']['tooltip_content'] = array(
        '#type' => 'details',
        '#title' => $this->t('Tooltip content'),
        '#open' => TRUE,
        '#weight' => 9,
      );
      $form['custom_selectors'][$i]['options']['tooltip_content']['source'] = array(
        '#type' => 'radios',
        '#title' => $this->t('Source'),
        '#default_value' => $custom_selectors[$i]['options']['tooltip_content']['source'],
        '#options' => array('attribute' => $this->t('HTML attribute'), 'child' => $this->t('Child element')),
      );
      $form['custom_selectors'][$i]['options']['tooltip_content']['selector'] = array(
        '#type' => 'textarea',
        '#title' => $this->t('Selector'),
        '#default_value' => $custom_selectors[$i]['options']['tooltip_content']['selector'],
        '#description' => $this->t("The name of the HTML attribute or a selector pointing to the child element (e.g: .content). <br /> Refer to the module's README.txt for more information."),
        '#rows' => 1,
        '#maxlength' => 400,
      );

      $form['closure'] = array(
        '#weight' => 10,
        '#suffix' => '</div>',
      );
    }

    $form['custom_selectors']['add_option'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Add one more'),
      '#submit' => array('::tipsyAddOneOption'),
      '#ajax' => array(
        'callback' => '::tipsyAddMoreCallback',
        'wrapper' => 'names-fieldset-wrapper',
      ),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $options_count = $this->configFactory->get("total_options");
    $wide_settings = [
      'drupal_forms' => [
        'forms' => $values['drupal_forms']['forms'],
        'options' => $values['drupal_forms']['wrapper']['options'],
      ],
      'custom_selectors' => [],
    ];
    for ($i = 0; $i <= $options_count; $i++) {
      $wide_settings['custom_selectors'][$i] = $values['custom_selectors'][$i];
    }

    $this->configFactory->set('wide_settings', $wide_settings)
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Implementation of form function for the tooltip options.
   */
  public function tipsyOptionsForm($settings = FALSE, $drupal_forms = FALSE) {

    if ($settings == FALSE) {
      $settings = _tipsy_get_settings(TRUE);
    }

    $form = array();
    $form['fade'] = array(
      '#type' => 'checkbox',
      '#default_value' => $settings['options']['fade'],
      '#description' => $this->t('This will make the tooltip fade.'),
      '#title' => $this->t('Make Tipsy tooltips fade.'),
      '#weight' => 0,
      '#prefix' => '<div class="tipsy-selector-options clear-block">',
    );

    $form['gravity'] = array(
      '#type' => 'select',
      '#default_value' => $settings['options']['gravity'],
      '#title' => $this->t('Tipsy arrow position'),
      '#description' => $this->t('Specify the position of the tooltip when it appears.'),
      '#weight' => 2,
      '#options' => array(
        'nw' => $this->t('North west'),
        'n' => $this->t('North'),
        'ne' => $this->t('North east'),
        'w' => $this->t('West'),
        'e' => $this->t('East'),
        'sw' => $this->t('South west'),
        's' => $this->t('South'),
        'se' => $this->t('South east'),
        'autoNS' => $this->t('Auto detect North/South'),
        'autoWE' => $this->t('Auto detect West/East'),
      ),
    );

    $form['delayIn'] = array(
      '#type' => 'textfield',
      '#default_value' => $settings['options']['delayIn'],
      '#title' => $this->t('Delay when appearing'),
      '#description' => $this->t('Amount of milliseconds for the tooltip to appear.'),
      '#size' => 5,
      '#maxlength' => 5,
      '#weight' => 3,
    );

    $form['delayOut'] = array(
      '#type' => 'textfield',
      '#default_value' => $settings['options']['delayOut'],
      '#title' => $this->t('Delay when disappearing'),
      '#description' => $this->t('Amount of milliseconds for the tooltip to disappear.'),
      '#size' => 5,
      '#maxlength' => 5,
      '#weight' => 4,
    );

    $form['trigger'] = array(
      '#type' => 'select',
      '#default_value' => $settings['options']['trigger'],
      '#description' => $this->t('Specify what action will make the tooltip appear.'),
      '#title' => $this->t('Tipsy trigger'),
      '#weight' => 5,
      '#options' => array(
        'focus' => $this->t('Focus'),
        'hover' => $this->t('Hover'),
      ),
    );

    $form['opacity'] = array(
      '#type' => 'textfield',
      '#default_value' => $settings['options']['opacity'],
      '#title' => $this->t('Tooltip opacity'),
      '#description' => $this->t('A value between 0 and 1.'),
      '#size' => 5,
      '#maxlength' => 4,
      '#weight' => 6,
    );

    $form['offset'] = array(
      '#type' => 'textfield',
      '#default_value' => $settings['options']['offset'],
      '#title' => $this->t('Tooltip offset'),
      '#description' => $this->t('Number of pixels in which the tooltip will distance from the element.'),
      '#size' => 5,
      '#maxlength' => 5,
      '#weight' => 7,
    );

    if ($drupal_forms == FALSE) {
      $form['html'] = array(
        '#type' => 'checkbox',
        '#default_value' => $settings['options']['html'],
        '#description' => $this->t('This will let HTML code be parsed inside the tooltip.'),
        '#title' => $this->t('Allow HTML in tooltip content.'),
        '#weight' => 1,
      );

      $form['tooltip_content'] = array(
        '#type' => 'details',
        '#title' => $this->t('Tooltip content'),
        '#weight' => 9,
        '#open' => TRUE,
      );

      $form['tooltip_content']['source'] = array(
        '#type' => 'radios',
        '#title' => $this->t('Source'),
        '#default_value' => $settings['options']['tooltip_content']['source'],
        '#options' => array('attribute' => $this->t('HTML attribute'), 'child' => $this->t('Child element')),
      );

      $form['tooltip_content']['selector'] = array(
        '#type' => 'textarea',
        '#title' => $this->t('Selector'),
        '#default_value' => $settings['options']['tooltip_content']['selector'],
        '#description' => $this->t("The name of the HTML attribute or a selector pointing to the child element (e.g: .content). <br /> Refer to the module's README.txt for more information."),
        '#rows' => 1,
        '#maxlength' => 400,
      );

    }

    $form['closure'] = array(
      '#weight' => 10,
      '#suffix' => '</div>',
    );
    return $form;
  }

}
