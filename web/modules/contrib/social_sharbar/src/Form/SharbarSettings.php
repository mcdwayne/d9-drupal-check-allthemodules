<?php

namespace Drupal\social_sharbar\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SharebarSettings.
 *
 * @package Drupal\social_sharebar\Form;
 */
class SharbarSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sharebarsettings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_sharebar.settings');
    $num_sharebar_form = $form_state->get('num_sharebar_form');

    if (empty($num_sharebar_form)) {
      $sharebar_form_fieldset_count = $config->get('sharebar_form_fieldset_count');
      if ($sharebar_form_fieldset_count) {
        $form_state->set('num_sharebar_form', $sharebar_form_fieldset_count);
      }
      else {
        $form_state->set('num_sharebar_form', 1);
      }
    }
    $form['#tree'] = TRUE;
    $form['sharebar'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Sharebar Buttons'),
      '#prefix' => "<div id='sharebar-form-fieldset-wrapper'>",
      '#suffix' => '</div>',
    ];
    for ($i = 0; $i < $form_state->get('num_sharebar_form'); $i++) {

      $form_count = $i + 1;
      $form['sharebar'][$i]['sharebar_button'] = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t('Name'),
        '#description' => $this->t('Social sharebar name.'),
        '#maxlength' => 50,
        '#size' => 50,
        '#default_value' => $config->get($form_count . 'sharebar_button'),
      ];
      $form['sharebar'][$i]['sharebar_button_value'] = [
        '#type' => 'textarea',
        '#required' => TRUE,
        '#title' => $this->t('Big Button'),
        '#description' => $this->t('Social sharebar button value'),
        '#default_value' => $config->get($form_count . 'sharebar_button_value'),
      ];
      $form['sharebar'][$i]['sharebar_small_button_value'] = [
        '#type' => 'textarea',
        '#required' => TRUE,
        '#title' => $this->t('Smaill Button'),
        '#description' => $this->t('Social sharebar button value'),
        '#default_value' => $config->get($form_count . 'sharebar_small_button_value'),
      ];

    }
    $form['token_help'] = [
      '#title' => $this->t('Replacement patterns'),
      '#type' => 'fieldset',
      '#description' => $this->t('Be very careful while using node specific tokens, 
        as these are available only for nodes, so you might see naked token on 
        non-nodes and can be a reason for breakage:'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $form['token_help']['token_tree'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['node'],
      '#show_restricted' => TRUE,
      '#dialog' => TRUE,
      '#description' => $this->t('x'),
    ];
    $form['displayoptions'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Sharebar Display'),
      '#prefix' => "<div id='sharebardisplay-form-fieldset-wrapper'>",
      '#suffix' => '</div>',
    ];
    $form['displayoptions']['sharebar_bar_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Sharebar Position'),
      '#default_value' => $config->get('sharebar_bar_position'),
      '#options' => ['left' => $this->t('Left'), 'right' => $this->t('Right')],
    ];
    $form['displayoptions']['sharebar_bar_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Minimum width in pixels required to show vertical Sharebar to the left of post (cannot be blank)'),
      '#size' => 10,
      '#required' => TRUE,
      '#default_value' => $config->get('sharebar_bar_width'),
    ];
    $form['displayoptions']['top_offset'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Top Offset'),
      '#size' => 10,
      '#required' => TRUE,
      '#default_value' => $config->get('top_offset'),
    ];
    $form['displayoptions']['left_offset'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Left Offset'),
      '#size' => 10,
      '#required' => TRUE,
      '#default_value' => $config->get('left_offset'),
    ];
    $form['displayoptions']['right_offset'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Right Offset'),
      '#size' => 10,
      '#required' => TRUE,
      '#default_value' => $config->get('right_offset'),
    ];
    $form['sharebar']['actions'] = [
      '#type' => 'actions',
    ];
    $form['sharebar']['actions']['add_name'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add one more'),
      '#submit' => ['::addOne'],
      '#ajax' => [
        'callback' => '::addmoreCallback',
        'wrapper' => "sharebar-form-fieldset-wrapper",
      ],
    ];
    if ($form_state->get('num_sharebar_form') > 1) {
      $form['sharebar']['actions']['remove_name'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove one'),
        '#submit' => ['::removeCallback'],
        '#ajax' => [
          'callback' => '::addmoreCallback',
          'wrapper' => "sharebar-form-fieldset-wrapper",
        ],
      ];
    }
    $form_state->setCached(FALSE);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config_obj = $this->config('social_sharebar.settings');
    $config_obj->delete();
    $j = 0;
    foreach ($form_state->getValue(['sharebar']) as $key => $value) {
      if (is_numeric($key)) {
        $config_obj->set(($j + 1) . 'sharebar_button',
          $form_state->getValue(['sharebar', $key, 'sharebar_button']))
          ->set(($j + 1) . 'sharebar_button_value',
            $form_state->getValue(['sharebar', $key, 'sharebar_button_value']))
          ->set(($j + 1) . 'sharebar_small_button_value',
            $form_state->getValue(['sharebar', $key,
              'sharebar_small_button_value',
            ]))
          ->save();
        $j++;
      }
    }
    if ($j) {
      $config_obj->set('sharebar_form_fieldset_count', $j)
        ->save();
    }
    $config_obj->set('sharebar_bar_position', $form_state->getValue(['displayoptions', 'sharebar_bar_position']))->save();
    $config_obj->set('sharebar_bar_width', $form_state->getValue(['displayoptions', 'sharebar_bar_width']))->save();
    $config_obj->set('top_offset', $form_state->getValue(['displayoptions', 'top_offset']))->save();
    $config_obj->set('left_offset', $form_state->getValue(['displayoptions', 'left_offset']))->save();
    $config_obj->set('right_offset', $form_state->getValue(['displayoptions', 'right_offset']))->save();
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function addmoreCallback(array &$form, FormStateInterface $form_state) {
    return $form['sharebar'];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public function addOne(array &$form, FormStateInterface $form_state) {
    $num_sharebar_form = $form_state->get('num_sharebar_form');
    $add_button = $num_sharebar_form + 1;
    $form_state->set('num_sharebar_form', $add_button);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public function removeCallback(array &$form, FormStateInterface $form_state) {
    $num_sharebar_form = $form_state->get('num_sharebar_form');
    if ($num_sharebar_form > 1) {
      $remove_button = $num_sharebar_form - 1;
      $form_state->set('num_sharebar_form', $remove_button);
    }

    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'social_sharebar.settings',
    ];
  }

}
