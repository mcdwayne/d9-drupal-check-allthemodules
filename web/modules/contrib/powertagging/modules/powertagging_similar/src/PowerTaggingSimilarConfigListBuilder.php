<?php

/**
 * @file
 * Contains \Drupal\powertagging_similar\PowerTaggingSimilarConfigListBuilder.
 */

namespace Drupal\powertagging_similar;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\powertagging\Entity\PowerTaggingConfig;
use Drupal\powertagging_similar\Entity\PowerTaggingSimilarConfig;

class PowerTaggingSimilarConfigListBuilder extends ConfigEntityListBuilder
{
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = t('Title');
    $header['powertagging'] = t('PowerTagging Configuration');
    return $header + parent::buildHeader();
  }
  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var PowerTaggingSimilarConfig $entity */
    $powertagging_config = PowerTaggingConfig::load($entity->getPowerTaggingId());
    $row['title'] = new FormattableMarkup('<div class="semantic-connector-led" data-server-id="@connectionid" data-server-type="pp-server" title="@servicetitle"></div>@entitytitle', ['@connectionid' => $powertagging_config->getConnection()->id(), '@servicetitle' => t('Checking service'), '@entitytitle' => $entity->get('title')]);
    $row['powertagging'] = Link::fromTextAndUrl($powertagging_config->getTitle(), Url::fromRoute('entity.powertagging.edit_config_form', array('powertagging' => $powertagging_config->id())))->toString();

    return $row + parent::buildRow($entity);
  }

  public function buildOperations(EntityInterface $entity) {
    $build = array(
      '#type' => 'operations',
      '#links' => $this->getOperations($entity),
    );

    /*if (isset($build['#links']['edit'])) {
      $build['#links']['edit']['url'] = \Drupal\Core\Url::fromRoute('entity.powertagging_similar.edit_config_form', array('powertagging_similar' => $entity->id()));
    }*/

    /*$build['#links']['block'] = array(
      'title' => t('Go to block'),
      'url' => Url::fromRoute('entity.powertagging.clone_form', array('powertagging' => $entity->id())),
      'weight' => 1000,
    );*/

    return $build;
  }
}