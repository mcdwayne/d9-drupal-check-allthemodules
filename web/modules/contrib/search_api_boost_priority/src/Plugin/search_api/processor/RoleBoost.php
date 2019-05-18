<?php

namespace Drupal\search_api_boost_priority\Plugin\search_api\processor;

use Drupal\comment\CommentInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\node\NodeInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\user\Entity\User;
use Drupal\user\RoleInterface;

/**
 * Adds a boost to indexed items based on User Role.
 *
 * @SearchApiProcessor(
 *   id = "search_api_boost_priority_role",
 *   label = @Translation("Role specific boosting"),
 *   description = @Translation("Adds a boost to indexed items based on User Role."),
 *   stages = {
 *     "preprocess_index" = 0,
 *   }
 * )
 */
class RoleBoost extends ProcessorPluginBase implements PluginFormInterface {

  use PluginFormTrait;

  /**
   * The available boost factors.
   *
   * @var string[]
   */
  protected static $boostFactors = [
    '0.0' => '0.0',
    '0.1' => '0.1',
    '0.2' => '0.2',
    '0.3' => '0.3',
    '0.5' => '0.5',
    '0.8' => '0.8',
    '1.0' => '1.0',
    '2.0' => '2.0',
    '3.0' => '3.0',
    '5.0' => '5.0',
    '8.0' => '8.0',
    '13.0' => '13.0',
    '21.0' => '21.0',
  ];

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'boost_table' => [
        'weight' => '0.0',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $formState) {
    $form['boost_table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Role'),
        $this->t('Boost'),
      ],
    ];

    $masterRoles = user_roles();
    $roles = array_map(function (RoleInterface $role) {
      return Html::escape($role->label());
    }, $masterRoles);

    // Make a dummy array to add custom weight.
    foreach ($roles as $roleId => $roleName) {
      if (isset($this->configuration['boost_table'][$roleId]['weight'])) {
        $weight = $this->configuration['boost_table'][$roleId]['weight'];
      }
      elseif (isset($this->configuration['boost_table']['weight'])) {
        $weight = $this->configuration['boost_table']['weight'];
      }

      $roleWeight[$roleId]['id'] = $roleId;
      $roleWeight[$roleId]['name'] = $roleName;
      $roleWeight[$roleId]['weight'] = $weight;
    }

    // Sort weights.
    uasort($roleWeight, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);

    // Loop over each role and create a form row.
    foreach ($roleWeight as $roleId => $role) {
      $weight = $role['weight'];
      $roleName = $role['name'];

      // Table columns containing raw markup.
      $form['boost_table'][$roleId]['label']['#plain_text'] = $roleName;

      // Weight column element.
      $form['boost_table'][$roleId]['weight'] = [
        '#type' => 'select',
        '#title' => t('Weight for @title', ['@title' => $roleName]),
        '#title_display' => 'invisible',
        '#default_value' => $weight,
        '#options' => static::$boostFactors,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $masterRoles = user_roles();
    $roles = array_map(function (RoleInterface $role) {
      return Html::escape($role->label());
    }, $masterRoles);

    foreach ($roles as $roleId => $roleName) {
      if (!empty($values['boost_table'][$roleId]['weight'])) {
        $weight = $values['boost_table'][$roleId]['weight'];
        if ($weight === '') {
          unset($values['boost_table'][$roleId]);
        }
      }
    }

    $form_state->setValues($values);
    $this->setConfiguration($values);
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array $items) {
    foreach ($items as $item) {
      $entityTypeId = $item->getDatasource()->getEntityTypeId();

      // TODO Extend for other entities.
      switch ($entityTypeId) {
        case 'user':
          $user = $item->getOriginalObject()->getValue();
          $boost = $this->getRoleBoostFromUser($user);
          break;

        case 'node':
          // Get the node object.
          $node = $this->getNode($item->getOriginalObject());

          // Get the user associated with this node.
          $user = User::load($node->getOwnerId());
          $boost = $this->getRoleBoostFromUser($user);
          break;
      }

      $item->setBoost($boost);
    }
  }

  /**
   * Retrieves the node related to an indexed search object.
   *
   * Will be either the node itself, or the node the comment is attached to.
   *
   * @param \Drupal\Core\TypedData\ComplexDataInterface $item
   *   A search object that is being indexed.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The node related to that search object.
   */
  protected function getNode(ComplexDataInterface $item) {
    $item = $item->getValue();
    if ($item instanceof CommentInterface) {
      $item = $item->getCommentedEntity();
    }
    if ($item instanceof NodeInterface) {
      return $item;
    }

    return NULL;
  }

  /**
   * Retrieves the boost related to a User Role.
   *
   * @param \Drupal\user\Entity\User $user
   *   User Obj.
   *
   * @return float
   *   Boost Value.
   */
  protected function getRoleBoostFromUser(User $user) {
    // Get user roles.
    $userRoles = $user->getRoles();
    $boosts = $this->configuration['boost_table'];

    // Construct array for role sorting.
    foreach ($userRoles as $roleId) {
      $roleWeights[] = (double) $boosts[$roleId]['weight'];
    }

    // Get highest weight for this user.
    $boost = max($roleWeights);
    return $boost;
  }

}
