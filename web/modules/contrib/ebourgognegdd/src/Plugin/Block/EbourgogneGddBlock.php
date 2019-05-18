<?php

namespace Drupal\ebourgognegdd\Plugin\Block;

use \Drupal\ebourgognegdd\Form\EbourgogneGddConfigForm;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'E-bourgogne annuaires' block.
 *
 * Drupal\Core\Block\BlockBase gives us a very useful set of basic functionality
 * for this configurable block. We can just fill in a few of the blanks with
 * defaultConfiguration(), blockForm(), blockSubmit(), and build().
 *
 * @Block(
 *   id = "ebourgognegdd_config_block",
 *   admin_label = "Configuration block for e-bourgogne guides"
 * )
 */
class EbourgogneGddBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'list_guides' => array(),
    );
  }

  /**
   * $this->variable_set doesn't exist anymore in drupal 8
   * we redefine it.
   */
  function variable_set($name, $value) {
    $config = \Drupal::configFactory()->getEditable('core.site_information');

    $config->set($name, $value)->save();
  }

  /**
   * $this->variable_get doesn't exist anymore in drupal 8
   * we redefine it.
   */
  function variable_get($name, $default_return) {
    $config = \Drupal::configFactory()->getEditable('core.site_information');

    $retour = $config->get($name);

    if ($retour == NULL) {
      $retour = $default_return;
    }

    return $retour;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    EbourgogneGddConfigForm::checkApiKey($this->variable_get('ebourgognegdd_api_key', ''));

    $form['guideType'] = array(
      '#id' => 'gdd-flux',
      '#type' => 'select',
      '#title' => t('Type de guide'),
      '#options' => array('part' => 'Particulier', 'asso' => 'Association', 'pro' => 'Professionel'),
      '#required' => TRUE,
      '#default_value' => 'part',
      '#suffix' => '<div id="gdd-list"></div>',
      '#attached' => array(
        'library' => array(
          'ebourgognegdd/ebourgognegdd',
        ),
      ),
    );
    $form['guideType']['#attributes']['class'] = array('form-control');

    $form['selection'] = array(
      '#id' => 'selection',
      '#type' => 'textfield',
      '#title' => t("Votre sélection"),
      '#required' => TRUE,
      '#disabled' => TRUE,
    );
    $form['selection']['#attributes']['class'] = array('form-control');

    $form['cssUrl'] = array(
      '#type' => 'textfield',
      '#title' => t("Feuille de style personnalisée (URL)"),
      '#default_value' => isset($config['cssUrl']) ? $config['cssUrl'] : '',
    );
    $form['cssUrl']['#attributes']['class'] = array('form-control');

    // Hidden fields.
    $form['guideUrl'] = array(
      '#id' => 'guideUrl',
      '#type' => 'textfield',
      '#title' => t("Url du script"),
      '#default_value' => '',
      '#prefix' => '<div class="hidden">',
    );

    $form['guideUrl']['#suffix'] = '</div><div class="messages messages--warning">' . t("Attention : il est deconseillé d'utiliser plusieurs Guides dans votre application.") . '</div>';

    $form['#attached'] = array(
      'library' => array(
        'ebourgognegdd/ebourgognegdd',
      ),
    );

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {

    $this->setConfigurationValue('guideUrl', $form_state->getValue('guideUrl'));
    $this->setConfigurationValue('cssUrl', $form_state->getValue('cssUrl'));
  }

  /**
   *
   */
  private function constructUrl() {
    $config = $this->getConfiguration();

    $result = "";

    $css_url = "";

    if (isset($config['cssUrl'])) {
      if (!empty($config['cssUrl'])) {
        $result .= '<div id="gdd"></div>';
        $css_url = $config['cssUrl'];
      }
    }

    $this->variable_set('ebourgognegdd_css_url', $css_url);

    $result .= '<script type="text/javascript" src="' . $config['guideUrl'] . '"></script>';

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $snippet = $this->constructUrl();

    $form = array();

    $form['markup'] = array(
      '#markup' => $snippet,
    // By default script tag is forbidden.
      '#allowed_tags' => array('script', 'div'),
    );

    $form['#attached'] = array(
      'library' => array(
        'ebourgognegdd/ebourgognegdd',
      ),
    );

    /*$form['#attached']['html_head'][] = [[
    '#tag' => 'link',
    '#value' => 'rel="stylesheet" href="http://localhost/test.css"',
    ], 'gdd_css'];*/

    // Add the css to apply to the guide block (done with ebourgognegdd.js because previous code didn't work)
    $form['#attached']['drupalSettings']['ebourgogne_gdd']['css_url'] = $this->variable_get('ebourgognegdd_css_url', '');

    return $form;
  }

}
