<?php

namespace Drupal\drd\Entity\ViewsData;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Domain entities.
 */
class Domain extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['drd_domain']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('DRD Domain'),
      'help' => $this->t('DRD Domain ID.'),
    ];

    $data['drd_domain']['name']['field']['id'] = 'drd_domain_name';
    $data['drd_domain']['pingstatus']['field'] = [
      'title' => $this->t('Latest ping status'),
      'help' => $this->t('Pseudo field that displays the latest ping status'),
      'id' => 'drd_ping_status',
    ];

    $data['drd_domain']['status']['filter']['label'] = $this->t('Active');
    $data['drd_domain']['status']['filter']['type'] = 'yes-no';

    $data['drd_domain']['installed']['filter']['label'] = $this->t('Installed');
    $data['drd_domain']['installed']['filter']['type'] = 'yes-no';

    $data['drd_domain']['secure']['filter']['label'] = $this->t('SSL');
    $data['drd_domain']['secure']['filter']['type'] = 'yes-no';

    $data['drd_domain']['status_agg']['field'] = [
      'title' => $this->t('DRD Domain status'),
      'help' => $this->t('Show the status of the domain with colored icons'),
      'id' => 'drd_domain_status_agg',
    ];
    $data['drd_domain']['secure']['field']['id'] = 'drd_domain_secure';
    $data['drd_domain']['drd_entity_actions']['field'] = [
      'title' => $this->t('Actions'),
      'help' => $this->t('Add a form element that lets you run operations on multiple domains.'),
      'id' => 'drd_entity_actions',
    ];

    return $data;
  }

}
