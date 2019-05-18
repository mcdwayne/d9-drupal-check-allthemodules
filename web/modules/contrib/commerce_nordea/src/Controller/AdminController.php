<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2019 Lamia Oy (https://lamia.fi)
 */


namespace Drupal\commerce_nordea\Controller;

use Drupal\commerce_nordea\DependencyInjection\KeysHelper;
use Drupal\Core\Controller\ControllerBase;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class AdminController extends ControllerBase
{

  /**
   * Returns a render-able array for a test page.
   */
  public function generateKeys(Request $request)
  {

    $success = true;
    $messages = array();

    $type = $request->request->get('keys_type');
    $id = $request->request->get('id');

    if (empty($type) || $type === false) {
      $success = false;
      $messages[] = $this->t('Problem with generating new keys.') . $this->t('Please refresh the page and try again.');
    }

    if (!$success) {
      return new JsonResponse(['success' => $success, 'messages' => $messages]);
    }

    $generator = new \Verifone\Core\DependencyInjection\CryptUtils\RsaKeyGenerator();
    $resultGenerate = $generator->generate();

    if ($resultGenerate) {
      $helper = new KeysHelper();
      $resultStoreKey = $helper->storeKeys($id, $type, $generator->getPublicKey(), $generator->getPrivateKey());

      if ($resultStoreKey === true) {
        $messages[] = $this->t('Keys are generated correctly. Please refresh or save the configuration.');
        return new JsonResponse(['success' => $success, 'messages' => $messages]);
      }

      $success = false;
      $messages[] = $resultStoreKey;

    } else {
      $success = false;
      $messages[] = $this->t('Problem with generating new keys.');
    }

    $response['type'] = $request->request->get('keys_type');

    $response['data'] = 'Not implemented yet!';
    $response['method'] = 'POST';

    return new JsonResponse(['success' => $success, 'messages' => $messages]);
  }


}