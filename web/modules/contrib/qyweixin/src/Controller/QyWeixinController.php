<?php
/**
 * @file
 * Contains \Drupal\qyweixin\Controller\QyWeixinController.
 */

namespace Drupal\qyweixin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Image\ImageInterface;
use Drupal\qyweixin\CorpBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller routines for qyweixin routes.
 */
class QyWeixinController extends ControllerBase {
	public function defaultResponse(Request $request, RouteMatchInterface $routeMatch) {
		$entry=explode('.',$routeMatch->getRouteName());
		$agentId=$entry['3'];
		$entryClass=$entry['2'];
		
		if(empty($agentId) || empty($entryClass))  {
			throw new NotFoundHttpException('Could not found the agent.');
		}

		// All the parameters should be there.
		if(empty($request->get('msg_signature')) || empty($request->get('timestamp')) || empty($request->get('nonce'))) 
			throw new AccessDeniedHttpException('At least one of msg_signature, timestamp and nonce is missing.');
		
		// If we just get the GET to verify the URL, then return
		if($request->getMethod()==Request::METHOD_GET) {
			// Calculate msg_signature for verifying
			$echostr=CorpBase::verifyURL(
				$request->get('msg_signature'),
				$request->get('timestamp'),
				$request->get('nonce'),
				$request->get('echostr'),
				\Drupal::config('qyweixin.general')->get('agent.'.$agentId.'.token'),
				\Drupal::config('qyweixin.general')->get('agent.'.$agentId.'.encodingaeskey')
			);
			
			// If the returned string is empty, then the request is invalid
			if(empty($echostr)) throw new BadRequestHttpException('Validation failed.');
			
			\Drupal::logger('qyweixin')->info('Qyweixin connected with agent %agent / %entryclass.', ['%agent'=>$agentId, '%entryclass'=>$entryClass]);
			return new Response($echostr, 200, ['Content-Type'=>'text/plain']);
		}
		
		// We receive a customer message now
		$body=$request->getContent();
		$xml=new \DOMDocument();
		if($xml->loadXML($body)==FALSE||$xml->getElementsByTagName('ToUserName')->item(0)->nodeValue!==\Drupal::config('qyweixin.general')->get('corpid'))
			throw new BadRequestHttpException('The corpid is not correct.');
		
		try {
			$msg='';
			$msg=CorpBase::decryptMsg(
				$request->get('msg_signature'), $request->get('timestamp'), $request->get("nonce"), $body,
				\Drupal::config('qyweixin.general')->get('agent.'.$agentId.'.token'),
				\Drupal::config('qyweixin.general')->get('agent.'.$agentId.'.encodingaeskey')
			);
			
			$domObj=new \DOMDocument();
			$domObj->loadXML($msg, LIBXML_NOCDATA);
			$message=simplexml_import_dom($domObj);
			
			// Allow module to alter fromUsername
			$fromUsername=$message->FromUserName;
			\Drupal::moduleHandler()->alter('qyweixin_from_username', $fromUsername);
			$message->FromUserName=$fromUsername;
			
			// Find the correct agent entry class
			$agent=\Drupal::service('plugin.manager.qyweixin.agent')->createInstance(\Drupal::config('qyweixin.general')->get('agent.'.$agentId.'.entryclass'));
			
			switch($message->MsgType) {
				case 'event':
					// First to check if eventClickResponse is available
					if(is_callable([$agent, 'event'.ucwords($message->Event).'Response'])) {
						$ret=call_user_func([$agent, 'event'.ucwords($message->Event).'Response'], $message);
						break;
					}
				default:
					// Then check if eventResponse/textResponse is available
					if(is_callable([$agent, $message->MsgType.'Response'])) {
						$ret=call_user_func([$agent, $message->MsgType.'Response'], $message);
					} else
						$ret=$agent->defaultResponse($message);
			}
			
			// If the aganet plugin returns nothing, then the agent will send message by itself
			if(empty($ret)) return new Response('', 200, ['Content-Type'=>'text/plain']);
			
			switch(gettype($ret)) {
				case 'string': {
					// Construct response object
					$domObj=new \DOMDocument();
					$domObjXml=$domObj->createElement('xml');

					$element=$domObj->createElement('ToUserName');
					$cdata=$domObj->createCDATASection($message->FromUserName);
					$element->appendChild($cdata);
					$domObjXml->appendChild($element);
					
					$element=$domObj->createElement('FromUserName');
					$cdata=$domObj->createCDATASection($message->toUserName);
					$element->appendChild($cdata);
					$domObjXml->appendChild($element);
					
					$element=$domObj->createElement('CreateTime', time());
					$domObjXml->appendChild($element);
					
					$element=$domObj->createElement('MsgType');
					$cdata=$domObj->createCDATASection('text');
					$element->appendChild($cdata);
					$domObjXml->appendChild($element);
					
					$element=$domObj->createElement('Content');
					$cdata=$domObj->createCDATASection($ret);
					$element->appendChild($cdata);
					$domObjXml->appendChild($element);
					
					$domObj->appendChild($domObjXml);
					
					$xml=explode("?>\n", $domObj->saveXML())[1];

					$retobj=CorpBase::encryptMsg($xml, $request->get('timestamp'), $request->get("nonce"),
						\Drupal::config('qyweixin.general')->get('agent.'.$agentId.'.token'),
						\Drupal::config('qyweixin.general')->get('agent.'.$agentId.'.encodingaeskey'));
					break;
				}
				case 'object':
					if($ret instanceof ImageInterface) {
						
					}
			}
			return new Response($retobj, 200, ['Content-Type'=>'text/xml']);
		} catch (\Exception $e ) {
			throw new BadRequestHttpException($e->getMessage());
		}
	}
	
}

?>
