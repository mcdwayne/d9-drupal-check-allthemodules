<?php

namespace Drupal\drd_pi;

use Drupal\drd\Entity\Domain;
use GuzzleHttp\Client;

/**
 * Provides platform based domain.
 */
class DrdPiDomain extends DrdPiEntity {

  /**
   * The domain name.
   *
   * @var string
   */
  protected $domain;

  /**
   * Core to which this domains is attached.
   *
   * @var DrdPiCore
   */
  protected $core;

  /**
   * DRD logging service for console output.
   *
   * @var \Drupal\drd\Logging
   */
  protected $logging;

  /**
   * Set the core and domain name for this entity.
   *
   * @param DrdPiCore $core
   *   Core to which this domain is attached.
   * @param string $domain
   *   The domain name of this entity.
   *
   * @return $this
   */
  public function setDetails(DrdPiCore $core, $domain) {
    $this->core = $core;
    $this->domain = $domain;
    $this->logging = \Drupal::service('drd.logging');
    return $this;
  }

  /**
   * Determine full remote URL by testing https and http protocol.
   *
   * @param bool $secure
   *   Flag to determine which protocol to test.
   *
   * @return bool|string
   *   Returns the full remote URL including scheme with https as the preference
   *   or FALSE if none of the possible URLs responds to a head request.
   */
  private function determineUrl($secure = TRUE) {
    $scheme = $secure ? 'https' : 'http';
    $options = [];
    if (isset($this->header['Authorization'])) {
      $options['headers']['Authorization'] = $this->header['Authorization'];
    }
    $url = $scheme . '://' . $this->domain;
    $success = FALSE;
    try {
      $client = new Client(['base_uri' => $url]);
      $response = $client->request('head', '', $options);
      $statusCode = $response->getStatusCode();
      $success = ($statusCode < 300);
    }
    catch (\Exception $ex) {
      // Ignore.
    }
    if (!$success) {
      \Drupal::service('drd.logging')->debug('Can not connect to @url', [
        '@url' => $url,
      ]);
      if ($secure) {
        return $this->determineUrl(FALSE);
      }
      return FALSE;
    }
    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function host() {
    return $this->core->host();
  }

  /**
   * {@inheritdoc}
   */
  public function create() {
    if (!$url = $this->determineUrl()) {
      $this->logging->log('emergency', 'Unable to determine remote URL');
      return $this;
    }

    /* @var \Drupal\drd\Entity\CoreInterface $core */
    $core = $this->core->getDrdEntity();
    $this->entity = Domain::instanceFromUrl($core, $url, [
      'pi_type' => $this->account->getEntityTypeId(),
      'pi_account' => $this->account->id(),
      'pi_id_host' => $this->host()->id(),
      'pi_id_core' => $this->core->id(),
      'pi_id_domain' => $this->id,
    ]);

    /** @var \Drupal\drd\Entity\DomainInterface $domain */
    $domain = $this->getDrdEntity();
    /** @var \Drupal\drd\Entity\CoreInterface $core */
    $core = $this->core->getDrdEntity();

    $domain->initValues('');

    $this->entity->save();

    if ($domain->authorizeBySecret($this->account->getAuthorizationMethod(), $this->account->getAuthorizationSecrets($this))) {
      if (empty($core->getDrupalRoot())) {
        $domain->initCore($core);
      }
      else {
        $domain->remoteInfo();
      }
    }
    else {
      $this->logging->log('warning', 'This DRD instance is not yet authorized remotely.');
    }

    return $this;
  }

}
