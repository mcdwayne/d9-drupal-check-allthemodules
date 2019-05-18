<?php
namespace Drupal\pause_player\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
//use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;

/**
 * Field formatter.
 * Plugin implementation of the 'Pause Player' formatter.
 *
 * @FieldFormatter(
 *   id = "pause_player",
 *   label = @Translation("Pause Player"),
 *   field_types = {
 *     "link",
 *     "file"
 *   }
 * )
 */
class PausePlayerFormatter extends FormatterBase {

  /**
   * Define fields types that can be managed by the module (the display of fields will be managed by the module) : FileItem, LinkItem
   * {@inheritdoc}
   * $langcode : fr, en...
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
  	//$elements = parent::viewElements($items);
  	$elements = array();
  	//TODO : it's recommended to use getEntitiesToView() instead of $items directly
  	//$items = $this->getEntitiesToView($items, $langcode);
  	
  	//Get settings
  	$settings = $this->getSettings();
  	//Format width and height values
  	if (is_numeric($settings['width'])) {
  	    $settings['width'] .= 'px';
  	}
  	if (is_numeric($settings['height'])) {
  	    $settings['height'] .= 'px';
  	}
  	//Transform checkboxs values
  	$settings['autoplay'] = $settings['autoplay'] ? 'true' : 'false';
  	$settings['mute'] = $settings['mute'] ? 'true' : 'false';
  	$settings['debug'] = $settings['debug'] ? 'true' : 'false';
  	$settings['confirm_commercial_version'] = $settings['confirm_commercial_version'] ? 'true' : 'false';
  	
  	foreach ($items as $key => $item) {
  		//$fieldLabel = $item->getFieldDefinition()->getLabel();
  		
  		/*
  		 get_class($item) :
  		 Drupal\file\Plugin\Field\FieldType\FileItem
		 Drupal\link\Plugin\Field\FieldType\LinkItem
		 Drupal\video\Plugin\Field\FieldType\VideoItem
		 */
  		$className = get_class($item);
  		//VideoItem : based on FileItem. VideoItem is not implemented yet because extern videos are not managed by the player.
  		if ($className == 'Drupal\file\Plugin\Field\FieldType\FileItem') { // || $className == 'Drupal\video\Plugin\Field\FieldType\VideoItem'
  			$fileInfos = $item->getValue();
  			if (isset($fileInfos['target_id'])) {
	  			$file = \Drupal\file\Entity\File::load($fileInfos['target_id']);
	  			//$description = isset($fileInfos['description']) ? $fileInfos['description'] : '';
	  			if ($file != null) {
	  				$filename = $file->getFilename(); //Ex : video_16_9.mp4
	  				$url = $file->url(); //Ex : http://127.0.0.1:82/sites/default/files/2017-08/video_16_9.mp4
	  				$mime = $file->getMimeType(); //Ex : video/mp4
	  				$sizeO = $file->getSize(); //Ex : 124934 o (/1024 for Ko)
	  				$sizeKo = is_numeric($sizeO) ? (round($sizeO / 1024)) : 0;
	  				$userOwnerName = '';
	  				$userOwner = $file->getOwner();
	  				if ($userOwner != null) {
	  					$userOwnerName = $userOwner->getDisplayName(); //Ex : admin
	  				}
	  				$creationDate = $file->getCreatedTime(); // Ex : 1502816888
	  				$creationDateFormat = (is_numeric($creationDate)) ? date('Y-m-d H:i', (int)$creationDate) : '';
	  				$modificationDate = $file->getChangedTime(); // Ex : 1502816888
	  				$modificationDateFormat = (is_numeric($modificationDate)) ? date('Y-m-d H:i', (int)$modificationDate) : '';
	  				
	  				$title = $filename;
	  				$filenameInfos = pathinfo($filename);
	  				$title = $filenameInfos['filename']; //filename without extension
	  				if (trim($settings['title']) != '') {
	  				    $title = $settings['title'];
	  				}
	  				
	  				//Get infos for formatter
	  				$elements[$key] = array(
  						'#theme' => 'pause_player_formatter',
	  					'#field_type' => 'file',
	  					'#idvideo' => uniqid('pauseplayer_'), //Ex : pauseplayer_557c6206872ce
  						'#url' => $url,
  						'#filename' => $filename,
	  					'#title' => $title,
  						'#mime' => $mime,
  						'#size' => $sizeKo,
  						'#userOwnerName' => $userOwnerName,
  						'#creationDate' => $creationDate,
	  					'#creationDateFormat' => $creationDateFormat,
	  					'#modificationDate' => $modificationDate,
	  					'#modificationDateFormat' => $modificationDateFormat,
  						'#attached' => array(
  							'library' => array('pause_player/pause_player_library')
  						),
	  				    '#settings' => $settings
	  				);
	  			}
  			}
  		}
  		//LinkItem
  		if ($className == 'Drupal\link\Plugin\Field\FieldType\LinkItem') {
  			if (!$item->isEmpty()) {
	  			$urlO = $item->getUrl(); //Drupal\Core\Url object
	  			if ($urlO != null) {
	  				$url = $urlO->getUri(); //Ex : http://www.youtube.com/watch/L1yB7PxqpJw
	  				//IsExternal(), isRouted()
	  				if ($urlO->isRouted()) {
	  					$routeName = $urlO->getRouteName(); //External URL do not have an internal route name
	  					$internalPath = $urlO->getInternalPath(); //Unrouted URI do not have internal representations
	  				}
	  				$title = (isset($item->title) && !is_null($item->title)) ? $item->title : ''; //Text link
	  				if (trim($settings['title']) != '') {
	  				    $title = $settings['title'];
	  				}
	  				
	  				//Get infos for formatter
	  				$elements[$key] = array(
  						'#theme' => 'pause_player_formatter',
	  					'#field_type' => 'link',
	  					'#idvideo' => uniqid('pauseplayer_'), //Ex : pauseplayer_557c6206872ce
  						'#url' => $url,
	  					'#filename' => basename($url),
	  					'#title' => $title,
  						'#attached' => array(
  							'library' => array('pause_player/pause_player_library'),
  						),
	  				    '#settings' => $settings
	  				);
	  			}
  			}
  		}
  		
