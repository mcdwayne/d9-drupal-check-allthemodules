<?php

namespace Drupal\drd\Plugin\Action;

use Drupal\drd\Entity\BaseInterface as RemoteEntityInterface;
use Drupal\drd\HttpRequest;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Provides a 'Download' action.
 *
 * @Action(
 *  id = "drd_action_download",
 *  label = @Translation("Download a file"),
 *  type = "drd_domain",
 * )
 */
class Download extends BaseEntityRemote {

  /**
   * {@inheritdoc}
   */
  public function executeAction(RemoteEntityInterface $domain) {
    $response = parent::executeAction($domain);
    if ($response) {
      /* @var \Drupal\drd\Entity\DomainInterface $domain */
      if ($this->responseHeaders['X-DRD-Encrypted'][0]) {
        $fs = new Filesystem();
        $newfile = $this->arguments['destination'] . '.openssl';
        if ($fs->exists($newfile)) {
          $fs->remove($newfile);
        }
        $fs->rename($this->arguments['destination'], $newfile);
        $this->crypt->decryptFile($newfile);
      }
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  protected function setRequestOptions(HttpRequest $request) {
    $request->setOption('sink', $this->arguments['destination']);
  }

  /**
   * {@inheritdoc}
   */
  protected function processResponse() {
    return FALSE;
  }

}
