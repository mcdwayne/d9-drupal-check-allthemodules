<?php

/**
 * @file
 * Provides Wordbee Beebox plugin controller.
 */

namespace Drupal\tmgmt_wordbee\Plugin\tmgmt\Translator;

use Symfony\Component\Dependency\Injection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\tmgmt_wordbee\Beebox\BeeboxAPI;
use Drupal\tmgmt_wordbee\Beebox\CustomXliff;
use Drupal\tmgmt\ContinuousTranslatorInterface;
use Drupal\tmgmt\TranslatorPluginBase;
use Drupal\tmgmt\TranslatorInterface;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt_file\Plugin\tmgmt_file\Format\Xliff;
use Drupal\tmgmt\Translator\AvailableResult;
use Drupal\tmgmt\Translator\TranslatableResult;
use Drupal\tmgmt\TMGMTException;

/**
 * Wordbee Beebox translator plugin.
 *
 * Check @link http://www.beeboxlinks.com/download
 *
 * @TranslatorPlugin(
 *   id = "wordbee",
 *   label = @Translation("Wordbee Beebox"),
 *   description = @Translation("WordBee Beebox Translation service."),
 *   ui = "Drupal\tmgmt_wordbee\BeeboxTranslatorUi",
 *   logo = "icons/beebox.svg"
 * )
 */
class BeeboxTranslator extends TranslatorPluginBase implements ContainerFactoryPluginInterface, ContinuousTranslatorInterface {
    /**
	 * API_Calls instance
	 *
	 * @var BeeboxAPI
	 */
    private $apiCalls;

