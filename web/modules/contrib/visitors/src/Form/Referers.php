<?php

namespace Drupal\visitors\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\visitors\Form\DateFilter;

class Referers extends DateFilter {
  public function getFormID() {
    return 'visitors_referers_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->_setSessionRefererType();
    $form = parent::buildForm($form, $form_state);

    unset($form['submit']);

    $form['visitors_referer'] = array(
      '#type'          => 'fieldset',
      '#title'         => t('Referers type filter'),
      '#collapsible'   => FALSE,
      '#collapsed'     => FALSE,
      '#description'   => t('Choose referers type')
    );

    $form['visitors_referer']['referer_type'] = array(
      '#type' => 'select',
      '#title' => 'Referer type',
      '#default_value' => $_SESSION['referer_type'],
      '#options' => array(
        REFERER_TYPE_INTERNAL_PAGES => t('Internal pages'),
        REFERER_TYPE_EXTERNAL_PAGES => t('External pages'),
        REFERER_TYPE_ALL_PAGES      => t('All pages')
      ),
    );

    $form['submit'] = array(
      '#type'          => 'submit',
      '#value'         => t('Save'),
    );

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $_SESSION['referer_type'] = $form_state['values']['referer_type'];
  }

  /**
   * Set to session info default values for visitors referer type.
   */
  protected function _setSessionRefererType() {
    if (!isset($_SESSION['referer_type'])) {
      $_SESSION['referer_type'] = REFERER_TYPE_EXTERNAL_PAGES;
    }
  }
}

