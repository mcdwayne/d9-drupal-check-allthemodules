<?php

namespace Drupal\httpcsvalidation\Controller;

use Drupal\Core\Render\Markup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\httpcsvalidation\Helper\HttpcsFormHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class HttpcsController.
 *
 * @package Drupal\httpcsvalidation\Controller
 */
class HttpcsController extends ControllerBase {

  private $objFormHandler;
  private $url            = "";
  private $function       = "";
  private $company        = "";
  private $name           = "";
  private $phone          = "";
  private $email          = "";
  private $messageSuccess = "";
  private $messageError   = "";
  private $secondToken    = "";
  private $render         = "init";
  private $fail           = 0;
  private $event          = "";
  private $fileName       = "";
  private $fileContent    = "";
  private $dirname        = "";
  private $securedToken   = "";
  private $pathToInc      = "";

  private $formRetry = [];

  private $PATHHTTPCS             = '';
  private $PATHCHECKURLEMAIL      = '';
  private $PATHCHECKEMAILPASSWORD = '';
  private $PATHCHECKFILE          = '';
  private $PATHCHECKFILEAGAIN     = '';
  private $PATHCONNECTION         = '/user/login';
  private $PATHFORGOTPASSWORD     = '/user/reset-password';

  protected $form;
  protected $file;
  protected $languageManager;
  protected $configFactory;

