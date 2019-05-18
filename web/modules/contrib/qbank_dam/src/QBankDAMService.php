<?php

namespace Drupal\qbank_dam;

use DOMDocument;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\FileUsage\DatabaseFileUsageBackend;
use Exception;
use GuzzleHttp\Client;
use QBNK\QBank\API\Credentials;
use QBNK\QBank\API\Model\MediaUsage;
use QBNK\QBank\API\QBankApi;

/**
 * Class QBankDAMService.
 *
 * @package Drupal\qbank_dam
 */
class QBankDAMService implements QBankDAMServiceInterface
{

    use StringTranslationTrait;

    /**
     * QBank API instance.
     *
     * @var \QBNK\QBank\API\QBankApi
     */
    protected $QAPI;

    /**
     * The configuration object factory.
     *
     * @var \Drupal\Core\Config\ConfigFactory
     */
    protected $config_factory;

    /**
     * Drupal\Core\Entity\EntityTypeManager definition.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * Base Database API class.
     *
     * @var \Drupal\Core\Database\Connection
     */
    protected $database;

    /**
     * File usage manager.
     *
     * @var Drupal\file\FileUsage\DatabaseFileUsageBackend
     */
    protected $fileUsage;

    /**
     * Constructor.
     */
    public function __construct(ConfigFactory $config_factory, EntityTypeManagerInterface $entity_type_manager, Connection $database, DatabaseFileUsageBackend $file_usage)
    {
        $this->configFactory = $config_factory;
        $this->entityTypeManager = $entity_type_manager;
        $this->database = $database;
        $this->fileUsage = $file_usage;
        $this->QAPI = NULL;
    }

    /**
     * @return QBankApi|null
     */
    public function getAPI()
    {
        $conf = $this->getConfiguration();

        if (($this->QAPI == NULL) &&
            $conf['api_url'] &&
            $conf['client_id'] &&
            $conf['user'] &&
            $conf['password']
        ) {
            $this->QAPI = new QBankApi(
                $conf['api_url'],
                new Credentials(
                    $conf['client_id'],
                    $conf['user'],
                    $conf['password']
                ),
                [
                    'log' => FALSE,
                ]
            );
        } else {
            $config_link = \Drupal::l(t('QBank DAM Configuration'), \Drupal\Core\Url::fromRoute('qbank_dam.qbank_dam_config_form'));
            drupal_set_message(t('Unable to connect to QBank DAM, please check your @link', ['@link' => $config_link]), 'warning');
        }

        return $this->QAPI;
    }

    /**
     * @param $url
     * @param $client_id
     * @param $user
     * @param $password
     * @return bool|null
     */
    public function checkConfiguration($url, $client_id, $user, $password)
    {
        try {
            $test = new QBankApi(
                $url,
                new Credentials($client_id, $user, $password),
                ['log' => FALSE,]
            );
            $test->getToken();
        } catch (Exception $e) {
            return NULL;
        }

        return TRUE;
    }

    /**
     * @return bool|null
     */
    public function checkStoredConfiguration()
    {
        return $this->checkConfiguration(
            $this->getApiUrl(),
            $this->getClientId(),
            $this->getUser(),
            $this->getpassword()
        );
    }

    /**
     * @return array
     */
    public function getConfiguration()
    {
        return [
            'protocol' => $this->configFactory->get('qbank_dam.qbankdamconfig')
                ->get('protocol'),
            'api_url' => $this->configFactory->get('qbank_dam.qbankdamconfig')
                ->get('api_url'),
            'client_id' => $this->configFactory->get('qbank_dam.qbankdamconfig')
                ->get('client_id'),
            'user' => $this->configFactory->get('qbank_dam.qbankdamconfig')
                ->get('user'),
            'password' => $this->configFactory->get('qbank_dam.qbankdamconfig')
                ->get('password'),
        ];
    }

    /**
     * @return array|mixed|null
     */
    public function getProtocol()
    {
        return $this->configFactory->get('qbank_dam.qbankdamconfig')
            ->get('protocol');
    }

    /**
     * @return array|mixed|null
     */
    public function getApiUrl()
    {
        return $this->configFactory->get('qbank_dam.qbankdamconfig')
            ->get('api_url');
    }

    /**
     * @return array|mixed|null
     */
    public function getClientId()
    {
        return $this->configFactory->get('qbank_dam.qbankdamconfig')
            ->get('client_id');
    }

    /**
     * @return array|mixed|null
     */
    public function getUser()
    {
        return $this->configFactory->get('qbank_dam.qbankdamconfig')->get('user');
    }

    /**
     * @return array|mixed|null
     */
    public function getpassword()
    {
        return $this->configFactory->get('qbank_dam.qbankdamconfig')
            ->get('password');
    }

