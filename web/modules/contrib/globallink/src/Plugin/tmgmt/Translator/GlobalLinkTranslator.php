<?php

namespace Drupal\globallink\Plugin\tmgmt\Translator;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\globallink\GlExchangeAdapter;
use Drupal\tmgmt\ContinuousTranslatorInterface;
use Drupal\tmgmt\Entity\Job;
use Drupal\tmgmt\Entity\JobItem;
use Drupal\tmgmt\Entity\RemoteMapping;
use Drupal\tmgmt\JobItemInterface;
use Drupal\tmgmt\RemoteMappingInterface;
use Drupal\tmgmt\TMGMTException;
use Drupal\tmgmt\TranslatorPluginBase;
use Drupal\tmgmt_file\Format\FormatManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\tmgmt\TranslatorInterface;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\Translator\AvailableResult;


/**
 * GlobalLink translation plugin controller.
 *
 * @TranslatorPlugin(
 *   id = "globallink",
 *   label = @Translation("GlobalLink"),
 *   description = @Translation("GlobalLink is the world’s most powerful and flexible system to manage multilingual content"),
 *   ui = "Drupal\globallink\GlobalLinkTranslatorUi",
 *   logo = "icons/globallink.png",
 * )
 */
class GlobalLinkTranslator extends TranslatorPluginBase implements ContainerFactoryPluginInterface, ContinuousTranslatorInterface {

  /**
   * The translator.
   *
   * @var TranslatorInterface
   */
  protected $translator;

  /**
   * The adapter for GlExchange library.
   *
   * @var \Drupal\globallink\GlExchangeAdapter
   */
  protected $glExchangeAdapter;

  /**
   * Xliff converter service.
   *
   * @var \Drupal\tmgmt_file\Format\FormatInterface
   */
  protected $formatManager;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $globallinkSettings;

  /**
   * Message level status.
   */
  const MSG_STATUS = 'status';

  /**
   * Message level debug.
   */
  const MSG_DEBUG = 'debug';

  /**
   * Message level warning.
   */
  const MSG_WARNING = 'warning';

  /**
   * Message level error.
   */
  const MSG_ERROR = 'error';

  /**
   * Sets a Translator.
   *
   * @param \Drupal\tmgmt\TranslatorInterface $translator
   *   The translator.
   */
  public function setTranslator(TranslatorInterface $translator) {
    if (!isset($this->translator)) {
      $this->translator = $translator;
    }
  }

