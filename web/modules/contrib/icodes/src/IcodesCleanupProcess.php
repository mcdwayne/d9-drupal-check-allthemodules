<?php

namespace Drupal\icodes;

use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use \Drupal\Component\Utility\UrlHelper;
use \Drupal\node\Entity\Node;
use \Drupal\file\Entity\File;

/**
 * Discovery and instantiation of default cron jobs.
 */
class IcodesCleanupProcess
{
    /**
     * @var \Drupal\Core\Extension\ModuleHandlerInterface
     */
    protected $moduleHandler;

    /**
     * @var \Drupal\Core\Config\ConfigFactoryInterface
     */
    protected $configFactory;

    /**
     * CronJobDiscovery constructor.
     *
     * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
     *   The module handler.
     * @param \Drupal\Core\Queue\QueueWorkerManagerInterface $queue_manager
     *   The queue manager.
     */
    public function __construct(ModuleHandlerInterface $module_handler,
                                ConfigFactoryInterface $config_factory)
    {
        $this->moduleHandler = $module_handler;
        $this->configFactory = $config_factory;
        $this->maxActive = $this->configFactory->get('icodes.settings')->get('icodes_cleanup_max_active');
        $this->maxExpired = $this->configFactory->get('icodes.settings')->get('icodes_cleanup_max_expired');
        $this->merchant_count = 0;
        $this->expired_count = 0;
        $this->active_count = 0;
        $this->category_id = [];
    }

    /**
     * Automatically discovers and creates default cron jobs.
     */
    public function processCleanup()
    {
        $start = date("d/m/y h:i:s");

        $this->cleanup_vouchers();

        $header = array(
            t('Start Time'),
            t('End Time'),
            t('Merchants Checked'),
            t('Expired Vouchers Removed'),
            t('Active Vouchers Removed'),
        );

        $build['icodes_cleanup_table'] = [
            '#type' => 'table',
            '#header' => $header,
            '#empty' => t('Cleanup failed to run.'),
            '#title' => t('Cleanup Summaryy')
        ];

        $row['start']['#markup'] = $start;
        $row['end']['#markup'] = date("d/m/y h:i:s");
        $row['merchant_count']['#markup'] = $this->merchant_count;
        $row['expired_count']['#markup'] = $this->expired_count;
        $row['active_count']['#markup'] = $this->active_count;

        $build['icodes_cleanup_table'][] = $row;

        return $build;
    }

    /**
     *
     */
    function cleanup_vouchers()
    {
        $query = \Drupal::entityQuery('node')->condition('type', 'merchant');
        $results = $query->execute();

        foreach ($results as $result) {
            $this->merchant_count++;
            $this->remove_expired_merchant_vouchers($result);
            $this->remove_active_merchant_vouchers($result);
        }
    }

    /**
     *
     */
    function remove_expired_merchant_vouchers($merchant_nid)
    {

        $query = \Drupal::entityQuery('node')
            ->condition('field_merchant_ref', $merchant_nid)
            ->condition('type', 'voucher')
            ->condition('field_voucher_ends', date("Y-m-d"), "<=")
            ->sort('field_voucher_ends', "desc");

        $results = $query->execute();

        if (count($results) > $this->maxExpired) {
            $i = 0;
            foreach ($results as $result) {
                if ($i >= $this->maxExpired) {
                    entity_delete_multiple("node", array($result));
                    $this->expired_count ++;
                }
                $i++;
            }
        }
    }

    /**
     *
     */
    function remove_active_merchant_vouchers($merchant_nid)
    {


        $query = \Drupal::entityQuery('node')
            ->condition('field_merchant_ref', $merchant_nid)
            ->condition('type', 'voucher')
            ->condition('field_voucher_ends', date("Y-m-d"), ">")
            ->sort('nid', "desc");

        $results = $query->execute();

        if (count($results) > $this->maxActive) {
            $i = 0;
            foreach ($results as $result) {
                if ($i >= $this->maxActive) {
                    //cant place at the end, to memeory intensive to bulk delete 1000 + nodes
                    entity_delete_multiple("node", array($result));
                    $this->active_count ++;
                }
                $i++;
            }
        }
    }
}