    /**
     * @return array|mixed|null
     */
    public function getDeploymentSite()
    {
        return $this->configFactory->get('qbank_dam.qbankdamconfig')
            ->get('deployment_site');
    }

    /**
     * @return array|mixed|null
     */
    public function getSessionId()
    {
        return $this->configFactory->get('qbank_dam.qbankdamconfig')
            ->get('session_id');
    }

    /**
     * @return array|mixed|null
     */
    public function getFieldMap()
    {
        return $this->configFactory->get('qbank_dam.qbankdamconfig')
            ->get('map');
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        $this->getAPI();

        if ($this->QAPI) {
            return $this->QAPI->getToken()->getToken();
        }
    }

    /**
     * @return array
     */
    public function getDeploymentSites()
    {
        $this->getAPI();
        $sites = [];
        $list = [];

        if ($this->QAPI) {
            try {
                $deployment = $this->QAPI->deployment();
                $list = $deployment->listSites();
            } catch (Exception $e) {
                $list = [];
            }
        }

        foreach ($list as $site) {
            $sites[$site->getId()] = $site->getName();
        }

        if (count($sites) > 0) {
            return $sites;
        } else {
            return [
                'No site' => $this->t('No site'),
            ];
        }
    }

    /**
     * @param $url
     * @param $media_id
     * @return \Drupal\file\FileInterface|false|null
     */
    public function download($url, $media_id)
    {
        $file = NULL;
        $client = new Client();
        $response = $client->get($url);

        if ($response->getStatusCode() == 200) {
            $filenameOriginal = str_replace('"', '', explode('=', $response->getHeader('content-disposition')[0])[1]);
            $filenameParts = explode('.', $filenameOriginal);
            $ext = array_pop($filenameParts);
            $filenameOnly = implode('.', $filenameParts);
            $filename = $filenameOnly . date('YmdHis') . '.' . $ext;
            if ($filename) {
                $file_data = $response->getBody()->getContents();
                $directory = 'public://qbank/';
                file_prepare_directory($directory, FILE_CREATE_DIRECTORY);
                $file = file_save_data($file_data, $directory . $filename, FILE_EXISTS_REPLACE);
            }

            if ($file) {
                $this->database->update('file_managed')
                    ->fields([
                        'qbank_origin_type' => 'qbank',
                        'qbank_origin_id' => $media_id,
                    ])
                    ->condition('fid', $file->Id(), '=')
                    ->execute();
            }
        }

        return $file;
    }

    /**
     * @param $entity
     */
    public function deleteUsage($entity)
    {
        $this->database->delete('file_usage')
            ->condition('module', 'media')
            ->condition('id', $entity->Id())
            ->execute();
    }

    /**
     * @param $entity
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
     */
    public function addUsage($entity)
    {
        $this->qbankFilterAddFileUsageFromFields($entity);
        $this->qbankAddUsage($entity);
    }

    /**
     * Add file usage from file references in an entity's text fields.
     */
    private function qbankFilterAddFileUsageFromFields($entity)
    {
        // Track the total usage for files from all fields combined.
        $entity_files = $this->qbankEntityFieldCountFiles($entity);
        $entity_id = $entity->Id();
        // When an entity has revisions and then is saved again NOT as new version the
        // previous revision of the entity has be loaded to get the last known good
        // count of files. The saved data is compared against the last version
        // so that a correct file count can be created for that (the current) version
        // id. This code may assume some things about entities that are only true for
        // node objects. This should be reviewed.
        if (!($entity->isNew())) {
            $old_files = $this->qbankEntityFieldCountFiles($this->entityTypeManager->getStorage($entity->getEntityTypeId())
                ->load($entity->id()));
            foreach ($old_files as $fid => $old_file_count) {
                // Were there more files on the node just prior to saving?
                if (empty($entity_files[$fid])) {
                    $entity_files[$fid] = 0;
                }
                if ($old_file_count > $entity_files[$fid]) {
                    $deprecate = $old_file_count - $entity_files[$fid];
                    // Now deprecate this usage
                    $file = $this->entityTypeManager->getStorage('file')->load($fid);
                    if ($file) {
                        $this->fileUsage->delete($file, 'media', $entity->getEntityType()
                            ->id(), $entity_id, $deprecate);
                    }
                    // Usage is deleted, nothing more to do with this file
                    unset($entity_files[$fid]);
                } // There are the same number of files, nothing to do
                elseif ($entity_files[$fid] == $old_file_count) {
                    unset($entity_files[$fid]);
                }
                // There are more files now, adjust the difference for the greater number.
                // file_usage incrementing will happen below.
                else {
                    // We just need to adjust what the file count will account for the new
                    // images that have been added since the increment process below will
                    // just add these additional ones in
                    $entity_files[$fid] = $entity_files[$fid] - $old_file_count;
                }
            }
        }

        // Each entity revision counts for file usage. If versions are not enabled
        // the file_usage table will have no entries for this because of the delete
        // query above.
        foreach ($entity_files as $fid => $entity_count) {
            if ($file = $this->entityTypeManager->getStorage('file')->load($fid)) {
                $this->fileUsage->add($file, 'media', $file->getEntityTypeId(), $fid, $entity_count);
            }
        }
    }

