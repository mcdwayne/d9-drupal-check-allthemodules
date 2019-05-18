<?php

/**
 * @file
 * Contains \Drupal\pp_taxonomy_manager\PPTaxonomyManagerConfigListBuilder.
 */

namespace Drupal\pp_taxonomy_manager;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\pp_taxonomy_manager\Entity\PPTaxonomyManagerConfig;
use Drupal\taxonomy\Entity\Vocabulary;

class PPTaxonomyManagerConfigListBuilder extends ConfigEntityListBuilder
{
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = t('Title');
    $header['server'] = t('PoolParty GraphSearch Server');
    $header['project'] = t('Selected project');
    $header['taxonomies'] = t('Interconnected taxonomies');
    return $header + parent::buildHeader();
  }
  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var PPTaxonomyManagerConfig $entity */

    $row['title'] = new FormattableMarkup('<div class="semantic-connector-led" data-server-id="@connectionid" data-server-type="pp-server" title="@servicetitle"></div>@entitytitle', ['@connectionid' => $entity->getConnection()->id(), '@servicetitle' => t('Checking service'), '@entitytitle' => $entity->get('title')]);
    $row['server'] = Link::fromTextAndUrl($entity->getConnection()->getTitle(), Url::fromUri($entity->getConnection()->getUrl()))->toString();

    // Get the project label.
    $settings = $entity->getConfig();
    if ($settings['root_level'] != 'project') {
      $project_id = $entity->getProjectID();
      $connection_config = $entity->getConnection()->getConfig();
      $project_label = t('Project label not found');
      if (isset($connection_config['projects'])) {
        foreach ($connection_config['projects'] as $project) {
          if ($project['id'] == $project_id) {
            $project_label = $project['title'];
            break;
          }
        }
      }
    }
    else {
      $project_label = '-';
    }
    $row['project'] = $project_label;

    // Create the list of the interconnected taxonomies.
    $vocabularies = Vocabulary::loadMultiple(array_keys($settings['taxonomies']));
    $taxonomies = array();
    /** @var Vocabulary $vocabulary */
    foreach ($vocabularies as $vocabulary) {
      $taxonomies[] = Link::fromTextAndUrl($vocabulary->label(), Url::fromRoute('entity.taxonomy_vocabulary.edit_form', array('taxonomy_vocabulary' => $vocabulary->id())))->toString();
    }
    $taxonomies = empty($taxonomies) ? '<div class="semantic-connector-italic">' . t('not yet set') . '</div>' : '<div class="item-list"><ul><li>' . implode('</li><li>', $taxonomies) . '</li></ul></div>';
    $row['taxonomies'] = new FormattableMarkup($taxonomies, array());

    return $row + parent::buildRow($entity);
  }

  public function buildOperations(EntityInterface $entity) {
    $build = array(
      '#type' => 'operations',
      '#links' => $this->getOperations($entity),
    );

    if (isset($build['#links']['edit'])) {
      $build['#links']['edit']['url'] = \Drupal\Core\Url::fromRoute('entity.pp_taxonomy_manager.edit_config_form', array('pp_taxonomy_manager' => $entity->id()));
    }

    return $build;
  }
}