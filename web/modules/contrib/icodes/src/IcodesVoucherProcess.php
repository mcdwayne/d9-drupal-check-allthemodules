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
class IcodesVoucherProcess
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
        $this->voucher_count = 0;
        $this->voucher_skipped = 0;
        $this->message = "";
        $this->merchant_missing = 0;
        $this->vouchers_added = 0;
        $this->pageCount = 1;
        $this->pageSize = 50;
        $this->maxPageCount = 500;
        $this->finished = false;
        $this->categories = array();
    }

    /**
     * Automatically discovers and creates default cron jobs.
     */
    public function processFeed()
    {


        $continue = true;

        $row = [];
        $start = date("d/m/y h:i:s");
        $file_downloaded = "No";

        if ($this->configFactory->get('icodes.settings')->get('icodes_feeds_voucher_enable')) {

            $query = \Drupal::database()->select('taxonomy_term__field_category_id',
                'cat_id');
            $query->fields('cat_id', ['entity_id', 'field_category_id_value']);
            $categories = $query->execute()->fetchAllAssoc('field_category_id_value');

            $this->categories = $categories;

            while ($this->finished == false) {

                $this->fetchXMLFile($continue);

                //no error mssages so crack on
                if ($this->message == "") {
                    $file_downloaded = "Yes";
                }

                if ($continue === true) {
                    $this->processXMLFile();
                }
            }
        } else {
            drupal_set_message("Feed disabled by icodes", 'error');
            $this->message = "Feed disabled by icodes";
        }

        $header = array(
            t('Start Time'),
            t('End Time'),
            t('File Downloaded'),
            t('Vouchers Found'),
            t('Duplicate vouchers skipped'),
            t('Missing Merchants'),
            t('New vouchers imported'),
            t('Messages')
        );

        $build['icodes_voucher_feed_table'] = [
            '#type' => 'table',
            '#header' => $header,
            '#empty' => t('Voucher feed failed to run.'),
        ];

        $row['start']['#markup'] = $start;
        $row['end']['#markup'] = date("d/m/y h:i:s");
        $row['download']['#markup'] = $file_downloaded;
        $row['feed_vouchers']['#markup'] = $this->voucher_count;
        $row['skipped_vouchers']['#markup'] = $this->voucher_skipped;
        $row['missing_merchants']['#markup'] = $this->merchant_missing;
        $row['new_vouchers']['#markup'] = $this->vouchers_added;
        $row['message']['#markup'] = $this->message;

        $build['icodes_voucher_feed_table'][] = $row;
        $build['#title'] = t('Voucher feed summary');

        return $build;
    }

    /**
     * import fetchXMLFile
     */
    public function fetchXMLFile(&$continue = true, &$message = "")
    {

        if ($this->configFactory->get('icodes.settings')->get('external_mode')) {

            $voucherUrl = $this->configFactory->get('icodes.settings')->get('voucher_feed_url');

            if (strstr($voucherUrl, "Hours") && !strstr($voucherUrl, "Page") && !strstr($voucherUrl,
                    "PageSize")) {
                $voucherUrl .= "&Page=".$this->pageCount."&PageSize=".$this->pageSize;
//                print_r($voucherUrl);
            } else {
                $this->finished = true;
            }

            if (!$this->configFactory->get('icodes.settings')->get('process_directory')) {
                $continue = false;
                $this->message = t("Directory not set for XML Files");
                $this->finished = true;
                return;
            } else if (!$voucherUrl) {
                $continue = false;
                $this->message = t("merchant Feed URL not set");
                $this->finished = true;
                return;
            } else {

                $filename = "vouchers_".$this->pageCount.".xml";
                $base_local_url = $this->configFactory->get('icodes.settings')->get('process_directory');
                $this->local_url = $base_local_url."/".$filename;

                //external generate url
                $icodes_url = $voucherUrl;
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
                        $this->finished = true;
                        return;
                    }
                } else {
                    $continue = false;
                    $this->message = t("Could not create directory on the file system");
                    $this->finished = true;
                    return;
                }
            }
        } else {
            $continue = true;
            $this->message = t("Internal mode: skipping the download of the XML");
            $this->finished = true;
            return;
        }
    }

    /**
     *
     */
    public function processXMLFile()
    {

        $this->local_url = $filename = "vouchers_".$this->pageCount.".xml";
        $base_local_url = $this->configFactory->get('icodes.settings')->get('process_directory');
        $this->local_url = drupal_realpath($base_local_url)."/".$filename;

        if (filesize($this->local_url) > 0) {
            if (!$xml = simplexml_load_file($this->local_url,
                'SimpleXMLElement', LIBXML_NOCDATA)) {
                $this->message = t("Local file not found");
                return;
            }

            $count = 0;

            foreach ($xml->item as $voucher) {
                //check if voucher exists, if not add it
                $this->voucher_count++;
                if (count($this->checkVoucherExists($voucher)) == 0) {
                    //make new voucher
                    self::createNewVoucher($voucher);
                } else {
                    $this->voucher_skipped++;
                }
                $count ++;
            }

            if ($count == 0) {
                $this->finished = true;
            } else {
                $this->pageCount += 1;
            }
        } else {
            $this->finished = true;
        }

        if ($this->pageCount > $this->maxPageCount) {
            $this->finished = true;
            $this->message = t("MAX page count hit, aborting");
        }

        return;
    }

    /**
     *
     * @param type $item
     * @return type
     */
    public function checkVoucherExists($voucher)
    {


        $query = \Drupal::entityQuery('node')
            ->condition('field_voucher_icodes_icid', trim($voucher->icid));

        $results = $query->execute();

        if (count($results) == 0 && $voucher->network == "commission_junction") {
            $query = \Drupal::entityQuery('node')
                ->condition('field_affiliate_company', "commission_junction")
                ->condition('field_deep_link', $voucher->deep_link);

            $results = $query->execute();
        }

        return $results;
    }

    /**
     * @param type $item
     */
    public function createNewVoucher($voucher)
    {

        $placeholder_offer_text = array(
            "Great Deal",
            "Save",
            "Save Cash",
            "Latest Deal",
            "Deal",
            "Offer",
            "Top Deal",
        );

        $merchant = $this->checkMerchantExists($voucher);

        if (count($merchant) > 0) {
            $merchant_nid = (array_values($merchant));
            $merchant_nid = array_shift($merchant_nid);

            $dateFormat = 'Y-m-d\TH:i:s';
            $type = "code";

            $categories = explode(" ", $voucher->category_id);
            $node_categories = array();

            foreach ($categories as $cat) {
                if (isset($this->categories[$cat])) {
                    $cat_id = $this->categories[$cat]->entity_id;
                    $node_categories[] = array("target_id" => $cat_id);
                }
            }

            // Create node object with attached file.
            $node_data = [
                'type' => 'voucher',
                'title' => $voucher->icid." - ".$voucher->merchant." - ".$voucher->title,
                'status' => 1,
                'field_voucher_id' => [
                    'value' => $voucher->icid,
                ],
                'field_voucher_icodes_icid' => [
                    'value' => $voucher->icid,
                ],
                'field_voucher_title' => [
                    'value' => $voucher->title,
                ],
                'field_affiliate_company' => [
                    'value' => $voucher->network,
                ],
                'field_icodes_category_term' => $node_categories,
                'field_deep_link' => [
                    'value' => urldecode(urldecode($voucher->deep_link)),
                ],
                'field_deep_link_tracking' => [
                    'value' => urldecode(urldecode($voucher->affiliate_url)),
                ],
                'field_description' => [
                    'value' => $voucher->description,
                ],
                'field_voucher_starts' => [
                    'value' => date($dateFormat,
                        strtotime(str_replace('/', '-', $voucher->start_date)))
                ],
                'field_voucher_ends' => [
                    'value' => date($dateFormat,
                        strtotime(str_replace('/', '-', $voucher->expiry_date))),
                ],
                'field_date_added' => [
                    'value' => date($dateFormat, strtotime(date("Y-m-d h:i:s"))),
                ],
                'field_exclusive' => [
                    'value' => false,
                ],
                'field_voucher_terms' => [
                    'value' => "",
                ],
                'field_voucher_code' => [
                    'value' => $voucher->voucher_code,
                ],
                'field_voucher_type' => [
                    'value' => $type,
                ],
                'field_merchant_ref' => [
                    'target_id' => $merchant_nid,
                ],
            ];

            $node_data['field_voucher_offer_amount'] = "";
            $this->set_voucher_type($voucher->title, $node_data);

            if ($node_data['field_voucher_offer_amount'] == "") {
                $this->set_voucher_type($voucher->description, $node_data);
            }

            if ($node_data['field_voucher_offer_amount'] == "") {
                $key = array_rand($placeholder_offer_text, 1);
                $node_data['field_voucher_offer_amount'] = $placeholder_offer_text[$key];
            }

            $node = Node::create($node_data);
            $this->vouchers_added++;
            $node->save();
        } else {
            $this->merchant_missing++;
            $this->message = "Voucher Merchant not found: " .  $voucher->icid . " : "  . $voucher->merchant_id ;
        }
    }

    /**
     *
     * @param type $item
     * @return type
     */
    public function checkMerchantExists($voucher)
    {

        $query = \Drupal::entityQuery('node')
            ->condition('field_merchant_id', trim($voucher->merchant_id))
            ->condition('field_merchant_network', $voucher->network);

        $results = $query->execute();

        //check for linked merchants - (same website on other merchants)
        if (count($results) == 0) {

            $parse = parse_url($voucher->merchant_url);
            $clean_url = str_replace('www.', '', $parse['host']);

            $query = \Drupal::entityQuery('node')
                ->condition('field_merchant_url', $clean_url);

            $results = $query->execute();
        }
        return $results;
    }

    /**
     *
     */
    public function set_voucher_type($text, &$node_data)
    {

        $value = "";

        if (preg_match_all("/(£[0-9]+.[o][f][f])/i", $text, $matches)) {
            $node_data['field_voucher_offer_type'] = "&pound;";

            $value = $matches[0][0];
            $value = str_replace("£", "", $value);
            $value = str_ireplace("off", "", $value);
        } elseif (preg_match_all("/(€[0-9]+.[o][f][f])/i", $text, $matches)) {
            $node_data['field_voucher_offer_type'] = "&euro;";
            $value = $matches[0][0];
            $value = str_replace("€", "", $value);
            $value = str_ireplace("off", "", $value);
        } elseif (preg_match_all("/(%[0-9]+.[o][f][f])/i", $text, $matches)) {
            $node_data['field_voucher_offer_type'] = "%";
            $value = $matches[0][0];
            $value = str_replace("%", "", $value);
            $value = str_ireplace("off", "", $value);
        } elseif (preg_match_all("/([0-9]+%.[o][f][f])/i", $text, $matches)) {
            $node_data['field_voucher_offer_type'] = "%";
            $value = $matches[0][0];
            $value = str_replace("%", "", $value);
            $value = str_ireplace("off", "", $value);
        } elseif (preg_match_all("/([s][a][v][e].[0-9]+[%])/i", $text, $matches)) {
            $node_data['field_voucher_offer_type'] = "%";
            $value = $matches[0][0];
            $value = str_replace("%", "", $value);
            $value = str_ireplace("save", "", $value);
        } elseif (preg_match_all("/([s][a][v][e].£[0-9]+)/i", $text, $matches)) {
            $node_data['field_voucher_offer_type'] = "&pound;";
            $value = $matches[0][0];
            $value = str_replace("£", "", $value);
            $value = str_ireplace("save", "", $value);
        } elseif (preg_match_all("/([f][r][e][e])/i", $text, $matches)) {
            $node_data['field_voucher_offer_type'] = "free";
            $value = "Free";
        } elseif (preg_match_all("/([0-9]+%.[d][e])/i", $text, $matches)) {
            $node_data['field_voucher_offer_type'] = "%";
            $value = $matches[0][0];
            $value = str_replace("%", "", $value);
            $value = str_ireplace("de", "", $value);
        } elseif (preg_match_all("/([f][r][o][m].[0-9]+€)/i", $text, $matches)) {
            $node_data['field_voucher_offer_type'] = "from_&euro;";
            $value = $matches[0][0];
            $value = str_replace("€", "", $value);
            $value = str_ireplace("from", "", $value);
        } elseif (preg_match_all("/([f][r][o][m].[0-9]+£)/i", $text, $matches)) {
            $node_data['field_voucher_offer_type'] = "from_&pound;";
            $value = $matches[0][0];
            $value = str_replace("£", "", $value);
            $value = str_ireplace("from", "", $value);
        } elseif (preg_match_all("/(%[0-9]+.[d][i][s][c][o][u][n][t])/i", $text,
                $matches)) {
            $node_data['field_voucher_offer_type'] = "%";
            $value = $matches[0][0];
            $value = str_replace("%", "", $value);
            $value = str_ireplace("discount", "", $value);
        } elseif (preg_match_all("/([0-9]+%.[d][i][s][c][o][u][n][t])/i", $text,
                $matches)) {
            $node_data['field_voucher_offer_type'] = "%";
            $value = $matches[0][0];
            $value = str_replace("%", "", $value);
            $value = str_ireplace("discount", "", $value);
        } elseif (preg_match_all("/([0-9]+€)/i", $text, $matches)) {
            $node_data['field_voucher_offer_type'] = "&euro;";
            $value = $matches[0][0];
            $value = str_replace("€", "", $value);
            $value = str_ireplace("off", "", $value);
        }

        if ($value != "") {
            $node_data['field_voucher_offer_amount'] = trim($value);
        }
    }
}