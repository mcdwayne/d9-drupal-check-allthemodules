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
class IcodesMerchantProcess
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
        $this->localUrl = "";
        $this->highResImageCount = 0;
        $this->merchant_count = 0;
        $this->merchant_skipped = 0;
        $this->message = "";
        $this->vclogos = "http://www.vclogos.co.uk/logo.php";
        $this->categories = [];
        $this->limit = 0;
        $this->max = 100;
    }

    /**
     * Automatically discovers and creates default cron jobs.
     */
    public function processFeed()
    {


        $merchants_added = 0;
        $continue = true;

        $row = [];
        $start = date("d/m/y h:i:s");
        $file_downloaded = "No";

        if ($this->configFactory->get('icodes.settings')->get('icodes_feeds_merchant_enable')) {

            $query = \Drupal::database()->select('taxonomy_term__field_category_id',
                'cat_id');
            $query->fields('cat_id', ['entity_id', 'field_category_id_value']);
            $categories = $query->execute()->fetchAllAssoc('field_category_id_value');

            $this->categories = $categories;

            $this->fetchXMLFile($continue);

            //no error mssages so crack on
            if ($this->message == "") {
                $file_downloaded = "Yes";
            }

            if ($continue === true) {
                $this->processXMLFile($merchants_added);
            }
        } else {
            drupal_set_message("Feed disabled by icodes", 'error');
            $this->message = "Feed disabled by icodes";
        }

        $header = array(
            t('Start Time'),
            t('End Time'),
            t('File Downloaded'),
            t('Merchants Found'),
            t('Duplicate merchants skipped'),
            t('New merchants imported'),
            t('Messages')
        );

        $build['icodes_merchant_feed_table'] = [
            '#type' => 'table',
            '#header' => $header,
            '#empty' => t('Merchant feed failed to run.'),
        ];

        $row['start']['#markup'] = $start;
        $row['end']['#markup'] = date("d/m/y h:i:s");
        $row['download']['#markup'] = $file_downloaded;
        $row['feed_merchants']['#markup'] = $this->merchant_count;
        $row['skipped_merchants']['#markup'] = $this->merchant_skipped;
        $row['new_merchants']['#markup'] = $merchants_added;
        $row['message']['#markup'] = $this->message;

        $build['icodes_merchant_feed_table'][] = $row;
        $build['#title'] = t('Merchant feed summary');

        return $build;
    }

    /**
     * import fetchXMLFile
     */
    public function fetchXMLFile(&$continue = true, &$message = "")
    {

        if ($this->configFactory->get('icodes.settings')->get('external_mode')) {
            if (!$this->configFactory->get('icodes.settings')->get('process_directory')) {
                $continue = false;
                $this->message = t("Directory not set for XML Files");
                return;
            } else if (!$this->configFactory->get('icodes.settings')->get('merchant_feed_url')) {
                $continue = false;
                $this->message = t("merchant Feed URL not set");
                return;
            } else {

                $filename = "merchants.xml";
                $base_local_url = $this->configFactory->get('icodes.settings')->get('process_directory');
                $this->local_url = $base_local_url."/".$filename;

                //external generate url
                $icodes_url = $this->configFactory->get('icodes.settings')->get('merchant_feed_url');
                $directory = file_stream_wrapper_uri_normalize($base_local_url);

                if (file_prepare_directory($directory, FILE_CREATE_DIRECTORY)) {

                    //set real path and inport files
                    $this->local_url = drupal_realpath($base_local_url)."/".$filename;
                    $ch = curl_init($icodes_url);
                    $fp = fopen($this->local_url, "w");
                    if ($fp !== false) {
                        curl_setopt($ch, CURLOPT_FILE, $fp);
                        curl_setopt($ch, CURLOPT_HEADER, 0);
                        curl_exec($ch);
                        curl_close($ch);
                        fclose($fp);
                    } else {
                        $continue = false;
                        $this->message = t("Could not a write to the file system");
                        return;
                    }
                } else {
                    $continue = false;
                    $this->message = t("Could not create directory on the file system");
                    return;
                }
            }
        } else {
            $continue = true;
            $this->message = t("Internal mode: skipping the download of the XML");
            return;
        }
    }

    /**
     *
     */
    public function processXMLFile(&$merchants_added)
    {

        $merchants_added = 0;

        $this->local_url = $filename = "merchants.xml";
        $base_local_url = $this->configFactory->get('icodes.settings')->get('process_directory');
        $this->local_url = drupal_realpath($base_local_url)."/".$filename;

        if (!$xml = simplexml_load_file($this->local_url, 'SimpleXMLElement',
            LIBXML_NOCDATA)) {
            $this->message = t("Local file not found");
            return;
        }


        foreach ($xml->item as $merchant) {
            if ($this->limit < $this->max) {
                //check if merchant exists, if not add it
                $existing_merchant = $this->checkMerchantExists($merchant);
                if (count($existing_merchant) == 0) {
                    //make new merchant
                    self::createNewMerchant($merchant);
                    $merchants_added++;
                } else {
//                    self::checkMerchantLogo($existing_merchant, $merchant);
                }
            }
        }

        return;
    }

    /**
     *
     * @param type $item
     * @return type
     */
    public function checkMerchantExists($merchant)
    {

        $this->merchant_count++;

        $query = \Drupal::entityQuery('node')
            ->condition('field_merchant_id', trim($merchant->merchant_id))
            ->condition('field_merchant_network', $merchant->network);

        $results = $query->execute();

        /*
         * check commission junction manually imported
         */
        if (count($results) == 0 && $merchant->network == "commission_junction") {
            $query = \Drupal::entityQuery('node')
                ->condition('field_merchant_network', "commission_junction")
                ->condition('field_merchant_id', $merchant->merchant_id);

            $results = $query->execute();
        }

        //check for linked merchants - (same website on other merchants)
        if (count($results) == 0) {

            $parse = parse_url($merchant->merchant_url);
            $clean_url = str_replace('www.', '', $parse['host']);

            $query = \Drupal::entityQuery('node')
                ->condition('field_merchant_url', $clean_url);

            $results = $query->execute();
            if (count($results) > 0) {
                $this->merchant_skipped++;
            }
        }
        return $results;
    }

    /**
     *
     * @param type $item
     */
    public function createNewMerchant($merchant)
    {

        //skip if loading with high res images
        if ($this->highResImageCount <= $this->max) {

            $categories = explode(" ", $merchant->category);
            $node_categories = array();

            foreach ($categories as $cat) {
                if (isset($this->categories[$cat])) {
                    $cat_id = $this->categories[$cat]->entity_id;
                    $node_categories[] = array("target_id" => $cat_id);
                }
            }

            $parse = parse_url($merchant->merchant_url);
            $clean_url = str_replace('www.', '', $parse['host']);
            $merchant_urls[] = array('value' => $clean_url);

            $node_data = [
                'type' => 'merchant',
                'title' => $merchant->merchant,
                'field_merchant_id' => [
                    'value' => $merchant->merchant_id,
                ],
                'field_merchant_url' => $merchant_urls,
                'field_icodes_category_term' => $node_categories,
                'field_merchant_icode_status' => [
                    'value' => $merchant->relationship,
                ],
                'field_merchant_affiliate_url' => [
                    'value' => $merchant->affiliate_url,
                ],
                'field_merchant_network' => [
                    'value' => $merchant->network,
                ],
                'field_merchant_icid' => [
                    'value' => $merchant->icid,
                ],
                'field_merchant_rating' => [
                    'status' => '1',
                ],
            ];

            //check publish state
            if ($this->configFactory->get('icodes.settings')->get('icodes_merchant_auto_publish')) {
                $node_data['status'] = true;
            } else {
                $node_data['status'] = false;
            }

            //set merchant image
            $file_id = self::setMerchantImage($merchant);
            if ($file_id != "") {
                $node_data['field_merchant_image'] = array('target_id' => $file_id);
            }

            //create node
            $node = Node::create($node_data);
            $node->save();
        } else {
            $this->message = t("Loading high res merchants can only batch import $this->max at a time. Please run cron again to import more.");
        }
    }

    /**
     * 
     * @param type $node_data
     * @return type
     */
    function setMerchantImage($merchant)
    {
        $icon_path = $this->configFactory->get('icodes.settings')->get('icodes_merchant_images_directory');
        $image_directory = "standard_logos";

        //load high res logos if being paid for
        if ($this->configFactory->get('icodes.settings')->get('icodes_merchant_high_res')
            != "") {
            $image_directory = "high_res_logos";
            $subscriptionId = $this->configFactory->get('icodes.settings')->get('icodes_merchant_high_res');
            $url = $this->vclogos."?subid=".$subscriptionId."&imgid=".$merchant->icid->__toString();
            $filename = $merchant->icid."_logo.gif";
        } else {
            $url = $merchant->merchant_logo_url->__toString;
            $filename = $merchant->merchant_id."_".basename($merchant->merchant_logo_url);
        }

        //check if merchant iamge already exists
        $base_local_url = $this->configFactory->get('icodes.settings')->get('icodes_merchant_images_directory');
        $filepath = $base_local_url."/".$image_directory."/".$filename;

        $data = false;

        if (file_exists($filepath) && filesize($filepath) > 0) {
            $data = file_get_contents(UrlHelper::stripDangerousProtocols(drupal_realpath($base_local_url)."/".$image_directory."/".$filename));
        } else {
            //load external image
            $this->highResImageCount += 1;

            //only request $this->max at a time to stop api locking you out
            if ($this->highResImageCount <= $this->max) {
                $data = file_get_contents(UrlHelper::stripDangerousProtocols($url));
            }
        }

        //skip missing images$filepath
        if ($data === false) {
            if ($this->configFactory->get('icodes.settings')->get('icodes_merchant_high_res')
                != "") {
                $this->message = t("Image skipped because API key is wrong, or feed is down.");
            } else {
                $this->message = t("File could not be loaded.");
            }
        } else {
            //make sure directory is readable
            if (file_prepare_directory($icon_path, FILE_CREATE_DIRECTORY)) {
                $filepath = $this->configFactory->get('icodes.settings')->get('icodes_merchant_images_directory')."/".$image_directory."/".$filename;
                $file = file_save_data($data, $filepath, FILE_EXISTS_REPLACE);
//                $node_data['field_merchant_image'] = array('target_id' => $file->id());
                return $file->id();
            } else {
                $this->message = t("File could not be saved.");
            }
        }

        return "";
    }
}