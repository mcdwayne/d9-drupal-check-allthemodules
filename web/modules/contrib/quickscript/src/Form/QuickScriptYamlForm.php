<?php
/**
 * @file
 * Contains \Drupal\quickscript\Form\QuickScriptYamlForm.
 */

namespace Drupal\quickscript\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\quickscript\Entity\QuickScript;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Class QuickScriptYamlForm
 *
 * Creates the Quick Script config form from the YAML settings.
 *
 * @package Drupal\quickscript\Form
 */
class QuickScriptYamlForm extends FormBase {

  public function getFormId() {
    return 'QuickScript_yaml_form';
  }

  /**
   * Renders YAML-based form elements recursively.
   *
   * @param array $form
   * @param array $elements
   *
   * @return array
   */
  public function renderElements($form, array $elements = []) {
    foreach ($elements as $el_key => $el) {
      foreach ($el as $prop => $val) {
        if (strpos($prop, '_') === 0) {
          $prop = '#' . ltrim($prop, '_');
          $form[$el_key][$prop] = $val;
        }
        else {
          $form[$el_key] = array_merge($form[$el_key], $this->renderElements([], [$prop => $el[$prop]]));
        }
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, QuickScript $quickscript = NULL) {
    $form_state->set('quickscript', $quickscript);

    $form_yaml = $quickscript->getFormYaml();

    $settings = [];
    if (isset($form_yaml['settings'])) {
      $settings = $form_yaml['settings'];
      unset($form_yaml['settings']);
    }

    if (isset($settings['form_title'])) {
      $form['title'] = ['#markup' => '<h2>' . $settings['form_title'] . '</h2>'];
    }

    if (isset($settings['form_description'])) {
      $form['description'] = ['#markup' => $settings['form_description']];
    }

    $form['form'] = [];

    $form['form'] = $this->renderElements($form['form'], $form_yaml);

    $form['form']['#tree'] = TRUE;

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Run Script'),
    ];

    if (isset($settings['submit_value'])) {
      $form['actions']['submit']['#value'] = $settings['submit_value'];
    }

    return $form;
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
    /** @var QuickScript $quickscript */
    $quickscript = $form_state->get('quickscript');
    $opts['quickscript'] = $quickscript->id();
    $opts['qs'] = $form_state->getValue('form');
    $opts['qs']['form'] = 'true';
    $form_state->setRedirect('entity.quickscript.execute', $opts);
  }

}