  /**
   * Guzzle HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * List of default Drupal 8 and OHT language mapping.
   *
   * @var array
   */
  protected $globallinkDefaultLangcodeMapping = [
    'aa' => 'Afar',
    'aa-DJ' => 'Afar, Djibouti',
    'aa-ER' => 'Afar, Eritrea',
    'aa-ET' => 'Afar, Ethiopia',
    'ab-GE' => 'Abkhazian, Georgia',
    'ae-ES' => 'Spain, Aranese',
    'af' => 'Afrikaans',
    'af-NA' => 'Afrikaans, Namibia',
    'af-ZA' => 'Afrikaans, South Africa',
    'am' => 'Amharic',
    'am-ET' => 'Amharic, Ethiopia',
    'an' => 'Aragonese',
    'an-ES' => 'Aragonese, Spain',
    'ar' => 'Arabic',
    'ar-AE' => 'Arabic, United Arab Emirates',
    'ar-BH' => 'Arabic, Bahrain',
    'ar-DZ' => 'Arabic, Algeria',
    'ar-EG' => 'Arabic, Egypt',
    'ar-IQ' => 'Arabic, Iraq',
    'ar-JO' => 'Arabic, Jordan',
    'ar-KW' => 'Arabic, Kuwait',
    'ar-LB' => 'Arabic, Lebanon',
    'ar-LY' => 'Arabic, Libya',
    'ar-MA' => 'Arabic, Morocco',
    'ar-OM' => 'Arabic, Oman',
    'ar-QA' => 'Arabic, Qatar',
    'ar-SA' => 'Arabic, Saudi Arabia',
    'ar-SD' => 'Arabic, Sudan',
    'ar-SY' => 'Arabic, Syria',
    'ar-TN' => 'Arabic, Tunisia',
    'ar-YE' => 'Arabic, Yemen',
    'as' => 'Assamese',
    'as-IN' => 'Assamese, India',
    'ay' => 'Aymara',
    'ay-BO' => 'Aymara, Bolivia',
    'ay-PE' => 'Aymara, Peru',
    'az' => 'Azerbaijani',
    'az-AZ' => 'Azerbaijani, Azerbaijan',
    'az-XC' => 'Azerbaijani-Cyrillic',
    'az-XE' => 'Azerbaijani-Latin',
    'ba' => 'Bashkir',
    'ba-RU' => 'Bashkir, Russia',
    'bd-ID' => 'Bahasa, Indonesia',
    'bd-MY' => 'Bahasa, Malaysia',
    'be' => 'Belarusian',
    'be-BY' => 'Byelorussian, Byelorussia',
    'bg' => 'Bulgarian',
    'bg-BG' => 'Bulgarian, Bulgaria',
    'bh' => 'Bihari',
    'bi' => 'Bislama',
    'bn' => 'Bengali',
    'bn-BD' => 'Bengali, Bangladesh',
    'bn-IN' => 'Bengali, India',
    'bo-CN' => 'Tibetan, China',
    'br' => 'Breton',
    'bs' => 'Bosnian',
    'bs-BA' => 'Bosnian, Bosnien und Herzegowina',
    'ca' => 'Catalan',
    'ca-ES' => 'Catalan, Spain',
    'cb' => 'Cebuano',
    'cb-PH' => 'Cebuano, Philippines',
    'ce' => 'Chechen',
    'ch' => 'Chamorro',
    'co' => 'Corsican',
    'cs' => 'Czech',
    'cs-CZ' => 'Czech, Czech Republic',
    'cy' => 'Welsh',
    'cy-GB' => 'Welsh, United Kingdom',
    'da' => 'Danish',
    'da-DK' => 'Danish, Denmark',
    'de' => 'German',
    'de-AT' => 'German, Austria',
    'de-CH' => 'German, Switzerland',
    'de-DE' => 'German, Germany',
    'de-LI' => 'German, Liechtenstein',
    'de-LU' => 'German, Luxembourg',
    'dz' => 'Dzongkha',
    'el' => 'Greek',
    'el-CY' => 'Greek, Cyprus',
    'el-GR' => 'Greek, Greece',
    'en' => 'English',
    'en-AU' => 'English, Australia',
    'en-BZ' => 'English, Belize',
    'en-CA' => 'English, Canada',
    'en-CB' => 'English, Caribbean',
    'en-GB' => 'English, United Kingdom',
    'en-ID' => 'English, Indonesia',
    'en-IE' => 'English, Ireland',
    'en-IN' => 'English, India',
    'en-JM' => 'English, Jamaica',
    'en-MT' => 'English, Malta',
    'en-NZ' => 'English, New Zealand',
    'en-PH' => 'English, Philippines',
    'en-RH' => 'English, Zimbabwe',
    'en-SG' => 'English, Singapore',
    'en-TT' => 'English, Trinidad and Tobago',
    'en-US' => 'English, United States',
    'en-ZA' => 'English, South Africa',
    'es' => 'Spanish',
    'es-AR' => 'Spanish, Argentina',
    'es-BO' => 'Spanish, Bolivia',
    'es-CL' => 'Spanish, Chile',
    'es-CO' => 'Spanish, Colombia',
    'es-CR' => 'Spanish, Costa Rica',
    'es-DO' => 'Spanish, Dominican Republic',
    'es-EC' => 'Spanish, Ecuador',
    'es-ES' => 'Spanish, Spain',
    'es-GT' => 'Spanish, Guatemala',
    'es-HN' => 'Spanish, Honduras',
    'es-LA' => 'Spanish, Latin America',
    'es-MX' => 'Spanish, Mexico',
    'es-NI' => 'Spanish, Nicaragua',
    'es-PA' => 'Spanish, Panama',
    'es-PE' => 'Spanish, Peru',
    'es-PR' => 'Spanish, Puerto Rico',
    'es-PY' => 'Spanish, Paraguay',
    'es-SV' => 'Spanish, El Salvador',
    'es-US' => 'Spanish, United States',
    'es-UY' => 'Spanish, Uruguay',
    'es-VE' => 'Spanish, Venezuela',
    'et' => 'Estonian',
    'et-EE' => 'Estonian, Estonia',
    'eu' => 'Basque',
    'eu-ES' => 'Basque, Spain',
    'fa' => 'Persian',
    'fa-AF' => 'Persian-Dari, Afghanistan',
    'fa-IR' => 'Farsi, Iran',
    'fi' => 'Finnish',
    'fi-FI' => 'Finnish, Finland',
    'fj' => 'Fijian',
    'fj-FJ' => 'Fijian, Fiji',
    'fj-PH' => 'Fijian, Philippines',
    'fo' => 'Faroese',
    'fo-FO' => 'Faroese, Faroe Islands',
    'fr' => 'French',
    'fr-BE' => 'French, Belgium',
    'fr-CA' => 'French, Canada',
    'fr-CH' => 'French, Switzerland',
    'fr-FR' => 'French, France',
    'fr-LU' => 'French, Luxembourg',
    'fr-MC' => 'French, Monaco',
    'fr-NC' => 'French, New Caledonia',
    'fr-PF' => 'French, French Polynesia',
    'fy' => 'Frisian',
    'fy-NL' => 'Frisian, Netherlands',
    'ga' => 'Irish',
    'ga-IE' => 'Irish, Ireland',
    'gd' => 'Scottish Gaelic',
    'gd-GB' => 'Scottish Gaelic, United Kingdom',
    'gl' => 'Galician',
    'gl-ES' => 'Galician, Spain',
    'gn' => 'Guaraní',
    'gs-ES' => 'Gascon, Spain',
    'gu' => 'Gujarati',
    'gu-IN' => 'Gujarati, India',
    'ha' => 'Hausa',
    'he' => 'Hebrew',
    'he-IL' => 'Hebrew, Israel',
    'hi' => 'Hindi',
    'hi-IN' => 'Hindi, India',
    'hm' => 'Hmong',
    'hr' => 'Croatian',
    'hr-HR' => 'Croatian, Croatia',
    'ht' => 'Haitian',
    'hu' => 'Hungarian',
    'hu-HU' => 'Hungarian, Hungary',
    'hy' => 'Armenian',
    'hy-AM' => 'Armenian, Armenia',
    'ia' => 'Interlingua',
    'id' => 'Indonesian',
    'id-ID' => 'Indonesian, Indonesia',
    'ie' => 'Interlingue',
    'ik' => 'Inupiaq',
    'il' => 'Ilonggo',
    'is' => 'Icelandic',
    'is-IS' => 'Icelandic, Iceland',
    'it' => 'Italian',
    'it-CH' => 'Italian, Switzerland',
    'it-IT' => 'Italian, Italy',
    'iu' => 'Inuktitut',
    'ja' => 'Japanese',
    'ja-JP' => 'Japanese, Japan',
    'jv' => 'Javanese',
    'ka-GE' => 'Georgian, Georgia',
    'kk-KZ' => 'Kazakh, Kazakhstan',
    'kl' => 'Kalaallisut',
    'km-KH' => 'Khmer, Cambodia',
    'kn-IN' => 'Kannada, India',
    'ko' => 'Korean',
    'ko-KR' => 'Korean, South Korea',
    'ko-KP' => 'Korean, North Korea',
    'ks' => 'Kashmiri',
    'ku' => 'Kurdish',
    'ku-TR' => 'Kurdish, Turkey',
    'ky-KG' => 'Kirghiz, Kyrgyzstan',
    'la' => 'Latin',
    'lb-LU' => 'Luxembourgish, Luxembourg',
    'ln' => 'Lingala',
    'ln-CD' => 'Lingala, The Democratic Republic Of Congo',
    'lo-LA' => 'Lao, Laos',
    'lt' => 'Lithuanian',
    'lt-LT' => 'Lithuanian, Lithuania',
    'lv' => 'Latvian',
    'lv-LV' => 'Latvian, Latvia',
    'mg' => 'Malagasy',
    'mg-MG' => 'Malagasy, Madagascar',
    'mi' => 'Maori',
    'mk' => 'Macedonian',
    'mk-MK' => 'Macedonian, Macedonia',
    'ml' => 'Malayalam',
    'ml-IN' => 'Malayalam, India',
    'mn' => 'Mongolian',
    'mn-MN' => 'Mongolian, Mongolia',
    'mo' => 'Moldavian',
    'mo-MD' => 'Moldovan, Moldova',
    'mr' => 'Marathi',
    'mr-IN' => 'Marathi, India',
    'ms' => 'Malay',
    'ms-SG' => 'Malay, Singapore',
    'ms-BX' => 'Malay, Brunei Darussalam',
    'ms-MY' => 'Malay, Malaysia',
    'mt' => 'Maltese',
    'mt-MT' => 'Maltese, Malta',
    'my-MM' => 'Burmese, MyanMar (Burma)',
    'na' => 'Nauru',
    'nb-NO' => 'Norwegian Bokmal, Norway',
    'nd-ZA' => 'North Ndebele, South Africa',
    'ne' => 'Nepali',
    'ne-NP' => 'Nepali, Nepal',
    'nl' => 'Dutch',
    'nl-BE' => 'Dutch, Belgium',
    'nl-NL' => 'Dutch, Netherlands',
    'no' => 'Norwegian',
    'no-NO' => 'Norwegian (Nynorsk), Norway',
    'nr-ZA' => 'South Ndebele, South Africa',
    'ns-ZA' => 'Nothern Sotho, South Africa',
    'ny-MW' => 'Nyanja, Malawi',
    'oc' => 'Occitan',
    'oc-FR' => 'Occitan, France',
    'om' => 'Oromo',
    'om-ET' => 'Oromo, Ethiopia',
    'or' => 'Oriya',
    'or-IN' => 'Oriya, India',
    'pa' => 'Punjabi',
    'pa-IN' => 'Punjabi (Gurmukha), India',
    'pa-PK' => 'Punjabi (Shahmukhi), Pakistan',
    'pl' => 'Polish',
    'pl-PL' => 'Polish, Poland',
    'ps-AF' => 'Pashto, Afghanistan',
    'pt' => 'Portuguese',
    'pt-BR' => 'Portuguese, Brazil',
    'pt-PT' => 'Portuguese, Portugal',
    'qu' => 'Quechua',
    'qu-BO' => 'Quechua, Bolivia',
    'qu-EU' => 'Quechua, Ecuador',
    'rm' => 'Romansh',
    'rn' => 'Kirundi',
    'ro' => 'Romanian',
    'ro-RO' => 'Romanian, Romania',
    'ru' => 'Russian',
    'ru-KZ' => 'Russian, Kazakhstan',
    'ru-KG' => 'Russian, Kyrgyzstan',
    'ru-UA' => 'Russian, Ukraine',
    'ru-BY' => 'Russian, Belarus',
    'ru-RU' => 'Russian, Russia',
    'rw' => 'Kinyarwanda',
    'rw-RW' => 'Kinyarwanda, Rwanda',
    'sa' => 'Sanskrit',
    'sd' => 'Sindhi',
    'sg' => 'Sango',
    'sh-YU' => 'Serbo-Croatian, Yugoslavia',
    'si-LK' => 'Sinhalese, Sri Lanka',
    'sk' => 'Slovak',
    'sk-SK' => 'Slovak, Slovakia',
    'sl' => 'Slovenian',
    'sl-SL' => 'Slovenian, Slovenia',
    'sm' => 'Samoan',
    'sn' => 'Shona',
    'so-SO' => 'Somali, Somalia',
    'sq' => 'Albanian',
    'sq-AL' => 'Albanian, Albania',
    'sr' => 'Serbian',
    'sr-BA' => 'Serbian, Bosnia and Herzegovina',
    'sr-CS' => 'Serbian, Serbia and Montenegro',
    'sr-ME' => 'Serbian, Montenegro',
    'sr-YU' => 'Serbian (Cyrillic), Yugoslavia',
    'ss' => 'Swati',
    'ss-ZA' => 'Swati, South Africa',
    'st' => 'Southern Sotho',
    'st-ZA' => 'Southern Sotho, South Africa',
    'su-SU' => 'Sudanese, Sudan',
    'sv' => 'Swedish',
    'sv-FI' => 'Swedish, Finland',
    'sv-SE' => 'Swedish, Sweden',
    'sw' => 'Swahili',
    'sw-KE' => 'Swahili, Kenya',
    'sz' => 'Sami',
    'tg-TJ' => 'Tajik, Tajikistan',
    'th-TH' => 'Thai, Thailand',
    'ta' => 'Tamil',
    'ta-IN' => 'Tamil, India',
    'te' => 'Telugu',
    'ti' => 'Tigrinya',
    'tk' => 'Turkmen',
    'tl' => 'Tagalog',
    'tl-PH' => 'Tagalog, Philippines',
    'tn' => 'Tswana',
    'tn-ZA' => 'Tswana, South Africa',
    'to' => 'Tonga',
    'tr' => 'Turkish',
    'tr-TR' => 'Turkish, Turkey',
    'ts' => 'Tsonga',
    'ts-ZA' => 'Tsonga, South Africa',
    'tt' => 'Tatar',
    'tw' => 'Twi',
    'ug' => 'Uighur',
    'uk' => 'Ukrainian',
    'uk-UA' => 'Ukrainian, Ukraine',
    'ur' => 'Urdu',
    'ur-PK' => 'Urdu (Pakistan), Pakistan',
    've-ZA' => 'Venda, South Africa',
    'vi' => 'Vietnamese',
    'vi-US' => 'Vietnamese, United States',
    'vi-VN' => 'Vietnamese, Vietnam',
    'vo' => 'Volapük',
    'uz-UZ' => 'Uzbek, Uzbekistan',
    'xh' => 'Xhosa',
    'xh-ZA' => 'Xhosa, South Africa',
    'yi' => 'Yiddish',
    'yo' => 'Yoruba',
    'za' => 'Zhuang',
    'zh' => 'Chinese',
    'zh-CN' => 'Chinese (Simplified), China',
    'zh-HK' => 'Chinese, Hong Kong',
    'zh-SG' => 'Chinese (Simplified), Singapore',
    'zh-TW' => 'Chinese (Traditional), Taiwan',
    'zh-XM' => 'Chinese, Macau',
    'zu' => 'Zulu',
    'zu-ZA' => 'Zulu, South Africa',
  ];

