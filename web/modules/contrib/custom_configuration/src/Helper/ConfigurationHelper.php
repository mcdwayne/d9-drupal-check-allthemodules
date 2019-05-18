<?php

namespace Drupal\custom_configuration\Helper;

use Drupal\Core\Database\Connection;

/**
 * CRUD operation for the custom configuration.
 *
 * @package Drupal\custom_configuration\Helper
 */
class ConfigurationHelper {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * Current active domain key.
   *
   * @var string
   */
  public $activeDomain = 'default';

  /**
   * All available domain.
   *
   * @var array
   */
  public $domainArray = [];

  /**
   * Current active language.
   *
   * @var string
   */
  public $activeLanguage = NULL;

  /**
   * All installed language.
   *
   * @var array
   */
  public $languageArray = [];

  /**
   * Module handler service.
   *
   * @var object
   */
  private $moduleHandler;

  /**
   * Domain negotitor service.
   *
   * @var object
   */
  private $negotiator;

  /**
   * Domain loader service.
   *
   * @var object
   */
  private $domainLoader;

  /**
   * Container service.
   *
   * @var object
   */
  private $container;

  /**
   * Language manager service.
   *
   * @var object
   */
  private $languageManager;

  /**
   * String Translation function.
   *
   * @var object
   */
  private $translator;

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $conn, $module_handler, $language_manager, $string_translation) {
    $this->database = $conn;
    $this->moduleHandler = $module_handler;
    $this->languageManager = $language_manager;
    $this->translator = $string_translation;
  }

  /**
   * Translate string.
   *
   * @param string $string
   *   Translate string.
   *
   * @return string
   *   Return translated string.
   */
  public function translate($string) {
    return $this->translator->translate($string);
  }

  /**
   * Create machine name. Replace all characters except alpha & number.
   *
   * @param string $name
   *   Name will check and replace the string.
   *
   * @return string
   *   It will return the machine name.
   */
  public function createMachineName($name) {
    $name = preg_replace('/[^a-zA-Z0-9_ ]/', '', strtolower(trim($name)));
    $name = preg_replace('/[_]+/', '_', preg_replace('/\s+/', ' ', $name));
    $name = ltrim(rtrim($name, '_'), '_');
    $name = preg_replace('/[^a-zA-Z0-9]/', '_', $name);
    if (strlen($name) > 50) {
      $name = substr($name, 0, 50);
    }
    return $name;
  }

  /**
   * Save configuration.
   *
   * @param array $post
   *   It will hold the custom configuration value.
   *
   * @return array
   *   It will provide the message with the status.
   */
  public function createConfiguration(array $post) {
    $name = $post['name'];
    $machine_name = $post['machine_name'];
    $config_value = $post['config_value'];
    $optional_value = $post['optional_value'];
    $status = $post['status'];
    $time = time();
    $domains = $this->implodeDomains($post);
    $langcode = $this->implodeLanguage($post);
    /* If machine name empty create machine name from name. */
    if ((empty($machine_name)) && (!empty($name))) {
      $machine_name = $this->createMachineName($name);
    }
    $args = ['domain' => $domains, 'langcode' => $langcode];
    $args['machine_name'] = $machine_name;
    if ($this->checkDuplicateItems($args) == TRUE) {
      $return = ['status' => 'error', 'message' => $this->translate('Machine name already exists in this combination')];
    }
    else {
      /* Must have value $name, $machine_name and $config_value. */
      if ((!empty($name)) && (!empty($machine_name)) && (!empty($config_value))) {
        try {
          $this->database->insert('custom_configuration')
            ->fields([
              'custom_config_name' => trim($name),
              'custom_config_machine_name' => $machine_name,
              'custom_config_value' => trim($config_value),
              'custom_config_options' => trim($optional_value),
              'custom_config_status' => $status,
              'custom_config_updated_date' => $time,
              'custom_config_created_date' => $time,
              'custom_config_domains' => $domains,
              'custom_config_langcode' => $langcode,
            ])->execute();
          $message = $this->translate('Configuration saved successfully. Machine name @machine_name.', ['@machine_name' => $machine_name]);
          return ['status' => 'status', 'message' => $message];
        }
        catch (\Exception $e) {
          return $this->getMessageByCode($e->getCode());
        }
      }
      else {
        $return = ['status' => 'error', 'message' => $this->translate('Required field can not empty')];
      }
    }
    return $return;
  }

  /**
   * Return message for the error code.
   *
   * @param int $code
   *   Error code.
   */
  public function getMessageByCode($code) {
    if ($code == '23000') {
      $return = ['status' => 'error', 'message' => $this->translate('Machine name already exists in this combination')];
    }
    else {
      $return = ['status' => 'error', 'message' => $this->translate('Configuration cannot be saved')];
    }
    return $return;
  }

  /**
   * Updating the value.
   *
   * @param array $post
   *   This will have the name, value and status in an array.
   *
   * @return array
   *   This  will have the status and message.
   */
  public function updateValue(array $post) {
    $name = $post['name'];
    $config_value = $post['config_value'];
    $optional_value = $post['optional_value'];
    $status = $post['status'];
    $domains = $this->implodeDomains($post);
    $langcode = $this->implodeLanguage($post);
    $args = ['domain' => $domains, 'langcode' => $langcode];
    $args['machine_name'] = $post['machine_name'];
    $args['config_id'] = $post['config_id'];
    if ($this->checkDuplicateItems($args) == TRUE) {
      $return = ['status' => 'error', 'message' => $this->translate('Machine name already exists in this combination')];
    }
    else {
      if ($name !== NULL && $config_value !== NULL && $status !== NULL) {
        try {
          $row_updated = $this->database->update('custom_configuration')
            ->fields([
              'custom_config_name' => $name,
              'custom_config_value' => $config_value,
              'custom_config_options' => $optional_value,
              'custom_config_status' => $status,
              'custom_config_updated_date' => time(),
              'custom_config_domains' => $domains,
              'custom_config_langcode' => $langcode,
            ])->condition('custom_config_id', $post['config_id'])->execute();
          if ($row_updated > 0) {
            $return = ['status' => 'status', 'message' => $this->translate('Configuration saved successfully.')];
          }
          else {
            $return = ['status' => 'error', 'message' => $this->translate('Configuration cannot be saved.')];
          }
        }
        catch (\Exception $e) {
          if ($e->getCode() == '23000') {
            $return = ['status' => 'error', 'message' => $this->translate('Machine name already exists in this combination')];
          }
          else {
            $return = ['status' => 'error', 'message' => $this->translate('Configuration cannot be saved')];
          }
        }
      }
      else {
        $return = ['status' => 'error', 'message' => $this->translate('Required field can not empty')];
      }
    }
    return $return;
  }

  /**
   * Check duplicate item by machine name, language and domain key.
   *
   * @param array $post
   *   Machine name, language and domain key.
   *
   * @return bool
   *   Return true or false.
   */
  public function checkDuplicateItems(array $post) {
    if (!empty($post['machine_name'])) {
      $query = $this->database->select('custom_configuration', 'cc');
      $query->fields('cc', [
        'custom_config_id',
      ]);
      $query->condition('cc.custom_config_machine_name', $post['machine_name']);
      if (!empty($post['config_id'])) {
        $query->condition('custom_config_id', $post['config_id'], '!=');
      }
      // Add language with or condition.
      if (!empty($post['langcode'])) {
        $languagesArr = explode(',', $this->removeComma($post['langcode']));
        if (count($languagesArr) > 0) {
          $group = $query->orConditionGroup();
          foreach ($languagesArr as $key) {
            $group->condition('custom_config_langcode', '%,' . $key . ',%', 'like');
          }
          $query->condition($group);
        }
      }
      // Add domain with or condition.
      if (!empty($post['domain'])) {
        $domainsArr = explode(',', $this->removeComma($post['domain']));
        if (count($domainsArr) > 0) {
          $group = $query->orConditionGroup();
          foreach ($domainsArr as $key) {
            $group->condition('custom_config_domains', '%,' . $key . ',%', 'like');
          }
          $query->condition($group);
        }
      }
      $result = $query->execute()->fetch();
      if ($result) {
        return TRUE;
      }
      else {
        return FALSE;
      }
    }
  }

  /**
   * Join all language code with comma separated sting.
   *
   * @param array $post
   *   Post data in array format.
   *
   * @return string
   *   Return language code.
   */
  public function implodeLanguage(array $post) {
    if (!empty($post['languages']) && count($post['languages']) > 0) {
      $langcode = ',' . implode(',', $post['languages']) . ',';
    }
    else {
      $activeLangcode = $this->getActiveLanguage();
      $langcode = ',' . $activeLangcode . ',';
    }
    return $langcode;
  }

  /**
   * Join all domain key with comma separated sting.
   *
   * @param array $post
   *   Post data in array format.
   *
   * @return string
   *   Return domain key string.
   */
  public function implodeDomains(array $post) {
    if (!empty($post['domains']) && count($post['domains']) > 0) {
      $domains = ',' . implode(',', $post['domains']) . ',';
    }
    else {
      $activeDomain = $this->getActiveDomain();
      $domains = ',' . $activeDomain . ',';
    }
    return $domains;
  }

  /**
   * Delete the config id.
   *
   * @param int $config_id
   *   It will give the config id.
   *
   * @return array
   *   Return the status and message.
   */
  public function deleteValue($config_id) {
    $del = $this->database->delete('custom_configuration')
      ->condition('custom_config_id', $config_id)->execute();
    if ($del > 0) {
      return ['status' => 'status', 'message' => $this->translate('Configuration deleted successfully.')];
    }
    else {
      return ['status' => 'error', 'message' => $this->translate('Configuration cannot be deleted.')];
    }
  }

  /**
   * Configuration getting values.
   *
   * @param array $args
   *   Arguments in key valye pair.
   *
   * @return array
   *   It will return the array of the custom configuration list.
   */
  public function getConfigList(array $args = NULL) {
    $query = $this->database->select('custom_configuration', 'cc');
    $query->fields('cc', [
      'custom_config_id',
      'custom_config_name',
      'custom_config_machine_name',
      'custom_config_value',
      '	custom_config_options',
      'custom_config_status',
      'custom_config_updated_date',
      'custom_config_domains',
      'custom_config_langcode',
    ]);
    if (!empty($args['id'])) {
      $query->condition('cc.custom_config_id', $args['id']);
    }
    if (!empty($args['machine_name'])) {
      $query->condition('cc.custom_config_machine_name', $args['machine_name']);
    }
    if (!empty($args['langcode'])) {
      $langCode = '%,' . $args['langcode'] . ',%';
      $query->condition('custom_config_langcode', $langCode, 'like');
    }
    if (!empty($args['domain_key'])) {
      $domain = '%,' . $args['domain_key'] . ',%';
      $query->condition('cc.custom_config_domains', $domain, 'like');
    }
    if (!empty($args['status'])) {
      $query->condition('cc.custom_config_status', $args['status']);
    }
    $result = $query->execute()->fetchAll();
    return $result;
  }

  /**
   * Configuration getting values.
   *
   * @param string $machine_name
   *   Machine name.
   * @param string $langCode
   *   Language code.
   * @param string $domainKey
   *   Domain key.
   *
   * @return array
   *   It will return the configuration value.
   */
  public function getValue($machine_name = NULL, $langCode = NULL, $domainKey = NULL) {
    if (empty($machine_name)) {
      return NULL;
    }
    // Set langCode as current active language.
    if (empty($langCode)) {
      $langCode = $this->getActiveLanguage();
    }
    // Set domainKey as current active domain.
    if (empty($domainKey)) {
      $domainKey = $this->getActiveDomain();
    }
    $args = [];
    $args['machine_name'] = $machine_name;
    $args['langcode'] = $langCode;
    $args['domain_key'] = $domainKey;
    $args['status'] = 1;
    $result = $this->getConfigList($args);
    if ($result) {
      foreach ($result as $res) {
        return $res->custom_config_value;
      }
    }
  }

  /**
   * Get configuration values with optional values.
   *
   * @param string $machine_name
   *   Machine name.
   * @param string $langCode
   *   Language code.
   * @param string $domainKey
   *   Domain key.
   *
   * @return array
   *   It will return the configuration value.
   */
  public function getValues($machine_name = NULL, $langCode = NULL, $domainKey = NULL) {
    if (empty($machine_name)) {
      return NULL;
    }
    // Set langCode as current active language.
    if (empty($langCode)) {
      $langCode = $this->getActiveLanguage();
    }
    // Set domainKey as current active domain.
    if (empty($domainKey)) {
      $domainKey = $this->getActiveDomain();
    }
    $args = [];
    $args['machine_name'] = $machine_name;
    $args['langcode'] = $langCode;
    $args['domain_key'] = $domainKey;
    $args['status'] = 1;
    $result = $this->getConfigList($args);
    if ($result) {
      foreach ($result as $res) {
        $languageCode = $this->removeComma($res->custom_config_langcode);
        $domains = $this->removeComma($res->custom_config_domains);
        $res->machine_name = $res->custom_config_machine_name;
        $res->name = $res->custom_config_name;
        $res->value = $res->custom_config_value;
        $res->langcode = explode(',', $languageCode);
        $res->domain_key = explode(',', $domains);
        $res->optional = unserialize($res->custom_config_options);
        unset($res->custom_config_id);
        unset($res->custom_config_updated_date);
        unset($res->custom_config_status);
        unset($res->custom_config_langcode);
        unset($res->custom_config_domains);
        unset($res->custom_config_options);
        unset($res->custom_config_value);
        unset($res->custom_config_machine_name);
        unset($res->custom_config_name);
        return $res;
      }
    }
  }

  /**
   * Remove comma from string both side.
   *
   * @param string $string
   *   String.
   *
   * @return string
   *   Return string.
   */
  public function removeComma($string) {
    return ltrim(rtrim($string, ','), ',');
  }

  /**
   * Get languages name in array.
   *
   * @param string $languages
   *   Languages code in comma separated string.
   *
   * @return array
   *   Languages name in array.
   */
  public function getLanguageName($languages) {
    if (!empty($languages)) {
      $output = [];
      $languagesArr = $this->getLanguages();
      $langArr = explode(',', $this->removeComma($languages));
      foreach ($langArr as $key) {
        $output[] = (isset($languagesArr[$key])) ? $languagesArr[$key] : $key;
      }
      return $output;
    }
  }

  /**
   * Get domain name in array.
   *
   * @param string $domains
   *   Domains code in comma separated string.
   *
   * @return array
   *   Domains name in array.
   */
  public function getDomainName($domains) {
    if (!empty($domains)) {
      $output = [];
      $domainsArr = $this->getDomains();
      $domainArr = explode(',', $this->removeComma($domains));
      foreach ($domainArr as $key) {
        $output[] = (isset($domainsArr[$key])) ? $domainsArr[$key] : ucwords($key);
      }
      return $output;
    }
  }

  /**
   * Return languages configuration.
   *
   * @return array
   *   Return languages array.
   */
  public function getLanguages() {
    if (count($this->languageArray) > 0) {
      return $this->languageArray;
    }
    // For the multilingual website.
    $lanuages = $this->languageManager->getLanguages();
    foreach ($lanuages as $lang) {
      $this->languageArray[$lang->getId()] = $lang->getName();
    }
    return $this->languageArray;
  }

  /**
   * Get configured domain list.
   *
   * @return array
   *   Domain list.
   */
  public function getDomains() {
    if (count($this->domainArray) > 0) {
      return $this->domainArray;
    }
    // For multi domain website.
    if ($this->moduleHandler->moduleExists('domain')) {
      /** @var \Drupal\domain\DomainInterface $domain */
      $this->negotiator = $this->container->get('domain.negotiator');
      $this->domainLoader = $this->container->get('domain.loader');
      $this->activeDomain = $this->negotiator->getActiveDomain()->id();
      $allDomain = $this->domainLoader->loadOptionsList();
      foreach ($allDomain as $key => $value) {
        $this->domainArray[$key] = $value;
      }
    }
    return $this->domainArray;
  }

  /**
   * Get current active domain.
   *
   * @return string
   *   Current active domain, default domain 'default'.
   */
  public function getActiveDomain() {
    // For multi domain website.
    if ($this->moduleHandler->moduleExists('domain')) {
      /** @var \Drupal\domain\DomainInterface $domain */
      $this->negotiator = $this->container->get('domain.negotiator');
      $this->activeDomain = $this->negotiator->getActiveDomain()->id();
    }
    return $this->activeDomain;
  }

  /**
   * Get current active language.
   *
   * @return string
   *   Current active language.
   */
  public function getActiveLanguage() {
    return $this->activeLanguage = (!empty($this->activeLanguage)) ? $this->activeLanguage : $this->languageManager->getCurrentLanguage()->getId();
  }

}
