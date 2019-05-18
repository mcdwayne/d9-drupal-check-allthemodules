<?php

namespace Drupal\drd\Plugin\views\field;

use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\drd\Entity\DomainInterface;
use Drupal\drd\Entity\ProjectInterface;
use Drupal\drd\Entity\ReleaseInterface;
use Drupal\views\Plugin\views\field\Standard;
use Drupal\views\ResultRow;

/**
 * A handler to display the project name.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("drd_project_name")
 */
class ProjectName extends Standard {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    $this->realField = 'label';
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $locked = FALSE;
    $hacked = FALSE;
    $project = $values->_entity;
    if ($project instanceof ReleaseInterface) {
      $locked = $project->isLocked();
      if (!$locked) {
        foreach ($values->_relationship_entities as $relationship_entity) {
          if ($relationship_entity instanceof DomainInterface) {
            $locked = $relationship_entity->getCore()->isReleaseLocked($project);
            $hacked = $relationship_entity->getCore()->isReleaseHacked($project);
          }
        }
      }
      $project = $project->getMajor()->getProject();
    }

    if ($project instanceof ProjectInterface) {
      $label = \Drupal::linkGenerator()
        ->generate($project->getLabel(), new Url('entity.drd_project.canonical', ['drd_project' => $project->id()]));
      $link = \Drupal::linkGenerator()
        ->generate($this->t('Link'), $project->getProjectLink());
      $output = '<div class="drd-icon link">' . $link . '</div>' . $label . '<span class="name">' . $project->getName() . '</span>';
      if ($locked) {
        $output .= ' <span class="drd-locked-info" title="locked">locked</span>';
      }
      if ($hacked) {
        $output .= ' <span class="drd-hacked-info" title="hacked">hacked</span>';
      }
    }
    else {
      $output = $this->getValue($values);
    }
    return Markup::create('<div class="drd-project-name">' . $output . '</div>');
  }

}
