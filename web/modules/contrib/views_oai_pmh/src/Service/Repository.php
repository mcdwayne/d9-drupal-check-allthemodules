<?php

namespace Drupal\views_oai_pmh\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Picturae\OaiPmh\Exception\NoRecordsMatchException;
use Picturae\OaiPmh\Interfaces\Repository as RepositoryInterface;
use Picturae\OaiPmh\Implementation\Repository\Identity;
use Symfony\Component\HttpFoundation\RequestStack;
use Picturae\OaiPmh\Implementation\RecordList;

/**
 *
 */
class Repository implements RepositoryInterface {

  protected $host;

  protected $path;

  protected $scheme;

  protected $port;

  protected $mail;

  protected $siteName;

  protected $records = [];

  protected $offset = NULL;

  protected $totalRecords = 0;

  /**
   *
   */
  public function __construct(ConfigFactoryInterface $config, RequestStack $request) {
    $system_site = $config->get('system.site');

    $this->siteName = $system_site
      ->getOriginal('name', FALSE);
    $this->mail = $system_site->get('mail');

    $currentRequest = $request->getCurrentRequest();

    $this->host = $currentRequest->getHost();
    $this->path = $currentRequest->getPathInfo();
    $this->scheme = $currentRequest->getScheme() . '://';
    $this->port = ':' . $currentRequest->getPort();
  }

  /**
   *
   */
  public function getBaseUrl() {
    return $this->scheme . $this->host . $this->port . $this->path;
  }

  /**
   *
   */
  public function getGranularity() {
    return 'YYYY-MM-DDThh:mm:ssZ';
  }

  /**
   *
   */
  public function identify() {
    $description = new \DOMDocument();
    $oai_identifier = $description->createElement('oai-identifier');

    $oai_identifier->setAttribute('xmlns', 'http://www.openarchives.org/OAI/2.0/oai-identifier');
    $oai_identifier->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
    $oai_identifier->setAttribute('xsi:schemaLocation', 'http://www.openarchives.org/OAI/2.0/oai-identifier http://www.openarchives.org/OAI/2.0/oai-identifier.xsd');
    $description->appendChild($oai_identifier);

    return new Identity(
      $this->siteName,
      new \DateTime(),
      'transient',
      [
        $this->mail,
      ],
      $this->getGranularity(),
      'deflate',
      $description
    );
  }

  /**
   *
   */
  public function listSets() {
    throw new NoRecordsMatchException('This repository does not support sets.');
  }

  /**
   *
   */
  public function listSetsByToken($token) {
    throw new NoRecordsMatchException('This repository does not support sets.');
  }

  /**
   *
   */
  public function getRecord($metadataFormat, $identifier) {
    return $this->records[$identifier];
  }

  /**
   *
   */
  public function listRecords($metadataFormat = NULL, \DateTime $from = NULL, \DateTime $until = NULL, $set = NULL) {
    $token = NULL;
    if ($this->offset) {
        $token = $this->encodeResumptionToken($this->offset, $from, $until, $metadataFormat, $set);
    }

    return new RecordList($this->records, $token);
  }

    /**
     * Get resumption token
     *
     * @param int $offset
     * @param DateTime $from
     * @param DateTime $util
     * @param string $metadataPrefix
     * @param string $set
     * @return string
     */
    private function encodeResumptionToken($offset = 0, \DateTime $from = null,  \DateTime $util = null, $metadataPrefix = null, $set = null) {
        $params = [];
        $params['offset'] = $offset;
        $params['metadataPrefix'] = $metadataPrefix;
        $params['set'] = $set;
        $params['from'] = null;
        $params['until'] = null;

        if ($from) {
            $params['from'] = $from->getTimestamp();
        }

        if ($util) {
            $params['until'] = $util->getTimestamp();
        }

        return base64_encode(json_encode($params));
    }

  /**
   *
   */
  public function listRecordsByToken($token) {
      $params = $this->decodeResumptionToken($token);
      $token = NULL;
      if ($this->offset) {
          $token = $this->encodeResumptionToken($this->offset, NULL, NULL, $params['metadataPrefix']);
      }
      return new RecordList($this->records, $token);
  }

    /**
     * Decode resumption token
     * possible properties are:
     *
     * ->offset
     * ->metadataPrefix
     * ->set
     * ->from (timestamp)
     * ->until (timestamp)
     *
     * @param string $token
     * @return array
     */
    public function decodeResumptionToken($token) {
        $params = (array) json_decode(base64_decode($token));

        if (!empty($params['from'])) {
            $params['from'] = new \DateTime('@' . $params['from']);
        }

        if (!empty($params['until'])) {
            $params['until'] = new \DateTime('@' . $params['until']);
        }

        return $params;
    }

  /**
   *
   */
  public function listMetadataFormats($identifier = NULL) {
    return $this->formats;
  }

  /**
   *
   */
  public function setRecords(array $records) {
    $this->records = $records;
  }

  /**
   *
   */
  public function setMetadataFormats(array $formats) {
    $this->formats = $formats;
  }

  public function setOffset(int $offset) {
    $this->offset = $offset;
  }

  public function setTotalRecords(int $total) {
    $this->totalRecords = $total;
  }

}