    /**
     * Parse file references from an entity's text fields and return them as an
     * array.
     */
    private function qbankFilterParseFromFields($entity)
    {
        $file_references = [];

        foreach ($this->qbankFilterFieldsWithTextFiltering($entity) as $field_name) {
            $field = $entity->get($field_name);

            if (!empty($field->getValue())) {
                $doc = new DOMDocument();
                $doc->loadHTML($field->getValue(TRUE)[0]['value']);
                $imgs = $doc->getElementsByTagName('img');

                foreach ($imgs as $img) {
                    $filename = explode('/', $img->getAttribute('src'));
                    $filename = $filename[sizeof($filename) - 1];
                    $query = $this->entityTypeManager->getStorage('file')->getQuery();
                    $result = $query->condition('uri', '%/' . $filename, 'LIKE')
                        ->execute();
                    $fid = reset($result);
                    $file_references[] = [
                        'fid' => $fid,
                        'filename' => $filename,
                    ];
                }
            }
        }

        return $file_references;
    }

    /**
     * Utility function to get the file count in this entity
     *
     * @param type $entity
     *
     * @return int
     */
    private function qbankEntityFieldCountFiles($entity)
    {
        $entity_files = [];
        foreach ($this->qbankFilterParseFromFields($entity) as $file_reference) {
            if (empty($entity_files[$file_reference['fid']])) {
                $entity_files[$file_reference['fid']] = 1;
            } else {
                $entity_files[$file_reference['fid']]++;
            }
        }
        return $entity_files;
    }

    /**
     * Returns an array containing the names of all fields that perform text
     * filtering.
     */
    private function qbankFilterFieldsWithTextFiltering($entity)
    {
        // Get all of the fields on this entity that allow text filtering.
        $fields_with_text_filtering = [];

        foreach ($entity->getFieldDefinitions() as $field_name => $field) {
            if (array_key_exists('format', $field->getFieldStorageDefinition()
                ->getPropertyDefinitions())) {
                $fields_with_text_filtering[] = $field_name;
            }
        }

        return $fields_with_text_filtering;
    }

    /**
     * @param $node
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
     */
    private function qbankAddUsage($node)
    {
        $query = $this->database->select('file_usage', 'fu');
        $query->join('file_managed', 'fm', 'fu.fid = fm.fid');
        $query->fields('fm', ['fid', 'uri', 'qbank_origin_id', 'uid']);
        $query->fields('fu', ['id']);
        $query->condition('fu.id', $node->Id(), '=');
        $query->condition('fm.qbank_origin_type', 'qbank', '=');
        $r = $query->execute();

        $qbankapi = $this->getAPI();

        $qbank_session = $qbankapi->events()->session(
            $this->getDeploymentSite(),
            $this->getSessionId(),
            gethostbyname($_SERVER['HTTP_HOST']),
            'drupal'
        );

        while ($record = $r->fetchAssoc()) {
            $user = $this->entityTypeManager->getStorage('user')
                ->load($record['uid']);
            $mediaUsage = new MediaUsage([
                'mediaId' => $record['qbank_origin_id'],
                'mediaUrl' => file_create_url($record['uri']),
                'pageUrl' => \Drupal\Core\Url::fromRoute('entity.node.canonical', ['node' => $record['id']], ['absolute' => TRUE])
                    ->toString(),
                'language' => 'eng', //Three character language ISO
                'context' => [
                    'localID' => $record['fid'],
                    'cropCoords' => [],
                    'pageTitle' => $node->getTitle(),
                    'createdByName' => $user->name->value,
                    'createdByEmail' => $user->mail->value,
                ],
            ]);

            $mediaUsageResponse = $qbankapi->events()->addUsage(
                $qbank_session,
                $mediaUsage
            );
        }
    }

    /**
     * This method provides the image information from image ID, we read image properties from the returing object of the API.
     *
     * @param integer $qbank_id
     *
     * @return QBNK\QBank\API\Model\MediaResponse $property
     */
    public function getImageProperties($qbank_id)
    {
        $property = $this->getAPI()->media()->retrieveMedia($qbank_id);
        return $property;
    }

}
