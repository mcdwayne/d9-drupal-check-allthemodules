<?php

namespace Drupal\themekey\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\themekey\RuleChainManagerTrait;

/**
 * Provides a form for administering the ThemeKey rule chain.
 */
class ThemeKeyRuleChainForm extends ConfigFormBase {

  use RuleChainManagerTrait;

  /**
   * Gets the ThemeKey Rule Chain manager.
   *
   * @return \Drupal\themekey\RuleChainManagerInterface
   *   The ThemeKey Rule Chain manager.
   */
  protected function getRuleChainManager() {
    if (!$this->ruleChainManager) {
      $this->ruleChainManager = \Drupal::service('themekey.rule_chain_manager');
      $this->ruleChainManager->setRuleChainConfig($this->config('themekey.rule_chain'));
    }

    return $this->ruleChainManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'themekey_rule_chain';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $ruleChainManager = $this->getRuleChainManager();

    if (!$form_state->isSubmitted()) {
      $ruleChainManager->rebuildChain();
    }

    $form['#title'] = $this->t('ThemeKey Rule Chain');

    $form['table'] = array(
      '#theme' => 'themekey_rule_chain_table',
      '#tree' => TRUE,
    );

    $depth = 0;
    $ruleChain = $ruleChainManager->getChain();
    foreach ($ruleChain as $ruleId => $ruleMetaData) {
      $form['table'][$ruleId] = array(
        '#type' => 'fieldset',
        '#title' => $ruleId,
      );

      $form['table'][$ruleId]['enabled'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Enabled'),
        '#default_value' => $ruleMetaData['enabled'],
      );

      $form['table'][$ruleId]['weight'] = array(
        '#type' => 'weight',
        '#title' => $this->t('Weight'),
        '#default_value' => $ruleMetaData['weight'],
        '#delta' => 100, // REVIEW Allows weight from -100 to 100. Is that enough?
      );

      $form['table'][$ruleId]['parent'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Parent'),
        '#default_value' => $ruleMetaData['parent'],
      );

      $form['table'][$ruleId]['depth'] = array(
        '#type' => 'value',
        '#value' => $ruleMetaData['depth'],
      );
    }

    $form['save'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save rule chain'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $chain = array();
    foreach (Element::children($form['table']) as $ruleId) {
      $parent = $form_state->getValue(array('table', $ruleId, 'parent'));
      $chain[$ruleId] = array(
        'weight' => $form_state->getValue(array('table', $ruleId, 'weight')),
        'parent' => $parent ? : NULL,
        'enabled' => $form_state->getValue(array('table', $ruleId, 'enabled')),
      );
    }

    $ruleChainManager = $this->getRuleChainManager();
    $ruleChainManager->setChain($chain);

    drupal_set_message($this->t('The rule chain has been saved.'));
  }
}
