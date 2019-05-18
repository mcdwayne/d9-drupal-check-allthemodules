<?php

namespace Drupal\httpbl;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the host entity type.
 */
class HostViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Enables adding bulk operations to Views rendered Host list.
    $data['httpbl_host']['host_bulk_form'] = array(
      'title' => $this->t('Host operations bulk form'),
      'help' => $this->t('Add a form element that lets you run operations on multiple hosts.'),
      'field' => array(
        'id' => 'host_bulk_form',
      ),
    );

    // Set up views field to be handled by Cron Expire date field formatter.
    $data['httpbl_host']['expire']['field']['id'] = 'host_cron_expire';
    $data['httpbl_host']['expire']['title'] = $this->t('Cron Expire Date');
    $data['httpbl_host']['host_ip']['help'] = $this->t('When this host will expire and be deleted (via cron).');

    // Set up views field to be handled by Project link formatter.
    $data['httpbl_host']['host_ip']['field']['id'] = 'host_ip';
    $data['httpbl_host']['host_ip']['title'] = $this->t('Host IP with Project link');
    $data['httpbl_host']['host_ip']['help'] = $this->t('Host IP with an optional link to the host\'s IP profile on Project Honey Pot.');

    // Set up views field to be handled by Project link formatter.
    $data['httpbl_host']['source']['field']['id'] = 'host_source';
    $data['httpbl_host']['source']['title'] = $this->t('Source field linked');
    $data['httpbl_host']['source']['help'] = $this->t('Evaluation source with host profile when originally sourced.');

    // Set up status field to be handled by Status Enhanced formatter.
    $data['httpbl_host']['host_status']['field']['id'] = 'status_enhanced';
    $data['httpbl_host']['host_status']['title'] = $this->t('Status enhanced');
    $data['httpbl_host']['host_status']['help'] = $this->t('Shows the definitions of the integer status values.');

    // Define IP-to-IP relationship to Ban module (ban_ip table).
    $data['httpbl_host']['ban_relationship'] = array(
      'title' => t('Add relationship to Ban_ip IPs'),
      'help' => t('Finds banned IPs that are also blacklisted Hosts.'),
      'relationship' => array(
        'base' => 'ban_ip',
        'base field' => 'ip',
        'field' => 'host_ip',
        'id' => 'standard',
        'label' => t('Host IPs in Ban'),
      ),
    );

    return $data;
  }

}
