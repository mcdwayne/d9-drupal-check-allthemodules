<?php

namespace Drupal\phpmetricsintegration\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Provides a listing of MetricsLog.
 */
class MetricsLogListBuilder extends ConfigEntityListBuilder
{

  /**
   * {@inheritdoc}
   */
    public function buildHeader()
    {
        $header['created'] = $this->t('Date & Time');
        $header['success_report'] = $this->t('Log');
        $header['analyzedby'] = $this->t('Analyzed By');
        $header['status'] = $this->t('Status');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity)
    {
        $row['created'] = $entity->getFormattedDate($entity->getCreated());
        $row['success_report'] = implode('<br />', \unserialize($entity->getSuccessReport()));
    
        /*$row['success_report']['data'] = [
          '#theme' => 'success_report',
          '#log' => \unserialize($entity->getSuccessReport())
        ];*/
        /**/
        $row['analyzedby']['data'] = [
        '#theme' => 'username',
        '#account' => $entity->getAnalyzedby(),
        ];
        $row['status'] = ($entity->getStatusCode()==0) ? 'Passed' : 'Failed';

        // You probably want a few more properties here...

        return $row + parent::buildRow($entity);
    }

    /**
     * Gets this list's default operations.
     *
     * @param \Drupal\Core\Entity\EntityInterface $entity
     *   The entity the operations are for.
     *
     * @return array
     *   The array structure is identical to the return value of
     *   self::getOperations().
     */
    public function getDefaultOperations(EntityInterface $entity)
    {
        $operations = parent::getDefaultOperations($entity);

        if ($entity->getStatusCode() == 0) {
            $linkView = 'http://';
            $linkView .= \Drupal::request()->getHost();
            $reportDir = $entity->getReportPath();
            $linkView .= '/sites/default/files/' . $reportDir;

            $operations['showreport'] = array(
            'title' => $this->t('View report'),
            'weight' => 10,
            'url' => Url::fromUri($linkView, ['attributes' => ['target' => '_blank']]),
            ); 
        }
        

        return $operations;
    }
}