  		//If no informations
  		if (!isset($elements[$key])) {
  			$elements[$key] = $item;
  		}
  	}
  	
  	return $elements;
  }
  
  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
      return array(
          //video config
          'title' => '',
          'description' => '',
          'ratio' => '', //ratio : for correct ratio of the video : "", 4:3 (1,33:1), 5:4 (1,25:1), 16:9 (1,77:1), 185:100 (1,85:1), 235:100 (2,35:1 : cinemaScope), 239:100 (2,39:1)
          'image' => '', //path of an image that will be associated with the video
          //player config
          'width' => '480', //16:9
          'height' => '270',
          'cssclasses' => '',
          'appearmode' => 'onplay', //default (animation visible immediate), onpreload (player appear when preload of the first video is complete), onplay (play is launched by javascript, playbutton)
          'autoplay' => FALSE,
          'backgroundcolor' => 'transparent', //blue, #4682B4, transparent...
          'controlsdisplaymode' => 'disappear', //allthetime, disappear, never
          'looping' => 'none', //all, none, 0, 1, 2, 3 (index of video to loop)...
          'volume' => '80', //volume : 0 to 100. Volume forced if usersettings inactive. Volume is set but user settings have priority.
          'mute' => FALSE, //volume off
          'preloadtime' => '', //preload time before playback (in seconds)
          'playbutton' => 'both', //playbutton : none (playbutton hide most of the time, but show when necessarily (iOS suspended...)), javascript (playbutton always hide), both, start, end
          'videodisplaymode' => 'default', //default, noresize, stretch, cover (no black bars)
          'debug' => FALSE,
          //setDebugLevels : pauseplayer.DEBUGLEVEL_INFO | pauseplayer.DEBUGLEVEL_WARN | pauseplayer.DEBUGLEVEL_ERROR
          //playnonstop
          //loadPolicyFile
          //usersettings
          //language : fr, en
          
          //For commercial player
          'confirm_commercial_version' => FALSE,
          'startcontent_mode' => 'none', //none (no content), videoimage (the image associated with the video will be used, if not defined the application will attempt a screenshot of the current video), webimage (the image defined by the 'image' property will be used), html (an HTML element of the page will be displayed. This is an element (<div>) with the 'endcontent' class created by the developer inside the container element of the video player. Replacing an image, the HTML element allows unique realizations in HTML and CSS).
          'startcontent_displaymode' => 'cover', //display mode of the image in the frame of the video container : default , noresize, stretch, cover.
          'startcontent_image' => '', //path of the image if the 'webimage' mode is selected
          'endcontent_mode' => 'none', //none (no content), videoimage (the image associated with the video will be used, if not defined the application will attempt a screenshot of the current video), webimage (the image defined by the 'image' property will be used), html (an HTML element of the page will be displayed. This is an element (<div>) with the 'endcontent' class created by the developer inside the container element of the video player. Replacing an image, the HTML element allows unique realizations in HTML and CSS).
          'endcontent_displaymode' => 'cover', //display mode of the image in the frame of the video container : default , noresize, stretch, cover.
          'endcontent_image' => '', //path of the image if the 'webimage' mode is selected
          'disappearmode' => 'atend' //disappearmode (endcontent displayed when player disappear) : none (no endcontent), atend (when end of the playing of all videos), onstop, onpause
          //jscommunication/activeJSCommunication : callback, waitdom
      ) + parent::defaultSettings();
  }
  
  /**
   * Input fields for the settings form when the module is choosen for display a field : the administrator can define size of player, autoplay...
   * See defaultSettings() function for default settings.
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
      $field_name = $this->fieldDefinition->getName();
      
      $form = parent::settingsForm($form, $form_state);
      
      //video config
      $form['title'] = [
          '#title' => $this->t('Title of the video'),
          '#type' => 'textfield',
          '#default_value' => $this->getSetting('title'),
          '#description' => $this->t('If the value is empty, the title associated with the field will be used')
      ];
      $form['description'] = [
          '#title' => $this->t('Description of the video'),
          '#type' => 'textfield',
          '#default_value' => $this->getSetting('description')
      ];
      //ratio : for correct ratio of the video : "", 4:3 (1,33:1), 5:4 (1,25:1), 16:9 (1,77:1), 185:100 (1,85:1), 235:100 (2,35:1 : cinemaScope), 239:100 (2,39:1)
      $form['ratio'] = [
          '#title' => $this->t('Ratio'),
          '#type' => 'select',
          '#default_value' => $this->getSetting('ratio'),
          '#options' => array(
              '' => '',
              '4:3' => '4:3 (1,33:1)',
              '5:4' => '5:4 (1,25:1)',
              '16:9' => '16:9 (1,77:1)',
              '185:100' => '185:100 (1,85:1)',
              '235:100' => '235:100 (2,35:1 : cinemaScope)',
              '239:100' => '239:100 (2,39:1)'
          ),
          '#description' => $this->t('Force ratio only for correct the size of the video')
      ];
      //path of an image that will be associated with the video
      $form['image'] = [
          '#title' => $this->t('Image path'),
          '#type' => 'textfield',
          '#default_value' => $this->getSetting('image'),
          '#description' => $this->t('Path of an image that will be associated with the video')
      ];
      
      //player config
      $form['width'] = [
          '#title' => $this->t('Width'),
          '#type' => 'textfield',
          '#default_value' => $this->getSetting('width'),
          '#required' => TRUE,
          '#description' => $this->t('Specify a number value and its unit. Ex : 480, 480px, 100%')
      ];
      $form['height'] = [
          '#title' => $this->t('Height'),
          '#type' => 'textfield',
          '#default_value' => $this->getSetting('height'),
          '#required' => TRUE,
          '#description' => $this->t('Specify a number value and its unit. Ex : 270, 270px, 100%')
      ];
      $form['cssclasses'] = [
          '#title' => $this->t('CSS classes'),
          '#type' => 'textfield',
          '#default_value' => $this->getSetting('cssclasses'),
          '#description' => $this->t('Adds CSS classes on the HTML element container of the video player')
      ];
      $form['appearmode'] = [
          '#title' => $this->t('Appear mode'),
          '#type' => 'select',
          '#default_value' => $this->getSetting('appearmode'),
          '#options' => array(
              'default' => $this->t('Default (animation visible immediate)'),
              'onpreload' => $this->t('On preload (player appear when preload of the first video is complete)'),
              'onplay' => $this->t('On play (launched by play button)')
          ),
          '#required' => TRUE
      ];
      $form['autoplay'] = [
          '#title' => $this->t('Autoplay'),
          '#type' => 'checkbox',
          '#default_value' => $this->getSetting('autoplay')
      ];
      $form['backgroundcolor'] = [
          '#title' => $this->t('Player background color'),
          '#type' => 'textfield',
          '#default_value' => $this->getSetting('backgroundcolor'),
          '#description' => $this->t('HTML colors : blue, #4682B4, transparent...')
      ];
      $form['controlsdisplaymode'] = [
          '#title' => $this->t('Controls display mode'),
          '#type' => 'select',
          '#default_value' => $this->getSetting('controlsdisplaymode'),
          '#options' => array(
              'allthetime' => $this->t('All the time'),
              'disappear' => $this->t('Disappear'),
              'onplay' => $this->t('Never')
          ),
          '#required' => TRUE
      ];
      $form['looping'] = [
          '#title' => $this->t('Looping'),
          '#type' => 'select',
          '#default_value' => $this->getSetting('looping'),
          '#options' => array(
              'none' => $this->t('None'),
              'all' => $this->t('All videos'),
              '0' => $this->t('Video 1'),
              '1' => $this->t('Video 2'),
              '2' => $this->t('Video 3'),
              '3' => $this->t('Video 4')
          ),
          '#required' => TRUE
      ];
      $form['volume'] = [
          '#title' => $this->t('Default volume'),
          '#type' => 'select',
          '#default_value' => $this->getSetting('volume'),
          '#options' => array(
              '0' => '0%',
              '20' => '20%',
              '50' => '50%',
              '80' => '80%',
              '100' => '100%'
          )
      ];
      $form['mute'] = [
          '#title' => $this->t('Mute'),
          '#type' => 'checkbox',
          '#default_value' => $this->getSetting('mute')
      ];
      $form['preloadtime'] = [
          '#title' => $this->t('Preload time'),
          '#type' => 'textfield',
          '#default_value' => $this->getSetting('preloadtime'),
          '#description' => $this->t('Preload time before playback (in seconds).')
      ];
      $form['playbutton'] = [
          '#title' => $this->t('Play button display'),
          '#type' => 'select',
          '#default_value' => $this->getSetting('playbutton'),
          '#options' => array(
              'none' => $this->t('None'),
              'both' => $this->t('Both'),
              'start' => $this->t('Start'),
              'end' => $this->t('End')
          ),
          '#required' => TRUE
      ];
      $form['videodisplaymode'] = [
          '#title' => $this->t('Video display mode'),
          '#type' => 'select',
          '#default_value' => $this->getSetting('videodisplaymode'),
          '#options' => array(
              'default' => $this->t('Default'),
              'noresize' => $this->t('No resize'),
              'stretch' => $this->t('Stretch'),
              'cover' => $this->t('Cover (no black bars)')
          ),
          '#required' => TRUE,
          '#description' => $this->t('Video display mode inside the video player')
      ];
      $form['debug'] = [
          '#title' => $this->t('Debug'),
          '#type' => 'checkbox',
          '#default_value' => $this->getSetting('debug'),
          '#description' => $this->t('If debug is activated, informations about the playing are added to the browser console')
      ];
      //setDebugLevels : pauseplayer.DEBUGLEVEL_INFO | pauseplayer.DEBUGLEVEL_WARN | pauseplayer.DEBUGLEVEL_ERROR
      //playnonstop
      //loadPolicyFile
      //usersettings
      //language : fr, en
      
      //For commercial player
      $form['confirm_commercial_version'] = [
          '#title' => $this->t('I have the commercial version'),
          '#type' => 'checkbox',
          '#default_value' => $this->getSetting('confirm_commercial_version')
      ];
      $form['startcontent_mode'] = [
          '#title' => $this->t('Start content mode'),
          '#type' => 'select',
          '#default_value' => $this->getSetting('startcontent_mode'),
          '#options' => array(
              'none' => $this->t('None'),
              'videoimage' => $this->t('Video image'),
              'webimage' => $this->t('Web image')
          ),
          '#required' => TRUE,
          '#description' => $this->t('Sets a startup content that will be displayed before playing video'),
          '#states' => array(
              'visible' => array(
                  ':input[name="fields[' . $field_name . '][settings_edit_form][settings][confirm_commercial_version]"]' => ['checked' => TRUE],
              ),
          )
      ];
      $form['startcontent_displaymode'] = [
          '#title' => $this->t('Start content display mode'),
          '#type' => 'select',
          '#default_value' => $this->getSetting('startcontent_displaymode'),
          '#options' => array(
              'default' => $this->t('Default'),
              'noresize' => $this->t('No resize'),
              'stretch' => $this->t('Stretch'),
              'cover' => $this->t('Cover (no black bars)')
          ),
          '#required' => TRUE,
          '#description' => $this->t('Display mode of the image in the frame of the video container'),
          '#states' => array(
              'visible' => array(
                  ':input[name="fields[' . $field_name . '][settings_edit_form][settings][confirm_commercial_version]"]' => ['checked' => TRUE],
              ),
          )
      ];
      $form['startcontent_image'] = [
          '#title' => $this->t('Start content image path'),
          '#type' => 'textfield',
          '#default_value' => $this->getSetting('startcontent_image'),
          '#description' => $this->t('Path of the image if the \'Web image\' mode is selected'),
          '#states' => array(
              'visible' => array(
                  ':input[name="fields[' . $field_name . '][settings_edit_form][settings][confirm_commercial_version]"]' => ['checked' => TRUE],
              ),
          )
      ];
      $form['endcontent_mode'] = [
          '#title' => $this->t('End content mode'),
          '#type' => 'select',
          '#default_value' => $this->getSetting('endcontent_mode'),
          '#options' => array(
              'none' => $this->t('None'),
              'videoimage' => $this->t('Video image'),
              'webimage' => $this->t('Web image')
          ),
          '#required' => TRUE,
          '#description' => $this->t('Sets an ending content that will be displayed when the video playback stops'),
          '#states' => array(
              'visible' => array(
                  ':input[name="fields[' . $field_name . '][settings_edit_form][settings][confirm_commercial_version]"]' => ['checked' => TRUE],
              ),
          )
      ];
      $form['endcontent_displaymode'] = [
          '#title' => $this->t('End content display mode'),
          '#type' => 'select',
          '#default_value' => $this->getSetting('endcontent_displaymode'),
          '#options' => array(
              'default' => $this->t('Default'),
              'noresize' => $this->t('No resize'),
              'stretch' => $this->t('Stretch'),
              'cover' => $this->t('Cover (no black bars)')
          ),
          '#required' => TRUE,
          '#description' => $this->t('Display mode of the image in the frame of the video container'),
          '#states' => array(
              'visible' => array(
                  ':input[name="fields[' . $field_name . '][settings_edit_form][settings][confirm_commercial_version]"]' => ['checked' => TRUE],
              ),
          )
      ];
      $form['endcontent_image'] = [
          '#title' => $this->t('End content image path'),
          '#type' => 'textfield',
          '#default_value' => $this->getSetting('endcontent_image'),
          '#description' => $this->t('Path of the image if the \'Web image\' mode is selected'),
          '#states' => array(
              'visible' => array(
                  ':input[name="fields[' . $field_name . '][settings_edit_form][settings][confirm_commercial_version]"]' => ['checked' => TRUE],
              ),
          )
      ];
      $form['disappearmode'] = [
          '#title' => $this->t('Disappear mode'),
          '#type' => 'select',
          '#default_value' => $this->getSetting('disappearmode'),
          '#options' => array(
              'none' => $this->t('None (no end content)'),
              'atend' => $this->t('At end (when end of the playing of all videos)'),
              'onstop' => $this->t('On stop'),
              'onpause' => $this->t('On pause')
          ),
          '#required' => TRUE,
          '#description' => $this->t('Endcontent displayed when player disappear'),
          '#states' => array(
              'visible' => array(
                  ':input[name="fields[' . $field_name . '][settings_edit_form][settings][confirm_commercial_version]"]' => ['checked' => TRUE],
              ),
          )
      ];
      //jscommunication/activeJSCommunication : callback, waitdom
      
      return $form;
  }
  
  /**
   * Description displayed when the module is choosen for display a field
   * {@inheritdoc}
   */
  public function settingsSummary() {
      $summary = array();
      $summary[] = $this->t('Pause Player Video Settings (@widthx@height).', [
          '@width' => $this->getSetting('width'),
          '@height' => $this->getSetting('height')
      ]);
      return $summary;
  }

}
