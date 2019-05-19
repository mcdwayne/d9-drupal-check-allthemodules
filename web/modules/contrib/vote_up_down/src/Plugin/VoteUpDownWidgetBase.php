<?php

namespace Drupal\vud\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Defines a plugin base implementation that corresponding plugins will extend.
 *
 * @todo Inject used classes.
 */
abstract class VoteUpDownWidgetBase extends PluginBase implements VoteUpDownWidgetInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getWidgetId() {
    return $this->getPluginDefinition()['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getWidgetTemplate() {
    return $this->getPluginDefinition()['widget_template'];
  }

  /**
   * {@inheritdoc}
   */
  public function alterTemplateVariables(&$variables) {
    // Nothing by default.
  }

  /**
   * {@inheritdoc}
   */
  public function getWidgetTemplateVars($base_path, &$variables) {
    $variables['#template_path'] = $base_path . '/widgets/' . $this->getWidgetId() . '/widget.html.twig';
    array_push($variables['#attached']['library'], 'vud/' . $this->getWidgetId());
    return $variables;
  }

  /**
   * {@inheritdoc}
   *
   * @fixme Do not use camelcase on variables, and fix coding standards.
   */
  public function build($entity) {
    $vote_storage = \Drupal::service('entity.manager')->getStorage('vote');
    $currentUser =  \Drupal::currentUser();
    $entityTypeId = $entity->getEntityTypeId();
    $entityId = $entity->id();

    $module_handler = \Drupal::service('module_handler');
    $module_path = $module_handler->getModule('vud')->getPath();

    // @todo: Implement voting API result functions instead of custom queries.
    $up_points = \Drupal::entityQuery('vote')
      ->condition('value', 1)
      ->condition('entity_type', $entityTypeId)
      ->condition('entity_id', $entityId)
      ->count()
      ->execute();
    $down_points = \Drupal::entityQuery('vote')
      ->condition('value', -1)
      ->condition('entity_type', $entityTypeId)
      ->condition('entity_id', $entityId)
      ->count()
      ->execute();

    $points = $up_points - $down_points;
    $unsigned_points = $up_points + $down_points;

    $widget_name = $this->getWidgetId();

    $variables = [
      '#theme' => 'vud_widget',
      '#widget_template' => $widget_name,
      '#entity_id' => $entityId,
      '#entity_type_id' => $entityTypeId,
      '#base_path' => $module_path,
      '#widget_name' => $widget_name,
      '#up_points' => $up_points,
      '#down_points' => $down_points,
      '#points' => $points,
      '#unsigned_points' => $unsigned_points,
      '#vote_label' => 'votes',
      '#widget_instance_id' => "vud-widget-$entityTypeId-$entityId",
      '#attached' => [
        'library' => [
          'vud/common',
        ]
      ],
    ];

    $this->getWidgetTemplateVars($module_path, $variables);

    $up_access = $down_access = $reset_access = FALSE;
    if (vud_can_vote($currentUser)) {
      $variables['#show_links'] = TRUE;
      $variables['#link_class_up'] = 'vud-link-up';
      $variables['#link_class_down'] = 'vud-link-down';
      $variables['#class_up'] = 'up';
      $variables['#class_down'] = 'down';
      $vote_type = \Drupal::config('vud.settings')->get('tag', 'vote');
      $user_votes_current_entity = $vote_storage->getUserVotes(
        $currentUser->id(),
        $vote_type,
        $entityTypeId,
        $entityId
      );
      if ($user_votes_current_entity != NULL) {
        $user_vote_id = (int)array_values($user_votes_current_entity)[0];
        $user_vote = $vote_storage->load($user_vote_id)->getValue();
      }
      else {
        $user_vote = 0;
      }
      $up_access = $user_vote <= 0;
      $down_access = $user_vote >= 0;
      $reset_access = ($user_vote != 0) && $currentUser->hasPermission("reset vote up/down votes");
    }

    if ($up_access) {
      $variables['#show_up_as_link'] = TRUE;
      $variables['#link_up'] = Url::fromRoute('vud.vote', [
        'entity_type_id' => $entityTypeId,
        'entity_id' => $entityId,
        'vote_value' => 1,
        'widget_name' => $widget_name,
        'js' => 'nojs',
      ]);
      $variables['#class_up'] .= ' active';
    }
    else {
      $variables['#class_up'] .= ' inactive';
    }
    if ($down_access) {
      $variables['#show_down_as_link'] = TRUE;
      $variables['#link_down'] = Url::fromRoute('vud.vote', [
        'entity_type_id' => $entityTypeId,
        'entity_id' => $entityId,
        'vote_value' => -1,
        'widget_name' => $widget_name,
        'js' => 'nojs',
      ]);
      $variables['#class_down'] .= ' active';
    }
    else {
      $variables['#class_down'] .= ' inactive';
    }
    if ($reset_access) {
      $variables['#show_reset'] = TRUE;
      $variables['#link_reset'] = Url::fromRoute('vud.reset', [
        'entity_type_id' => $entityTypeId,
        'entity_id' => $entityId,
        'widget_name' => $widget_name,
        'js' => 'nojs',
      ]);
      $variables += [
        '#reset_long_text' => $this->t('Reset your vote'),
        '#reset_short_text' => $this->t('(reset)'),
        '#link_class_reset' => 'reset',
      ];
    }

    // Let widgets change variables at the end.
    $this->alterTemplateVariables($variables);

    return $variables;
  }

}