  /**
   * Constructs a GlobalLinkTranslator object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\globallink\GlExchangeAdapter $gl_exchange_adapter
   *   The GLExchange library adapter.
   * @param \Drupal\tmgmt_file\Format\FormatManager $format_manager
   *   Format manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger for this channel.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, GlExchangeAdapter $gl_exchange_adapter, FormatManager $format_manager, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->glExchangeAdapter = $gl_exchange_adapter;
    $this->formatManager = $format_manager;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('globallink.gl_exchange_adapter'),
      $container->get('plugin.manager.tmgmt_file.format'),
      $container->get('logger.factory')->get('globallink')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function requestTranslation(JobInterface $job) {
    $this->requestJobItemsTranslation($job->getItems());
    if (!$job->isRejected()) {
      $job->submitted('The translation job has been submitted.');
    }
  }

  /**
   * Receives and stores a translation.
   *
   * @param string $ticket_id
   *   The submission ticket id.
   * @param \Drupal\tmgmt\JobInterface $job
   *   The job to retrieve translation for.
   */
  public function retrieveTranslation($ticket_id, JobInterface $job) {
    $translator = $job->getTranslator();
    $settings = $translator->getSettings();

    $pd_config = $this->glExchangeAdapter->getPDConfig($settings);
    $glexchange = $this->glExchangeAdapter->getGlExchange($pd_config);

    try {
      $data = $glexchange->downloadTarget($ticket_id);

      $xml = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA) or die("Error: Cannot create object");
      $body = $xml->file->body;
      $num_items = count($body->group);
      for ($i = 0; $i < $num_items; $i++) {
        $item_ids[$i] = (string) $body->group[$i]->attributes()->{'id'};
      }

      // Remove the preview URL again.
      foreach ($body->group as $group) {
        unset($group->preview);
      }

      $data = $xml->asXML();
      $parsed_data = $this->formatManager->createInstance('xlf')->import($data, FALSE);
      $job->addTranslatedData($parsed_data);
      $glexchange->sendDownloadConfirmation($ticket_id);
    }
    catch (\Exception $e) {
      $job->addMessage('Failed downloading translation. Message error: @error', ['@error' => $e->getMessage()], 'error');
    }
  }

  /**
   * Get completed translations for a specific translator project.
   *
   * @param TranslatorInterface $translator
   *   The translator interface
   *
   * @return \PDTarget[]
   *   Array of PDTarget objects.
   */
  public function getCompletedTranslations($translator) {
    $completed_translations = [];
    $adapter = $this->glExchangeAdapter;
    $settings = $translator->getSettings();

    $pd_config = $adapter->getPDConfig($settings);
    $glexchange = $adapter->getGlExchange($pd_config);
    try {
      $project = $glexchange->getProject($translator->getSetting('pd_projectid'));
      $completed_translations = $glexchange->getCompletedTargetsByProject($project, $adapter::COMPLETED_BY_PROJECT_MAX_RESULT);
    }
    catch (\Exception $e) {
      $this->logger->error('Could not retrieve completed translations because of the following error: @error', ['@error' => $e->getMessage()]);
      tmgmt_message_create('Failed downloading translation. Message error: @error', ['@error' => $e->getMessage()], ['type' => 'error']);
    }

    return $completed_translations;
  }

  /**
   * {@inheritdoc}
   */
  public function abortTranslation(JobInterface $job) {
    $this->setTranslator($job->getTranslator());
    $settings = $this->translator->getSettings();

    $remotes = RemoteMapping::loadByLocalData($job->id());
    if (!empty($remotes)) {
      // Because globallink does not support multiple items per job, all of our
      // job items have the same remote identifier. So we need to cancel the
      // submission just one time.
      try {
        $first_remote = array_shift($remotes);
        reset($first_remote);
        $pd_config = $this->glExchangeAdapter->getPDConfig($settings);
        $glexchange = $this->glExchangeAdapter->getGlExchange($pd_config);
        $glexchange->cancelSubmission($first_remote->getRemoteIdentifier1(), 'Submission aborted by user');

        /** @var RemoteMappingInterface $remote */
        foreach ($remotes as $remote) {
          /** @var JobItem $job_item */
          $job_item = $remote->getJobItem();
          if (!$job_item->isAborted()) {
            try {
              $job_item->setState(JobItemInterface::STATE_ABORTED, 'Aborted by user.');
            }
            catch (TMGMTException $e) {
              $job_item->addMessage('Abortion failed: @error', ['@error' => $e->getMessage()], 'error');
            }
            $job_item->save();
          }
        }
      }
      catch (\Exception $e) {
        $job->addMessage('Abortion failed: @error', ['@error' => $e->getMessage()], 'error');
      }
    }

    // Abort the job in the current system.
    return parent::abortTranslation($job);
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedRemoteLanguages(TranslatorInterface $translator) {
    $pairs = [];
    $globallink_langcodes = $this->globallinkDefaultLangcodeMapping;
    foreach ($globallink_langcodes as $langcode => $description) {
      $pairs[$langcode] = "$description ($langcode)";
    }
    asort($pairs);

    return $pairs;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTargetLanguages(TranslatorInterface $translator, $source_language) {
    $results = [];
    $language_pairs = $translator->getSupportedLanguagePairs();
    foreach ($language_pairs as $language_pair) {
      if ($source_language == $translator->mapToRemoteLanguage($language_pair['source_language'])) {
        $target_language = $language_pair['target_language'];
        $results[$target_language] = $target_language;
      }
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedLanguagePairs(TranslatorInterface $translator) {
    $language_pairs = [];

    try {
      $this->setTranslator($translator);
      $settings = $this->translator->getSettings();

      $pd_config = $this->glExchangeAdapter->getPDConfig($settings);
      $glexchange = $this->glExchangeAdapter->getGlExchange($pd_config);
      $supported = $glexchange->getProject($settings['pd_projectid'])->languageDirections;

      // Build a mapping of source and target language pairs.
      foreach ($supported as $pair) {
        $language_pairs[] = ['source_language' => $pair->sourceLanguage, 'target_language' => $pair->targetLanguage];
      }
    }
    catch (\Exception $e) {
      return [];
    }

    return $language_pairs;
  }

  /**
   * {@inheritdoc}
   */
  public function checkAvailable(TranslatorInterface $translator) {
    // One time per request should be enough if available.
    $available = &drupal_static(__METHOD__, FALSE);
    if ($available === FALSE) {
      try {
        $settings = $translator->getSettings();

        $pd_config = $this->glExchangeAdapter->getPDConfig($settings);
        $this->glExchangeAdapter->getGlExchange($pd_config);
      }
      catch (\Exception $e) {
        return AvailableResult::no(t('@translator is not available. Make sure it is properly <a href=:configured>configured</a>.', [
          '@translator' => $translator->label(),
          ':configured' => $translator->url(),
        ]));
      }
      $available = TRUE;
    }
    return AvailableResult::yes();
  }

  /**
   * Fetches translations for job items of a given job.
   *
   * @param Job $job
   *   A job containing job items that translations will be fetched for.
   *
   * @return bool
   *   Returns TRUE if there are error messages during the process of retrieving
   *   translations. Otherwise FALSE.
   */
  public function fetchJobs(Job $job) {
    $this->setTranslator($job->getTranslator());
    $completed_translations = $this->getCompletedTranslations($this->translator);

    // Reorganise completed translations by uuid.
    $translations_imported = FALSE;
    foreach ($completed_translations as $completed_translation) {
      if ($completed_translation->clientIdentifier == $job->uuid()) {
        $this->retrieveTranslation($completed_translation->ticket, $job);
        $translations_imported = TRUE;
      }
    }

    if (!$translations_imported) {
      drupal_set_message(t('No translations available for this job at the moment.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultSettings() {
    $defaults = parent::defaultSettings();
    // Enable CDATA for content encoding in File translator.
    $defaults['xliff_cdata'] = TRUE;
    return $defaults;
  }

  /**
   * Returns the XLIFF data for a job.
   *
   * @param \Drupal\tmgmt\JobInterface $job
   *   The translation job.
   * @param \Drupal\tmgmt\JobItemInterface[] $job_items
   *   Limit the export to the provided job items.
   *
   * @return array
   */
  protected function getXliffData(JobInterface $job, array $job_items) {
    // Ensure the job item list is keyed by the job item ID.
    $job_items_by_id = [];
    foreach ($job_items as $job_item) {
      $job_items_by_id[$job_item->id()] = $job_item;
    }

    $conditions['tjiid'] = ['value' => array_keys($job_items_by_id), 'operator' => 'IN'];

    $data = $this->formatManager->createInstance('xlf')->export($job, $conditions);

    $xml = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);

    $body = $xml->file->body;
    foreach ($body->group as $group) {
      foreach ($group->{'trans-unit'} as $trans_unit) {
        $trans_unit->target = $trans_unit->source;
      }
    }

    // Added this option for getting the url for every source submitted for
    // translation.
    foreach ($body->group as $group) {
      $job_item_id = (int) $group['id'];
      if ($url = $job_items_by_id[$job_item_id]->getSourceUrl()) {
        $group->addChild('preview', $url->setAbsolute()->toString());
      }
    }

    return $xml->asXML();
  }

  /**
   * {@inheritdoc}
   */
  public function requestJobItemsTranslation(array $job_items) {
    /** @var \Drupal\tmgmt\Entity\Job $job */
    $job = reset($job_items)->getJob();

    try {
      $source_language = $job->getRemoteSourceLanguage();
      $target_language = $job->getRemoteTargetLanguage();

      $settings = $job->getTranslator()->getSettings();

      $pd_config = $this->glExchangeAdapter->getPDConfig($settings);
      $glexchange = $this->glExchangeAdapter->getGlExchange($pd_config);
      $submission_settings = [
        'name' => $settings['pd_submissionprefix'] . $job->label(),
        'submitter' => $settings['pd_username'],
        'urgent' => $job->getSetting('urgent'),
        'comment' => $job->getSetting('comment'),
      ];

      if ($job->isContinuous()) {
        $required_by = $job->getSetting('required_by');
        $due_datetime = new DrupalDateTime("+$required_by weekday");
      }
      else {
        $due_setting = $job->getSetting('due');
        /** @var DrupalDateTime $due_datetime */
        $due_datetime = unserialize(serialize($due_setting['object']));
      }
      $submission_settings['due'] = $due_datetime->format('U') * 1000;

      $project = $glexchange->getProject($settings['pd_projectid']);
      $submission = $this->glExchangeAdapter->getSubmission($project, $submission_settings);
      $glexchange->initSubmission($submission);

      $upload_result_ids = [];
      if ($job->getTranslator()->getSetting('pd_combine')) {
        $data = $this->getXliffData($job, $job_items);

        $document_settings = [
          'name' => $job->label() . '.xliff',
          'source_language' => $source_language,
          'target_languages' => [$target_language],
          'data' => $data,
          'client_identifier' => $job->uuid(),
          'classifier' => $settings['pd_classifier'],
        ];
        $document = $this->glExchangeAdapter->getPdDocument($document_settings);
        $upload_result_id = $glexchange->uploadTranslatable($document);
        foreach ($job_items as $job_item) {
          $upload_result_ids[$job_item->id()] = $upload_result_id;
        }
      }
      else {
        foreach ($job_items as $job_item) {
          $data = $this->getXliffData($job, [$job_item]);

          $document_settings = [
            'name' => $job_item->label() . '.xliff',
            'source_language' => $source_language,
            'target_languages' => [$target_language],
            'data' => $data,
            'client_identifier' => $job->uuid(),
            'classifier' => $settings['pd_classifier'],
          ];
          $document = $this->glExchangeAdapter->getPdDocument($document_settings);
          $upload_result_ids[$job_item->id()] = $glexchange->uploadTranslatable($document);
        }
      }
      $submission_result_id = $glexchange->startSubmission();

      /** @var JobItemInterface $job_item */
      foreach ($job_items as $job_item) {
        $job_item->addRemoteMapping(NULL, $submission_result_id, ['remote_identifier_2' => $upload_result_ids[$job_item->id()]]);
        $job_item->active('Ticket with id %submission_id created', ['%submission_id' => $submission_result_id]);
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Job has been rejected with following error: @error', ['@error' => $e->getMessage()]);
      $job->rejected('Job has been rejected with following error: @error', ['@error' => $e->getMessage()], 'error');
      $job->addMessage('Job has been rejected with following error: @error', ['@error' => $e->getMessage()], 'error');
    }
  }
}
