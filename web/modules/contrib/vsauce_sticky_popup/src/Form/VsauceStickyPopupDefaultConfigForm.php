<?php

namespace Drupal\vsauce_sticky_popup\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class VsauceStickyPopupDefaultConfig.
 *
 * @package Drupal\vsauce_sticky_popup\Form
 */
class VsauceStickyPopupDefaultConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'vsauce_sticky_popup.default_config',
    ];
  }

  /**
   * Define formid.
   *
   * @return string
   *   Return string with formid.
   */
  public function getFormId() {
    return 'default_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('vsauce_sticky_popup.default_config');
    $buildForm = parent::buildForm($form, $form_state);

    $buildForm['#prefix'] = '<div id="default-config-form">';
    $buildForm['#suffix'] = '</div>';
    // Declaration of details with options.
    $buildForm['development'] = [
      '#type' => 'details',
      '#title' => $this->t('Development'),
      '#open' => TRUE,
    ];

    $buildForm['p_sticky'] = [
      '#type' => 'details',
      '#title' => $this->t('Positions'),
      '#open' => TRUE,
    ];

    // Show path id in all pages.
    $buildForm['development']['show_path_id'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Path id on all pages'),
      '#default_value' => $config->get('show_path_id'),
    ];

    // Position Sticky Popup.
    $buildForm['p_sticky']['position_sticky_popup'] = [
      '#type' => 'select',
      '#title' => $this->t('Collapsible content'),
      '#options' => [
        '' => '',
        'top' => $this->t('Top'),
        'right' => $this->t('Right'),
        'bottom' => $this->t('Bottom'),
        'left' => $this->t('Left'),
      ],
      '#required' => TRUE,
      '#default_value' => $config->get('position_sticky_popup'),
      '#ajax' => [
        'callback' => [
          $this,
          'availableOptions',
        ],
      ],
    ];

    $defaultValue = $form_state->getValue('position_open_button') ? $form_state->getValue('position_open_button') : $config->get('position_open_button');
    $defaultPositionStickyPopup = $form_state->getValue('position_sticky_popup') ? $form_state->getValue('position_sticky_popup') : $config->get('position_sticky_popup');
    $buildForm['p_sticky']['position_open_button'] = [
      '#type' => 'select',
      '#title' => $this->t('Button open/close'),
      '#options' => $this->getOptionByPositionPopup($defaultPositionStickyPopup),
      '#default_value' => $defaultValue,
    ];

    $buildForm['p_sticky']['position_arrow'] = [
      '#type' => 'select',
      '#title' => $this->t('Position Text around arrow'),
      '#options' => [
        '' => '',
        'prefix' => $this->t('Prefix'),
        'suffix' => $this->t('Suffix'),
      ],
      '#required' => TRUE,
      '#default_value' => $config->get('position_arrow'),
    ];
    return $buildForm;
  }

  /**
   * {@inheritdoc}
   */
  private function getOptionByPositionPopup($value) {
    switch ($value) {
      case 'top':
        $options = [
          '' => $this->t('Center'),
          'left' => $this->t('Left'),
          'right' => $this->t('Right'),
        ];
        break;

      case 'right':
        $options = [
          '' => $this->t('Center'),
          'top' => $this->t('Top'),
          'right' => $this->t('Right'),
        ];
        break;

      case 'bottom':
        $options = [
          '' => $this->t('Center'),
          'left' => $this->t('Left'),
          'right' => $this->t('Right'),
        ];
        break;

      case 'left':
        $options = [
          '' => $this->t('Center'),
          'top' => $this->t('Top'),
          'bottom' => $this->t('Bottom'),
        ];
        break;

      default:
        $options = [];
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function availableOptions(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $options = $this->getOptionByPositionPopup($form_state->getValue('position_sticky_popup'));

    $form['p_sticky']['position_open_button']['#options'] = $options;
    $response->addCommand(new ReplaceCommand('#default-config-form', $form));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('vsauce_sticky_popup.default_config')
      ->set('show_path_id', $form_state->getValue('show_path_id'))
      ->set('position_sticky_popup', $form_state->getValue('position_sticky_popup'))
      ->set('position_open_button', $form_state->getValue('position_open_button'))
      ->set('position_arrow', $form_state->getValue('position_arrow'))
      ->save();
  }

}
