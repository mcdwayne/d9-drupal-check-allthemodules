<?php
/**
 * Created by PhpStorm.
 * User: dev
 * Date: 15.03.18
 * Time: 16:35
 */

namespace Drupal\print_ninja\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Transliteration\PhpTransliteration;
use Drupal\field\Entity\FieldConfig;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\print_ninja\Form\ConfigForm;
use Drupal\user\Entity\User;
use Mpdf\Mpdf;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PdfController extends ControllerBase {

  private $pdfLogoPath = '';

  private $configData = [];

  public function exportPdf($nodeId) {
    $node = Node::load($nodeId);
    if ($node && $node->bundle() == \Drupal::config(ConfigForm::EDITABLE_CONFIG_NAME)
        ->get(ConfigForm::PARENT_TYPE)) {
      $this->configData = \Drupal::config(ConfigForm::EDITABLE_CONFIG_NAME)
        ->get();
      $logoFileId = $this->configData[ConfigForm::LOGO_IMAGE][0];
      if ($logoFileId) {
        $this->pdfLogoPath = File::load($logoFileId)->getFileUri();
      }
      $this->export($node);
      exit();
    }
    $url = \Drupal::request()->getRequestUri();
    $url = substr($url, 0, strlen($url) - 4);
    \Drupal::service('messenger')
      ->addMessage(t('This content type is not selected for being exported as a PDF files'), MessengerInterface::TYPE_ERROR);
    return new RedirectResponse($url);
  }

  private function export($node) {

    $mPdf = new Mpdf();
    $mPdf->SetImportUse();
    $mPdf->AddPage();

    $cssPath = __DIR__ . '/../../css/styles-original.css';
    if ($this->configData[ConfigForm::CSS_FILE]) {
      $cssPath = __DIR__ . '/../../css/' . $this->configData[ConfigForm::CSS_FILE];
    }
    if (file_exists($cssPath)) {
      $css = file_get_contents($cssPath);
      $mPdf->WriteHTML($css, 1);
    }

    $this->addLogo($mPdf);
    $title = $node->getTitle();
    $mPdf->WriteHTML("<h1>$title</h1>");
    $fieldsParent = array_keys($this->configData[ConfigForm::SELECTED_PARENT_FIELDS_FOR_EXPORT][$this->configData[ConfigForm::PARENT_TYPE]]);
    if (!empty($fieldsParent)) {
      // export selected parent fields
      foreach ($fieldsParent as $fieldName) {
        $field = $node->$fieldName;
        $fieldType = $field->getFieldDefinition()->getType();
        if ($fieldType == 'image' || $fieldType == 'file') {
          $files = $field->referencedEntities();
          $allowedImagesTypes = explode(' ', $field->getSetting('file_extensions'));
          foreach ($files as $file) {
            $this->writeFileIntoPdf($file, $mPdf, $allowedImagesTypes);
          }
        }
        else {
          $mPdf->AddPage();
          $this->addLogo($mPdf);
          $mPdf->WriteHTML($field->value);
          $mPdf->WriteHTML('<h6></h6>');
        }
      }
    }

    // export child referenced fields
    $this->writeChildFields($node, $mPdf);
    $trans = \Drupal::service('transliteration');
    $cleanFileName = $trans->transliterate($node->getTitle(), 'en', '?', 20);
    $mPdf->Output($cleanFileName . '.pdf', 'D');
  }

  private function addLogo(&$mPdf) {
    if ($this->configData[ConfigForm::USE_LOGO] && !empty($this->pdfLogoPath)) {
      $mPdf->WriteHTML('<div class="logo-wrapper"><img src="' . $this->pdfLogoPath . '" style="float: right; clear: both; max-height: 100px"/></div>');
    }
  }

  private function writeFileIntoPdf(File $file, Mpdf &$mPdf, $allowedImagesTypes = []) {
    $fileType = $file->getMimeType();
    if (!empty($allowedImagesTypes)) {
      array_walk($allowedImagesTypes, function (&$item, $key) {
        $item = 'image/' . $item;
      });
    }
    if ($fileType == 'application/pdf') {
      $path = \Drupal::service('file_system')->realpath($file->getFileUri());
      $tempDir = file_directory_temp();
      $output_file = $tempDir . '/' . $file->getFilename();
      exec("'gs' '-sDEVICE=pdfwrite' '-dCompatibilityLevel=1.4' -dNOPAUSE -dBATCH '-sOutputFile=$output_file' '$path'", $output);
      if (!empty($output)) {
        $pagesCount = $mPdf->SetSourceFile($output_file);
        $mPdf->AddPage();
        for ($i = 1; $i <= $pagesCount; $i++) {
          $id = $mPdf->ImportPage($i);
          $mPdf->UseTemplate($id, NULL, NULL, 210);
          if ($i < $pagesCount) {
            $mPdf->AddPage();
          }
        }
      }
    }
    elseif (in_array($fileType, $allowedImagesTypes)) {
      $mPdf->AddPage();
      $mPdf->WriteHTML('<img src="' . $file->url() . '" />');
    }
    elseif ($fileType == 'text/plain') {
      $text = file_get_contents($file->getFileUri());
      $mPdf->AddPage();
      $mPdf->WriteHTML($text);
    }
    $mPdf->WriteHTML('<h6> </h6>');
  }

  private function writeChildFields($node, Mpdf &$mPdf) {
    $fieldDefinition = $node->getFieldDefinitions()[$this->configData[ConfigForm::CHILD_TYPE]];
    if (!empty($fieldDefinition)) {
      $referencedField = $node->{$fieldDefinition->getName()};
      $entities = $referencedField->referencedEntities();
      foreach ($entities as $entity) {
        $selectedFieldsToPrint = array_keys($this->configData[ConfigForm::SELECTED_CHILD_FIELDS_FOR_EXPORT][$entity->bundle()]);
        if (!empty($selectedFieldsToPrint)) {
          foreach ($selectedFieldsToPrint as $fieldNameToPrint) {
            $fieldToPrint = $entity->{$fieldNameToPrint};
            $fieldType = $fieldToPrint->getFieldDefinition()->getType();
            if ($fieldType == 'image' || $fieldType == 'file') {
              $files = $fieldToPrint->referencedEntities();
              $allowedImagesTypes = explode(' ', $fieldToPrint->getSetting('file_extensions'));
              foreach ($files as $file) {
                $this->writeFileIntoPdf($file, $mPdf, $allowedImagesTypes);
              }
            }
            elseif (!empty($fieldToPrint->getValue())) {
              foreach ($fieldToPrint->getValue() as $value) {
                $mPdf->WriteHTML($fieldToPrint->getDataDefinition()
                    ->label() . ': ' . $value['value']);
              }
            }
          }
        }
      }
    }
  }
}
