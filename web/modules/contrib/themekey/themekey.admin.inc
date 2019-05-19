<?php


use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Render\Element;
use Drupal\themekey\Entity\ThemeKeyRule;

/**
 * Returns HTML for ThemeKey Rule Chain administration form.
 *
 * @param array $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @ingroup themeable
 */
function theme_themekey_rule_chain_table($variables) {
  $destination = drupal_get_destination();

  $form = $variables['form'];

  $header = array(t('ThemeKey Rule'), t('Weight'), t('Parent'), t('Enabled'), t('Operations'));
  $rows = array();

  foreach (Element::children($form) as $key) {
    $ruleId = $form[$key]['#title'];

    $form[$key]['weight']['#attributes']['class'] = array('rule-weight');
    $form[$key]['parent']['#attributes']['class'] = array('rule-parent');
    $form[$key]['rule_id'] = array(
      '#type' => 'hidden',
      '#disabled' => TRUE,
      '#value' => $ruleId,
      '#attributes' => array (
        'class' => array('rule-id'),
      ),
    );

    $indentation = array('#theme' => 'indentation', '#size' => $form[$key]['depth']['#value']);
    $data = array(
      SafeMarkup::set(
        drupal_render($indentation) .
        drupal_render($form[$key]['rule_id']) .
        ThemeKeyRule::load($ruleId)->toString()
      ),
      drupal_render($form[$key]['weight']),
      drupal_render($form[$key]['parent']),
      drupal_render($form[$key]['enabled']),
    );

    $links['edit'] = array(
      'title' => t('Edit'),
      'route_name' => 'themekey_rule.edit',
      'route_parameters' => array('themekey_rule' => $ruleId),
      'query' => $destination,
    );

    $data[] = array(
      'data' => array(
        '#type' => 'operations',
        '#links' => $links,
      ),
    );

    $row = array('data' => $data);
    if (isset($form[$key]['#attributes'])) {
      $row = array_merge($row, $form[$key]['#attributes']);
    }
    $row['class'][] = 'draggable';
    $rows[] = $row;
  }

  $table = array(
    '#type' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#attributes' => array(
      'id' => 'themekey-rule-chain-table',
    ),
    '#empty' => t('No ThemeKey rules available.'),
    '#tabledrag' => array(
      array(
        'action' => 'match',
        'relationship' => 'parent',
        'group' => 'rule-parent',
        'subgroup' => 'rule-parent',
        'source' => 'rule-id',
        'hidden' => TRUE,
      ),
      array(
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'rule-weight',
        'hidden' => TRUE,
      ),
    ),
  );

  return drupal_render($table);
}
