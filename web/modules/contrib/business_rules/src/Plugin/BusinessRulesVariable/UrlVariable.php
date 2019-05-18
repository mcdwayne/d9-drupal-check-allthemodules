<?php

namespace Drupal\business_rules\Plugin\BusinessRulesVariable;

use Drupal\business_rules\Entity\Variable;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesVariablePlugin;
use Drupal\business_rules\VariableObject;
use Drupal\business_rules\VariablesSet;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;

/**
 * A variable representing the current url.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesVariable
 *
 * @BusinessRulesVariable(
 *   id = "url_variable",
 *   label = @Translation("Current Url"),
 *   group = @Translation("System"),
 *   description = @Translation("Variable the current url. Each part of the url can be used as {{variable_id->n}}, where n = the part number; starting from 0. ex. /admin/workflow/business_rules - 1 -> admin; 2 -> workflow; 3 -> business_rules"),
 *   isContextDependent = FALSE,
 *   hasTargetEntity = FALSE,
 *   hasTargetBundle = FALSE,
 * )
 */
class UrlVariable extends BusinessRulesVariablePlugin {

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {
    $settings['help'] = [
      '#type'   => 'markup',
      '#markup' => t('You only need one url variable in your site and this variable is created during the module installation. There is no necessity to create another one.'),
    ];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function changeDetails(Variable $variable, array &$row) {
    // Show a link to a modal window which variable description.
    $header = [
      'variable' => t('Variable'),
      'field'    => t('Field'),
      'type'     => t('Type'),
    ];

    $url    = $_SERVER['REQUEST_URI'];
    $fields = explode('/', $url);
    unset($fields[0]);

    $rows   = [];
    $rows[] = [
      'variable' => ['data' => ['#markup' => '{{' . $variable->id() . '}}']],
      'field'    => ['data' => ['#markup' => $url]],
      'type'     => ['data' => ['#markup' => t('String')]],
    ];
    foreach ($fields as $key => $value) {
      $rows[] = [
        'variable' => ['data' => ['#markup' => '{{' . $variable->id() . '->' . $key . '}}']],
        'field'    => ['data' => ['#markup' => $value]],
        'type'     => ['data' => ['#markup' => t('String')]],
      ];
    }

    $content['description'] = [
      '#type'   => 'markup',
      '#markup' => t('As an example, the current Url would return the following values:'),
    ];

    $content['variable_fields'] = [
      '#type'   => 'table',
      '#rows'   => $rows,
      '#header' => $header,
      '#sticky' => TRUE,
    ];

    $keyvalue = $this->util->getKeyValueExpirable('url_variable');
    $keyvalue->set('url_variable.' . $variable->id(), $content);

    $details_link = Link::createFromRoute(t('Click here to see the entity fields'),
      'business_rules.ajax.modal',
      [
        'method'     => 'nojs',
        'title'      => t('Entity fields'),
        'collection' => 'url_variable',
        'key'        => 'url_variable.' . $variable->id(),
      ],
      [
        'attributes' => [
          'class' => ['use-ajax'],
        ],
      ]
    )->toString();

    $row['description']['data']['#markup'] .= '<br>' . $details_link;

  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array &$form, FormStateInterface $form_state) {
    unset($form['variables']);
    unset($form['tokens']);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(Variable $variable, BusinessRulesEvent $event) {
    $url            = $_SERVER['REQUEST_URI'];
    $variableObject = new VariableObject($variable->id(), $url, $variable->getType());
    $variableSet    = new VariablesSet();
    $variableSet->append($variableObject);

    $parts = explode('/', $url);

    foreach ($parts as $key => $part) {
      $variableObject = new VariableObject($variable->id() . '->' . $key, $part, $variable->getType());
      $variableSet->append($variableObject);
    }

    return $variableSet;
  }

}
