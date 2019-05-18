<?php

namespace Drupal\drd\Plugin\Action;

use Drupal\drd\Entity\BaseInterface as RemoteEntityInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Provides a 'Info' action.
 *
 * @Action(
 *  id = "drd_action_info",
 *  label = @Translation("Info"),
 *  type = "drd_domain",
 * )
 */
class Info extends BaseEntityRemote {

  /**
   * {@inheritdoc}
   */
  public function executeAction(RemoteEntityInterface $domain) {
    $response = parent::executeAction($domain);
    if ($response) {
      /* @var \Drupal\drd\Entity\DomainInterface $domain */
      if (!empty($response['settings']['container_yamls'])) {
        // Change container_yamls to relative paths.
        $fs = new Filesystem();
        $container_yamls = [];
        foreach ($response['settings']['container_yamls'] as $container_yaml) {
          if (strpos($container_yaml, '/redis/') === FALSE) {
            $container_yamls[] = trim($fs->makePathRelative($container_yaml, $response['root']), '/');
          }
        }
        $response['settings']['container_yamls'] = $container_yamls;
      }

      if (!empty($response['monitoring'])) {
        $this->analyzeMonitoring($response['monitoring'], $response['requirements']);
      }

      $domain
        ->setName($response['name'])
        ->cacheGlobals($response['globals'])
        ->cacheRequirements($response['requirements'])
        ->cacheSettings($response['settings'])
        ->cacheVariables($response['variables'])
        ->cacheReview($response['review'])
        ->cacheMonitoring($response['monitoring'])
        ->save();
      return $response;
    }
    return FALSE;
  }

  /**
   * Go through monitoring and add record to requirements.
   *
   * @param array $values
   *   The monitoring values.
   * @param array $requirements
   *   The requirements to which we add a new record.
   */
  private function analyzeMonitoring(array $values, array &$requirements) {
    // Load the install file, that also loads install.inc from Drupal core.
    \Drupal::moduleHandler()->loadInclude('drd', 'install');
    $severity = REQUIREMENT_OK;
    foreach ($values as $item) {
      switch ($item['status']) {
        case 'CRITICAL':
          $severity = REQUIREMENT_ERROR;
          break;

        case 'WARNING':
          $severity = max(REQUIREMENT_WARNING, $severity);
          break;

      }
    }

    switch ($severity) {
      case REQUIREMENT_WARNING:
        $description = $this->t('The monitoring recognized seomthing that you should pay attention for.');
        break;

      case REQUIREMENT_ERROR:
        $description = $this->t('The monitoring identified warnings for you.');
        break;

      default:
        $description = $this->t('The monitoring does not report any issues.');

    }

    // TODO: Add a link to the description.
    $requirements['drd.monitoring'] = [
      'title' => $this->t('Monitoring'),
      'description' => $description,
      'severity' => $severity,
    ];
  }

}
