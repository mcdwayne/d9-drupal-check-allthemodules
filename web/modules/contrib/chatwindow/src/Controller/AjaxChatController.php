<?php

namespace Drupal\chatwindow\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;



/**
 * Controller routines for AJAX chat routes.
 */
class AjaxChatController extends ControllerBase {
  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'chatwindow';
  }
	
	
	public function call_ajax(Request $request){
		
		
			//check method
		if(\Drupal::request()->getMethod() == 'POST')
		{	
	
			//initilizing some parameter
			$boterror = '';
			$response = '';
	
				 // get your POST parameter
			$userquery = $request->request->get('query');		
		
		
			$connection = \Drupal::database();
		
			$fields = array(
			  'uid' => \Drupal::currentUser()->id(),
			  'chatdata' => $userquery,
			  'databy' => 'user',
			  'datasenttouser' => 1,
			  'created' => time()
			);
			
			$connection->insert('chatwindow')
			  ->fields($fields)
			  ->execute();
			  
			
				// post the data to rasa server 
			$post = json_encode([
						'sender' => \Drupal::currentUser()->id(),
						'message' => $userquery
						
					]);
			
			
			$config = \Drupal::config('chatwindow.settings');
			
			$url = $config->get('botposturl');
			$curlwaittime = $config->get('curlwaittime');
			
			if(empty($curlwaittime))
			{

				$curlwaittime = 10;
			}
			
			if(empty($url))
			{

				$url = 'http://localhost:5004/webhook';

			}
			
			

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json'));
			curl_setopt($ch, CURLOPT_URL, $url);

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
			# Return response instead of printing.
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,$curlwaittime); 
			curl_setopt($ch, CURLOPT_TIMEOUT, $curlwaittime); 
			# Send request.
			$response = curl_exec($ch);  
			$err = curl_error($ch);
			curl_close($ch);
			if ($err) {    
			  $response = 'Error : Response time out';    
			}
			else if($response == 'success')
			{
					// in case no response from bot then send the below message
					$response = 'No response from Bot';			
				// show the message that was not sent to the user
			
				$query = $connection->select('chatwindow', 'chat')
				  ->condition('chat.datasenttouser', 0, '=')
				  ->condition('chat.databy', 'rasa', '=')
				  ->condition('chat.uid', \Drupal::currentUser()->id(), '=')
				  ->fields('chat', ['chatdata','pid'])
				  ->range(0, 50); 
				
				
				// single result
				if($query->countQuery()->execute()->fetchField() == 1)
				{
					
					$record = $query->execute()->fetchObject();

						
						$response = $record->chatdata;						
						
						// update the status for the data that was sent to the user
						$updatequery = $connection->update('chatwindow')
						  ->fields([
							'datasenttouser' => 1						
						  ])
						  ->condition('pid',$record->pid, '=');
						  $updatequery->execute();
						  //dump($updatequery->__toString());
						
					

				}
				// mulitple result . Ex checking for options like yes or no (button)
				elseif($query->countQuery()->execute()->fetchField() > 1)
				{
					$response = [];
					
					global  $base_url;
					
					
				
					$result = $query->execute();
					foreach ($result as $rowid=>$record) {
						
							
						//$record->chatdata = str_replace(DRUPAL_ROOT,$base_url,$record->chatdata)
						
						
						//check if first 6 letter is having 'Image:' etc
					/*	$typeofdata = substr($record->chatdata, 0, 6);
						
						
						
						switch($typeofdata) {
						
						// for showing image in the bot
							case 'Image:':
								
								
								array_push($response['image'.$rowid], $record->chatdata);
							
								break;
							
							default:
							
								
							
							break;
						
						}						
						*/
						
						array_push($response, $record->chatdata);
						//$response .= $record->chatdata;						
						
						// update the status for the data that was sent to the user
						$updatequery = $connection->update('chatwindow')
						  ->fields([
							'datasenttouser' => 1						
						  ])
						  ->condition('pid',$record->pid, '=');
						  $updatequery->execute();
						  //dump($updatequery->__toString());
						
					}
				}
				
	

			}
			else {
				// there might be issue with the rasa http server. 
				$boterror = 'There was some issue while connecting to the bot. Check the log files';	
					
			}
			
			

			
			

			$array = ['botreply' => $response,'error'=>$boterror];
			
			
			
			return new JsonResponse($array, 200, ['Content-Type'=> 'application/json']);
			
		}
		
			return new Response('Failure',Response::HTTP_OK);

		
	}
	
	public function rasadata(Request $request)
	{
		
		
		$message = $request->request->get('message');
		$recipientId = $request->request->get('recipient_id');
		$accesstoken = $request->headers->get('Auth-token');
		$buttons = $request->request->get('button');
		
		/*	
		$myfile = fopen("logs.txt", "wr") or die("Unable to open file!");
		$txt = '$message '.$message.' $recipientId '.$recipientId.' $accesstoken '.$accesstoken;
		fwrite($myfile, $txt);
		fclose($myfile);	
		*/
		
		
		
		//\Drupal::logger('rasa')->error(json_encode($request->request->all()));
		
		
		if(!empty($accesstoken))
		{
				
	
				$config = \Drupal::config('chatwindow.settings');				
				$chatwindowaccesstoken = $config->get('accesstoken');

				
			if($accesstoken == $chatwindowaccesstoken)
			{
				
				
					if(!empty($buttons))
					{

						
						$tempmessage['message'] = $message;		
						$tempmessage['button'] = json_decode($buttons);
						$message = json_encode($tempmessage);


					}
				
					$connection = \Drupal::database();
				
					$fields = array(
					  'uid' => $recipientId,
					  'chatdata' => $message,
					  'databy' => 'rasa',
					  'datasenttouser' => 0,
					  'created' => time()
					);
					
					$connection->insert('chatwindow')
					  ->fields($fields)
					  ->execute();
				
				$array = ['clientmessage'=> 'success'];
				
				return new JsonResponse($array, 200, ['Content-Type'=> 'application/json']);	  

			}
			
		}

				$array = ['clientmessage'=> 'You are not allowed to post. Authentication data did not match'];
				
				return new JsonResponse($array, 200, ['Content-Type'=> 'application/json']);	
		
	}
	
	public function imagecrop(Request $request)
	{
		
		$message = $request->request->get('message');
		$recipientId = $request->request->get('recipient_id');
		$accesstoken = $request->headers->get('Auth-token');
		$imagepath = $request->request->get('srcimagepath');
		
		global $base_url;	

		// remove the query string from image path
		$tmpimagepath =	explode('?',str_replace($base_url,DRUPAL_ROOT,$imagepath));
		
		$tmpimagepath[0] = urldecode($tmpimagepath[0]);
		
		// get extension of the image
		$ext = pathinfo($tmpimagepath[0], PATHINFO_EXTENSION);
		
		
		
		
		if(!empty($accesstoken))
		{
				
								
	
				$config = \Drupal::config('chatwindow.settings');				
				$chatwindowaccesstoken = $config->get('accesstoken');

				//log it to the database
			if($accesstoken == $chatwindowaccesstoken)
			{
				
					$connection = \Drupal::database();
				
					$fields = array(
					  'uid' => $recipientId,
					  'chatdata' => $message.$imagepath,
					  'databy' => 'rasa',
					  'datasenttouser' => 0,
					  'created' => time()
					);
					
					$connection->insert('chatwindow')
					  ->fields($fields)
					  ->execute();
				
				
				$imagename = basename($tmpimagepath[0]);
				
				
				
				$foldernames = explode('/',$tmpimagepath[0]);
				
				$lastSecondfolder = $foldernames[count($foldernames)-3];
				$lastFirstfolder = $foldernames[count($foldernames)-2];
				
				$originalImage = '';
				
				$query = $connection->select('file_managed','fm')
							->condition('uri' , 'public://'.$lastSecondfolder.'/'.$lastFirstfolder.'/'.$imagename,'=');
							
							
							//$connection->select('mytable')->countQuery()->execute()->fetchField();
														
						
				// check for 2 level of folder
				//$secondLevelcount = $query->countQuery()->execute()->fetchField();
				
				if($query->countQuery()->execute()->fetchField())
				{
					
					$originalImage = \Drupal::service('file_system')->realpath(file_default_scheme() . "://").'/'.$lastSecondfolder.'/'.$lastFirstfolder.'/'.$imagename;
					
				}
				else {
					
							// check for one level of folder
							$query = $connection->select('file_managed','fm')							
							->condition('uri' , 'public://'.$lastFirstfolder.'/'.$imagename,'=');
							
							
							if($query->countQuery()->execute()->fetchField())
							{

								$originalImage = \Drupal::service('file_system')->realpath(file_default_scheme() . "://").'/'.$lastFirstfolder.'/'.$imagename;

							}
							else {								
									// check for public folder
								$query = $connection->select('file_managed','fm')								
								->condition('uri' , 'public://'.$imagename,'=');								
								
								if($query->countQuery()->execute()->fetchField())
								{

									$originalImage = \Drupal::service('file_system')->realpath(file_default_scheme() . "://").'/'.$imagename;							
									
								}							
							}
				}


					
					$CVdirectory = \Drupal::service('file_system')->realpath(file_default_scheme() . "://").'/opencv';
					//folder name to be unixtimestamp based . Will help in debug the issue
					$currentfoldername = time();
					
					$erromessage = 'Failure';
					
					// if the style image and the original image exists
				if(file_exists($tmpimagepath[0]) && file_exists($originalImage))
				{

					list($width, $height) = getimagesize($tmpimagepath[0]); 
					
					
					//$oldmask = umask(0);
					
					if(!is_dir(\Drupal::service('file_system')->realpath(file_default_scheme() . "://").'/opencv'))
					{

				
						mkdir(\Drupal::service('file_system')->realpath(file_default_scheme() . "://").'/opencv',0777);
						//chmod(\Drupal::service('file_system')->realpath(file_default_scheme() . "://").'/opencv', 0666);
						

					}
					
					if(!is_dir(\Drupal::service('file_system')->realpath(file_default_scheme() . "://").'/opencv/faces'))
					{
						mkdir(\Drupal::service('file_system')->realpath(file_default_scheme() . "://").'/opencv/faces',0777);
						//chmod(\Drupal::service('file_system')->realpath(file_default_scheme() . "://").'/opencv/faces',0666);
						
					}	
					
					
					
					
					mkdir(\Drupal::service('file_system')->realpath(file_default_scheme() . "://").'/opencv/faces/'.$currentfoldername,0777);
					//chmod(\Drupal::service('file_system')->realpath(file_default_scheme() . "://").'/opencv/faces/'.$currentfoldername,0666);
					
					
					//umask($oldmask);
					
					$opencvconfig = \Drupal::config('chatwindow.opencv');
					
					 //$opencvconfig->get('haarcascadexml');
					
					//$haracascadepath = '/home/user/python/opencv-3.3.0/data/haarcascades/haarcascade_frontalface_default.xml';
					//$haracascadepath = $opencvconfig->get('haarcascadexml');
					
					//$Imagefacedetectioncodepath = '/home/user/rasa/weatherbot/Full_Code_latest/opencv/imagecrop.py';
					$Imagefacedetectioncodepath = $opencvconfig->get('pythoncodeforcropping');
					//$cvpath = '/user/.virtualenvs/cv/bin/python3.4';
					$cvpath = $opencvconfig->get('opencvbinpath');
					
					
					if(!empty($cvpath) && !empty($Imagefacedetectioncodepath)  && !empty($originalImage) && !empty($width) && !empty($height) && !empty($ext) && !empty($CVdirectory)  && !empty($currentfoldername) && !empty($currentfoldername)) 
					{
						//$command = exec($cvpath.' '.$Imagefacedetectioncodepath.' '.$originalImage.' '.$width.' '.$height.' '.$ext.' '.$CVdirectory.' '.$haracascadepath.' '.$currentfoldername.' '. $imagename);
						$command =$originalImage.' '.$width.' '.$height.' '.$ext.' '.$CVdirectory.' '.$currentfoldername.' '. $imagename;
						
					}	
					 	
					
						
				}
				else {
					
					
					//show the error message
					if(!file_exists($tmpimagepath[0]))
					{
						
						$erromessage =  'File/Image does not exist in the server';


					}
					elseif(!file_exists($originalImage))
					{
		
						$erromessage =  'Cropping can be done only for the image resized from image styles';

					}
					
					
					//$erromessage .= (!file_exists($tmpimagepath[0])) . '  '.(!file_exists($originalImage));
					
					
					
				}
				
				//if(file_exists($CVdirectory.'/faces/'.$currentfoldername.'/'.$imagename))
				if(!empty($command))
				{	
					
					$array = ['command'=>$command,'clientmessage'=> 'Success','originalimagepath' =>$originalImage,'croppedimage'=> $CVdirectory.'/faces/'.$currentfoldername.'/'.$imagename,'croppedimageurl'=> str_replace(DRUPAL_ROOT,$base_url,$CVdirectory.'/faces/'.$currentfoldername.'/'.$imagename)];
				}
				else {
					
					$array = ['command'=>$command,'clientmessage'=> $erromessage,'croppedimage'=> $CVdirectory.'/faces/'.$currentfoldername.'/'.$imagename];
				}	
				
				return new JsonResponse($array, 200, ['Content-Type'=> 'application/json']);	  

			}
			
		}
		

		
		$array = ['clientmessage'=> 'You are not allowed to post. Authentication data did not match '.DRUPAL_ROOT.$tmpimagepath .' '.$accesstoken.' '.$tmpimagepath[0]];
				
		return new JsonResponse($array, 200, ['Content-Type'=> 'application/json']);



				//exec($cmd . " > /dev/null &");  
		
		
	}	
	
	public function ReplaceCroppedimage(Request $request)
	{
		$cropimageurl  = $request->request->get('cropimage');
		$croppedimageurl = $request->request->get('croppedimageurl');
		$recipientid = $request->request->get('recipient_id');
		$authtoken = $request->headers->get('Auth-token');
		
		
		$messagetobot = ['clientmessage'=> 'Failure'];
		
		
		$config = \Drupal::config('chatwindow.settings');				
		$chatwindowaccesstoken = $config->get('accesstoken');
		
		if($request->getMethod() == 'POST' && !empty($authtoken))
		{
			
			
			
			$accesstoken = \Drupal::config('chatwindow.settings')->get('accesstoken');
			
			if($accesstoken == $authtoken) 
			{
				
				
				global $base_url;	

				// remove the query string from image path
				$tmpimagepath =	explode('?',str_replace($base_url,DRUPAL_ROOT,$cropimageurl));

				$tmpimagepath[0] = urldecode($tmpimagepath[0]);
				
				
				// remove the query string from image path
				$tmpcroppedimageurl =	explode('?',str_replace($base_url,DRUPAL_ROOT,$croppedimageurl));

				$tmpcroppedimageurl[0] = urldecode($tmpcroppedimageurl[0]);
				
				if(file_exists($tmpimagepath[0]) && file_exists($tmpcroppedimageurl[0]) && copy($tmpcroppedimageurl[0],$tmpimagepath[0]))
				{

					$messagetobot = ['clientmessage'=> 'Success'];


				}
					
				
				
				
				//$messagetobot 
				
				
				

				//$messagetobot	

			}
			
		}	
		
		
		
		
		return new JsonResponse($messagetobot, 200, ['Content-Type'=> 'application/json']);
		
	}	


}	