	/**
	 * Constructor
     * @param array $configuration
     *   A configuration array containing information about the plugin instance.
     * @param string $plugin_id
     *   The plugin ID for the plugin instance.
     * @param mixed $plugin_definition
     *   The plugin implementation definition.
	 */
	public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
		parent::__construct($configuration, $plugin_id, $plugin_definition);
	}

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
        return new static(
          $configuration,
          $plugin_id,
          $plugin_definition
        );
    }

    /**
     * {@inheritdoc}
     */
    public function checkAvailable(TranslatorInterface $translator) {
        if($this->prepareApiCalls($translator)) {
            try {
                $this->apiCalls->connect();
                $result = AvailableResult::yes();
            }
            catch (TMGMTException $e) {
                $trace = $e->getTrace();

                if(($element = array_shift($trace)) && isset($element['args'][1]) && ($json = json_decode($element['args'][1]['response'])) && array_key_exists('message', $json))
                    $message = $json->message;
                else
                    $message = $e->getMessage();
                $result = AvailableResult::no(t('@translator is not configured correctly. Please <a href=:configured>check your credentials</a>.<br>Details : @message', [
                    '@translator' =>  $translator->label(),
                    ':configured' =>  $translator->url(),
                    '@message'    =>  $message
                 ]));
            }
            $this->apiCalls->disconnect();
        } else
            $result = AvailableResult::no(t('@translator is not configured yet. Please <a href=:configured>configure</a> the connector first.', [
                '@translator' =>  $translator->label(),
                ':configured' =>  $translator->url()
            ]));
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function checkTranslatable(TranslatorInterface $translator, JobInterface $job) {
        $this->prepareApiCalls($translator);
        try{
            $languages = $this->apiCalls->getProjectLanguages();
            $this->apiCalls->disconnect();

            $source = $translator->mapToRemoteLanguage($job->getSourceLanguage()->getId());
            if(!isset($languages[$source])){
                return TranslatableResult::no(t('The source language @sourcelocale is not the Beebox source language', ['@sourcelocale' => $job->source_language]));
            }
            elseif(!array_key_exists($translator->mapToRemoteLanguage($job->getTargetLanguage()->getId()), $languages[$source])){
                return TranslatableResult::no(t('The target language @targetlocale is not configured in the Beebox project', ['@targetlocale' => $job->target_language]));
            }
            return parent::checkTranslatable($translator, $job);
        }
        catch (TMGMTException $e) {
            return TranslatableResult::no(t('An error occured when we tried to check the project languages'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function requestTranslation(JobInterface $job) {
        if($job->getTranslator()->getSetting('leave_xliff_target_empty'))
            $fileformat = new CustomXliff();
        else
            $fileformat = new Xliff();

        $filename = $job->id().'-drupal_connector.xliff';
        $xliff_file = $fileformat->export($job);

        $translator = $job->getTranslator();
        $this->prepareApiCalls($translator);

        try {
            $this->apiCalls->sendFile($xliff_file, $filename, $job->getTranslator()->mapToRemoteLanguage($job->getSourceLanguage()->getId()));
            $this->apiCalls->sendFile('{"locales":["'.$job->getTranslator()->mapToRemoteLanguage($job->getTargetLanguage()->getId()).'"]}', $filename.'.beebox', $job->getTranslator()->mapToRemoteLanguage($job->getSourceLanguage()->getId()));
            /*if($this->apiCalls->scanRequired())
                $this->apiCalls->scanFiles();*/
            $job->submitted('Job has been submitted to Beebox.');
        }
		catch (TMGMTException $e) {
            watchdog_exception('tmgmt_wordbee', $e);
            $job->rejected('Job has been rejected with following error: @error', array('@error' => $e->getMessage()), 'error');
        }
        $this->apiCalls->disconnect();
    }

    /**
     * {@inheritdoc}
     */
    public function abortTranslation(JobInterface $job) {
        $filename = $job->id().'-drupal_connector.xliff';
        $this->prepareApiCalls($job->getTranslator());
        try {
            $workprogress = $this->apiCalls->getWorkprogress(array($filename));
            if(count($workprogress) > 0) {
                $this->apiCalls->deleteFile($filename, $job->getTranslator()->mapToRemoteLanguage($job->getSourceLanguage()->getId()));
                $this->apiCalls->deleteFile($filename.'.beebox', $job->getTranslator()->mapToRemoteLanguage($job->getSourceLanguage()->getId()));
                $job->aborted('Job removed');
                return true;
            }
        }
        catch(TMGMTException $e){
            watchdog_exception('tmgmt_wordbee', $e);
            $job->rejected('Job has not been cancelled with following error: @error', array('@error' => $e->getMessage()), 'error');
        }
        $this->apiCalls->disconnect();

        return false;
    }

    /**
     * {@inheritdoc}
     * Used to show the remote language list in the configuration page.
     */
    public function getSupportedRemoteLanguages(TranslatorInterface $translator) {
        if($this->prepareApiCalls($translator)) {
            try {
                $remote_languages = $this->apiCalls->getProjectLanguages();
                $sourceLanguage = array_keys($remote_languages)[0];

                $allLanguages = array($sourceLanguage => $sourceLanguage);
                //return array_merge($allLanguages, array_keys($remote_languages[$sourceLanguage]));
                foreach(array_keys($remote_languages[$sourceLanguage]) as $id => $lang)
                    $allLanguages[$lang] = $lang;
                return $allLanguages;
            }
            catch(TMGMTException $e) {
            }
            $this->apiCalls->disconnect();
        }

        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedTargetLanguages(TranslatorInterface $translator, $source_language) {
        $this->prepareApiCalls($translator);
        $remote_languages = $this->apiCalls->getProjectLanguages();

        if(array_key_exists($source_language, $remote_languages))
            return $remote_languages[$source_language];

        $this->apiCalls->disconnect();

        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function hasCheckoutSettings(JobInterface $job) {
        return FALSE;
    }

    /**
     * Check if a job is completed in Beebox and download the file.
     * @param JobInterface $job The job to download files
     * @return int The number of files downloaded.
     */
    public function updateCompletedJob(JobInterface $job) {
        $this->prepareApiCalls($job->getTranslator());
        $content = $this->apiCalls->getWorkprogress(array($job->id().'-drupal_connector.xliff'));
        $workprogress = json_decode($content);
        $fileformat = new CustomXliff();

        /** @var $acceptedTranslations int The number of translations accepted */
        $acceptedTranslations = 0;
        foreach ($workprogress->files as $work) {
            if ($work->uptodate) {
                $file = $this->apiCalls->getFile($work->file, $work->locale);
                if($fileformat->validateImport($file)){
                    $acceptedTranslations++;
                    $job->addTranslatedData($fileformat->import($file, false));
                    if($job->getTranslator()->isAutoAccept())
                        $job->finished('Translation downloaded from Beebox and auto accepted');
                    else
                        $job->addMessage('Translation downloaded from Beebox');
                }
            }
        }
        $this->apiCalls->disconnect();
        return $acceptedTranslations;
    }

    /**
     * {@inheritdoc}
     */
    public function requestJobItemsTranslation(array $job_items) {
        /** @var \Drupal\tmgmt\Entity\JobItem $job_item */
        foreach ($job_items as $job_item) {
            $this->updateCompletedJob($job_item->getJob());
        }
    }

    /**
	 * Instanciate BeeboxAPI if needed, this method should be called before using $this->apiCalls
	 */
    private function prepareApiCalls(TranslatorInterface $translator) {
        // 1. Check if we got all settings.
        if(!$translator->getSetting('url') || !$translator->getSetting('projectKey') || !$translator->getSetting('username') || !$translator->getSetting('password'))
            return false;

        // 2. Connect
        $module_infos = system_get_info('module', 'tmgmt_'.$this->getBaseId());
        if (!isset($this->apiCalls))
            //$this->apiCalls = new API_calls(
            $this->apiCalls = new BeeboxAPI(
                'WB-Drupal',
                $module_infos['version'],
                $translator->getSetting('url'),
                $translator->getSetting('projectKey'),
                $translator->getSetting('username'),
                $translator->getSetting('password')
            );

        return true;
    }
}