  /**
   * Constructor.
   */
  public function __construct(FormBuilderInterface $form, FileSystem $file_system, LanguageManagerInterface $languageManager, ConfigFactoryInterface $configFactory) {
    $configArray = [];
    $this->pathToInc = drupal_get_path('module', 'httpcsvalidation') . '/src/Includes/';
    include_once $this->pathToInc . 'httpcsConfig.php';
    $this->PATHHTTPCS             = $configArray->PATHHTTPCS;
    $this->PATHCHECKURLEMAIL      = $configArray->PATHCHECKURLEMAIL;
    $this->PATHCHECKEMAILPASSWORD = $configArray->PATHCHECKEMAILPASSWORD;
    $this->PATHCHECKFILE          = $configArray->PATHCHECKFILE;
    $this->PATHCHECKFILEAGAIN     = $configArray->PATHCHECKFILEAGAIN;
    $this->objFormHandler         = new HttpcsFormHandler();
    $this->form                   = $form;
    $this->file                   = $file_system;
    $this->languageManager        = $languageManager;
    $this->configFactory          = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('file_system'),
      $container->get('language_manager'),
      $container->get('config.factory')
    );
  }

  /**
   * Init.
   */
  public function init() {
    global $base_url;
    $path = drupal_get_path('module', 'httpcsvalidation');

    $params = [];

    $form = $this->form->getForm('Drupal\httpcsvalidation\Form\HttpcsForm');
    $formCo = $this->form->getForm('Drupal\httpcsvalidation\Form\HttpcsCoForm');
    $this->formRetry = $this->form->getForm('Drupal\httpcsvalidation\Form\HttpcsRetryForm');

    $isValid = $this->config('httpcsvalidation.settings')->get('validated');

    if (!$this->isCurl()) {
      $this->render = 'curldisabled';
    }
    else {
      $formTmp = $form;
      if (isset($formCo['httpcs_co']['#value']) && !empty($formCo['email']['#value'])) {
        $formTmp = $formCo;
      }
      if (isset($this->formRetry['httpcs_retry']['#value']) && !empty($this->formRetry['email']['#value'])) {
        $formTmp = $this->formRetry;
      }

      if ((isset($form['httpcs_crea']['#value']) || isset($formCo['httpcs_co']['#value']) || isset($this->formRetry['httpcs_retry']['#value'])) && ((!empty($form['email']['#value'])) || !empty($formCo['email']['#value']) || !empty($this->formRetry['email']['#value']))) {
        $params['httpcs_crea'] = (isset($formTmp['httpcs_crea']['#value']) ? $formTmp['httpcs_crea']['#value'] : '');
        $params['httpcs_co'] = (isset($formTmp['httpcs_co']['#value']) ? $formTmp['httpcs_co']['#value'] : '');
        $params['httpcs_retry'] = (isset($formTmp['httpcs_retry']['#value']) ? $formTmp['httpcs_retry']['#value'] : '');
        $params['name'] = (isset($formTmp['name']['#value']) ? htmlentities($formTmp['name']['#value']) : '');
        $params['function'] = (isset($formTmp['function']['#value']) ? htmlentities($formTmp['function']['#value']) : '');
        $params['company'] = (isset($formTmp['company']['#value']) ? htmlentities($formTmp['company']['#value']) : '');
        $params['phone'] = (isset($formTmp['phone']['#value']) ? htmlentities($formTmp['phone']['#value']) : '');
        $params['email'] = (isset($formTmp['email']['#value']) ? htmlentities($formTmp['email']['#value']) : '');
        $params['password'] = (isset($formTmp['password']['#value']) ? htmlentities($formTmp['password']['#value']) : '');
        $params['url'] = (isset($formTmp['url']['#value']) ? $formTmp['url']['#value'] : '');
        $params['event'] = (isset($formTmp['event']['#value']) ? $formTmp['event']['#value'] : '');
        $params['secondToken'] = (isset($formTmp['secondToken']['#value']) ? $formTmp['secondToken']['#value'] : '');
      }

      $this->objFormHandler->handleForm($params);

      if ((isset($params['httpcs_crea']) || isset($params['httpcs_co']) || isset($this->formRetry['httpcs_retry']['#value'])) && $isValid !== '1') {
        // Creation.
        if (isset($params['httpcs_crea']) && $params['httpcs_crea'] === 'Y') {
          $this->creation($this->objFormHandler);
        }
        // Connexion.
        if (isset($params['httpcs_co']) && $params['httpcs_co'] === 'Y') {
          $this->connexion($this->objFormHandler);
        }
        // Retry.
        if (isset($params['httpcs_retry']) && $params['httpcs_retry'] === 'Y') {
          $this->retry($this->objFormHandler);
        }
      }
    }

    if ($isValid === '1') {
      $this->render = "validated";
    }

    $locale = 'en';
    if ($this->languageManager->getCurrentLanguage()->getId() == 'fr') {
      $locale = 'fr';
      $this->PATHCONNECTION = '/utilisateur/connexion';
      $this->PATHFORGOTPASSWORD = '/utilisateur/reinitialiser-password';
    }

    $returnArray = [
      '#theme' => $this->render,
      '#url' => $base_url,
      '#my_form' => $form,
      '#my_form_co' => $formCo,
      '#my_form_retry' => $this->formRetry,
      '#pathModule' => $path,
      '#fail' => $this->fail,
      '#notices' => $this->getNotices(),
      '#PATHHTTPCS' => $this->PATHHTTPCS,
      '#PATHCONNECTION' => $this->PATHCONNECTION,
      '#locale' => $locale,
      '#fileContent' => $this->fileContent,
      '#fileName' => $this->fileName,
      '#dirname' => $this->dirname,
      '#PATHFORGOTPASSWORD' => $this->PATHFORGOTPASSWORD,
    ];

    return $returnArray;
  }

  /**
   * Handles creation.
   */
  public function creation($objFormHandler) {
    $aFormHandler   = $objFormHandler->objToArray();
    $response[]     = $this->curlRequest($this->PATHCHECKURLEMAIL, $aFormHandler);
    $this->email    = $objFormHandler->getEmail();
    $this->name     = urldecode($objFormHandler->getName());
    $this->function = urldecode($objFormHandler->getFunction());
    $this->company  = urldecode($objFormHandler->getCompany());
    $this->phone    = urldecode($objFormHandler->getPhone());
    if ($response[0] != 'error') {
      if ($this->handleResponse($response[0])) {
        if (json_decode($response[0])->etat) {
          // Return   C:\wamp64\www\drupal\sites\default\files.
          $uploads = $this->file->realpath(file_default_scheme() . "://");
          // Return /sites/default/files.
          $relativePath = str_replace($this->file->realpath('.'), '', $uploads);
          $response[]   = $this->curlRequest($this->PATHCHECKFILE, [
            'url' => $aFormHandler['url'],
            'relativePath' => $relativePath . '/httpcs/',
            'token' => json_decode($response[0])->token,
            'email' => $this->email,
            'event' => 'creation',
          ]);
          if ($response[1] != 'error') {
            if (!json_decode($response[1])->etat) {
              $this->render                             = "retry";
              $this->secondToken                        = json_decode($response[1])->secondToken;
              $this->event                              = json_decode($response[1])->event;
              $this->formRetry['secondToken']['#value'] = $this->secondToken;
              $this->formRetry['event']['#value']       = $this->event;
              $this->formRetry['email']['#value']       = htmlentities($this->email);
            }
            else {
              $this->render = "success";
              $this->setValidation();
            }
          }
          else {
            $response = [];
            $response[0] = '{"etat":0,"code":8000}';
          }
        }
      }
      else {
        $uploads           = $this->file->realpath(file_default_scheme() . "://");
        $this->dirname     = dirname($uploads . '/httpcs/' . json_decode($response[0])->fileName);
        $this->fileName    = json_decode($response[0])->fileName;
        $this->fileContent = json_decode($response[0])->contentFile;
        $this->render      = "writingerror";
      }
    }
    else {
      $response = [];
      $response[0] = '{"etat":0,"code":8000}';
    }
    $this->renderResponses($response);
  }

  /**
   * Handles connexion.
   */
  public function connexion($objFormHandler) {
    $aFormHandler = $objFormHandler->objToArray();
    // Return   C:\wamp64\www\drupal\sites\default\files.
    $uploads = $this->file->realpath(file_default_scheme() . "://");
    // Return /sites/default/files.
    $relativePath   = str_replace($this->file->realpath('.'), '', $uploads);
    $this->email    = $objFormHandler->getEmail();
    $this->password = urldecode($objFormHandler->getPassword());

    $response[] = $this->curlRequest($this->PATHCHECKEMAILPASSWORD, [
      'url' => $aFormHandler['url'],
      'relativePath' => $relativePath . '/httpcs/',
      'password' => $aFormHandler['password'],
      'email' => $this->email,
    ]);
    if ($response[0] != 'error') {
      if (!json_decode($response[0])->etat) {
        $this->fail = 1;
      }
      else {
        if (isset(json_decode($response[0])->fileName)) {
          if ($this->handleResponse($response[0])) {
            $response[] = $this->curlRequest($this->PATHCHECKFILE, [
              'url' => $aFormHandler['url'],
              'relativePath' => $relativePath . '/httpcs/',
              'token' => json_decode($response[0])->token,
              'email' => $this->email,
              'event' => 'connexion',
            ]);
            if ($response[1] != 'error') {
              if (!json_decode($response[1])->etat) {
                $this->render                             = "retry";
                $this->secondToken                        = json_decode($response[1])->secondToken;
                $this->event                              = json_decode($response[1])->event;
                $this->formRetry['secondToken']['#value'] = $this->secondToken;
                $this->formRetry['event']['#value']       = $this->event;
                $this->formRetry['email']['#value']       = htmlentities($this->email);
              }
              else {
                $this->render = "successco";
                $this->setValidation();
              }
            }
            else {
              $response = [];
              $response[0] = '{"etat":0,"code":8000}';
            }
          }
          else {
            $uploads           = $this->file->realpath(file_default_scheme() . "://");
            $this->dirname     = dirname($uploads . '/httpcs/' . json_decode($response[0])->fileName);
            $this->fileName    = json_decode($response[0])->fileName;
            $this->fileContent = json_decode($response[0])->contentFile;
            $this->render      = "writingerror";
          }
        }
        else {
          $this->render = "successco";
          $this->setValidation();
        }
      }
    }
    else {
      $response = [];
      $response[0] = '{"etat":0,"code":8000}';
    }
    $this->renderResponses($response);
  }

  /**
   * Handles retry.
   */
  public function retry($objFormHandler) {
    $aFormHandler = $objFormHandler->objToArray();
    // Return   C:\wamp64\www\drupal\sites\default\files.
    $uploads = $this->file->realpath(file_default_scheme() . "://");
    // Return /sites/default/files.
    $relativePath      = str_replace($this->file->realpath('.'), '', $uploads);
    $this->email       = $objFormHandler->getEmail();
    $this->secondToken = urldecode($objFormHandler->getSecondToken());
    $this->event       = urldecode($objFormHandler->getEvent());
    $response[]        = $this->curlRequest($this->PATHCHECKFILEAGAIN, [
      'url' => $aFormHandler['url'],
      'relativePath' => $relativePath . '/httpcs/',
      'secondToken' => $this->secondToken,
      'email' => $this->email,
      'event' => $this->event,
    ]);
    if ($response[0] != 'error') {
      if (!json_decode($response[0])->etat) {
        if (json_decode($response[0])->code == '9008') {
          $this->render = "init";
        }
        else {
          $this->render = "retry";
        }
      }
      else {
        $this->render = "success";
        $this->setValidation();
      }
    }
    else {
      $response = [];
      $response[0] = '{"etat":0,"code":8000}';
    }
    $this->renderResponses($response);
  }

  /**
   * Handles responses of the web service and creates file.
   */
  public function handleResponse($aResponse = []) {
    if (!empty($aResponse)) {
      $aResult = json_decode($aResponse);
      $writingError = 1;
      if (isset($aResult->fileName) && isset($aResult->contentFile)) {
        $uploads = $this->file->realpath(file_default_scheme() . "://");
        $dirname = dirname($uploads . '/httpcs/' . $aResult->fileName);
        if (!is_dir($dirname)) {
          if (!mkdir($dirname, 0755, TRUE)) {
            $writingError = 0;
          }
        }
        if ($writingError) {
          if (is_writable($uploads . '/httpcs/')) {
            $handleFile = fopen($uploads . '/httpcs/' . $aResult->fileName, "w+");
            if ($handleFile) {
              fwrite($handleFile, $aResult->contentFile);
              fclose($handleFile);
            }
            else {
              $writingError = 0;
            }
          }
          else {
            $writingError = 0;
          }
        }
      }
      return $writingError;
    }
    return 0;
  }

  /**
   * Curl.
   */
  public function curlRequest($urlHttpcs, $aFormHandler) {
    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_URL            => $urlHttpcs,
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_MAXREDIRS      => 5,
      CURLOPT_TIMEOUT        => 30,
      CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
      CURLOPT_POST           => TRUE,
      CURLOPT_POSTFIELDS     => http_build_query($aFormHandler),
      CURLOPT_HEADER         => FALSE,
      CURLOPT_USERAGENT      => 'plugin-drupal',
    ]);
    $response = curl_exec($curl);
    $info = curl_getinfo($curl);
    if ($response === FALSE || empty($info) || $info['http_code'] != '200') {
      curl_close($curl);
      return 'error';
    }
    // $err = curl_error($curl);
    curl_close($curl);
    return ($response);
  }

  /**
   * Renders responses.
   */
  public function renderResponses($aResponses) {
    if (!empty($aResponses)) {
      foreach ($aResponses as $aResponse) {
        if (!empty($aResponse)) {
          $aResult = json_decode($aResponse);

          if ($aResult->etat) {
            $messageSuccess[] = $aResult->code;
          }
          else {
            $messageError[] = $aResult->code;
          }
        }
      }

      if (isset($messageSuccess)) {
        $this->messageSuccess = $messageSuccess;
      }
      if (isset($messageError)) {
        $this->messageError = $messageError;
      }
    }
  }

  /**
   * Gets success and error messages.
   */
  public function getNotices() {

    $notices = "";
    if (!empty($this->messageSuccess)) {
      $notices = '<div><p>';
      foreach ($this->messageSuccess as $code) {
        $notices .= $this->renderNotices($code, $this);
        $notices .= '<br>';
      }
      $notices .= '</p></div>';
      $rendered_message = Markup::create($notices);
      drupal_set_message($rendered_message, 'status');
    }
    if (!empty($this->messageError)) {
      $notices = '<div><p>';
      foreach ($this->messageError as $code) {
        $notices .= $this->renderNotices($code, $this);
        if ($code == '9002') {
          $notices .= ', <a id="coClick">' . $this->t('click here') . '</a>';
        }
        if ($code == '9006') {
          $notices .= ', <a id="creaClick">' . $this->t('click here') . '</a>';
        }
        $notices .= '<br>';
      }
      $notices .= '</p></div>';
      $rendered_message = Markup::create($notices);
      drupal_set_message($rendered_message, 'error');
    }

  }

  /**
   * Gets translations.
   */
  public function renderNotices($code, $instance) {
    $httpcsLang = [];

    $httpcsLang['1000'] = $this->t('Your account has been created successfully.');
    $httpcsLang['1001'] = $this->t('This website has already been validated.');
    $httpcsLang['1002'] = $this->t('Connection successful, creation of the file...');
    $httpcsLang['1003'] = $this->t('Verification done !');

    $httpcsLang['9000'] = $this->t('This email address is not allowed.');
    $httpcsLang['9001'] = $this->t('We already have informations from this website. Please, contact our assistance to claim the property.');
    $httpcsLang['9002'] = $this->t('This user already exists ! To log in');
    $httpcsLang['9003'] = $this->t('Please check your informations.');
    $httpcsLang['9004'] = $this->t("You're not the owner of this website.");
    $httpcsLang['9005'] = $this->t('Please check your email/password.');
    $httpcsLang['9006'] = $this->t("This user doesn't exist! To sign up");
    $httpcsLang['9007'] = $this->t('Unauthorized entry.');
    $httpcsLang['9008'] = $this->t('Operation not allowed.');
    $httpcsLang['9009'] = $this->t('The content of the file is not valid.');
    $httpcsLang['9010'] = $this->t('File not found. Please check if the file has been created and also the rights to read it remotely.');
    $httpcsLang['9011'] = $this->t('This website is not in our database.');
    $httpcsLang['9012'] = $this->t('Please, check the URL.');

    $httpcsLang['8000'] = $this->t('Sorry, we were not able to contact the HTTPCS validation service.');

    return $httpcsLang[$code];
  }

  /**
   * Store validation in database.
   */
  public function setValidation() {
    $this->configFactory->getEditable('httpcsvalidation.settings')->set('validated', '1')->save();
  }

  /**
   * Checks if Curl exists.
   */
  public function isCurl() {
    return function_exists('curl_version');
  }

}
