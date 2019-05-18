<?php

namespace Drupal\entityconnect\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class DefaultForm.
 *
 * @package Drupal\entityconnect\Form
 */
class AdministrationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'entityconnect.administration_config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entityconnect_administration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('entityconnect.administration_config');
    $defaults = $config->get();

    self::attach($form, $defaults);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('entityconnect.administration_config')
      ->set('icons.icon_add', $form_state->getValue(array(
        'entityconnect',
        'icons',
        'icon_add',
      )
      )
      )
      ->set('icons.icon_edit', $form_state->getValue(array(
        'entityconnect',
        'icons',
        'icon_edit',
      )
      )
      )
      ->set('buttons.button_add', $form_state->getValue(array(
        'entityconnect',
        'buttons',
        'button_add',
      )
      )
      )
      ->set('buttons.button_edit', $form_state->getValue(array(
        'entityconnect',
        'buttons',
        'button_edit',
      )
      )
      )
      ->save();
  }

  /**
   * Attach the common entityconnect settings to the given form.
   *
   * @param array $form
   *   The form to attach to.
   * @param array $defaults
   *   Entityconnect defaults.
   */
  public static function attach(array &$form, array $defaults) {

    $form['entityconnect'] = array(
      '#type' => 'details',
      '#title' => t('EntityConnect default Parameters'),
      '#open' => TRUE,
      '#tree' => TRUE,
    );

    $form['entityconnect']['buttons'] = array(
      '#type' => 'fieldset',
      '#title' => t('Buttons display Parameters'),
    );

    $form['entityconnect']['buttons']['button_add'] = array(
      '#required' => '1',
      '#default_value' => $defaults['buttons']['button_add'],
      '#description' => t('Default: "off"<br />
                            Choose "on" if you want the "add" buttons displayed by default.<br />
                            Each field can override this value.'),
      '#weight' => '0',
      '#type' => 'radios',
      '#options' => array(
        '0' => t('on'),
        '1' => t('off'),
      ),
      '#title' => t('Default Entity Connect "add" button display'),
    );

    $form['entityconnect']['buttons']['button_edit'] = array(
      '#required' => '1',
      '#default_value' => $defaults['buttons']['button_edit'],
      '#description' => t('Default: "off"<br />
                            Choose "on" if you want the "edit" buttons displayed by default.<br />
                            Each field can override this value.'),
      '#weight' => '1',
      '#type' => 'radios',
      '#options' => array(
        '0' => t('on'),
        '1' => t('off'),
      ),
      '#title' => t('Default Entity Connect "edit" button display'),
    );

    $form['entityconnect']['icons'] = array(
      '#type' => 'fieldset',
      '#title' => t('Icons display Parameters'),
    );

    $form['entityconnect']['icons']['icon_add'] = array(
      '#required' => '1',
      '#key_type_toggled' => '1',
      '#default_value' => $defaults['icons']['icon_add'],
      '#description' => t('Default: "Icon only"<br />
                           Choose "Icon + Text" if you want to see the edit (pencil) icon + the text displayed by default.<br />
                           Choose "Text only" if you don\'t want to see the edit (pencil) icon displayed by default.<br />
                           Each field can override this value.'),
      '#weight' => '2',
      '#type' => 'radios',
      '#options' => array(
        '0' => t('Icon only'),
        '1' => t('Icon + Text'),
        '2' => t('Text only'),
      ),
      '#title' => t('Default Entity Connect "add (+) icon" display'),
    );

    $form['entityconnect']['icons']['icon_edit'] = array(
      '#required' => '1',
      '#default_value' => $defaults['icons']['icon_edit'],
      '#description' => t('Default: "Icon only"<br />
                           Choose "Icon + Text" if you want to see the edit (pencil) icon + the text displayed by default.<br />
                           Choose "Text only" if you don\'t want to see the edit (pencil) icon displayed by default.<br />
                           Each field can override this value.'),
      '#weight' => '3',
      '#type' => 'radios',
      '#options' => array(
        '0' => t('Icon only'),
        '1' => t('Icon + Text'),
        '2' => t('Text only'),
      ),
      '#title' => t('Default Entity Connect "edit (pencil) icon" display'),
    );
  }

}
