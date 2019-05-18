<?php

namespace Drupal\iptc;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Component\Utility\NestedArray;
use Drupal\file\Entity\File;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class IptcManager.
 */
class IptcManager {

  /**
   * Form API callback: Processes a file_generic field element.
   *
   * Expands the file_generic type to include the description and display
   * fields.
   *
   * This method is assigned as a #process callback in formElement() method.
   */
  public static function processIptcFile(&$element, FormStateInterface $form_state, $form) {
    $element['upload_button']['#ajax']['callback'] = ['Drupal\iptc\IptcManager', 'iptcUploadAjaxCallback'];
    return $element;
  }

  /**
   * Function iptcUploadAjaxCallback.
   */
  public static function iptcUploadAjaxCallback(&$form, FormStateInterface &$form_state, Request $request) {

    /** @var \Drupal\Core\Render\RendererInterface $renderer */

    $renderer = \Drupal::service('renderer');

    $form_parents = explode('/', $request->query->get('element_parents'));

    // Retrieve the element to be rendered.
    $form = NestedArray::getValue($form, $form_parents);

    $form['#suffix'] .= '<span class="ajax-new-content"></span>';

    $status_messages = ['#type' => 'status_messages'];
    $form['#prefix'] .= $renderer->renderRoot($status_messages);
    $output = $renderer->renderRoot($form);
    $response = new AjaxResponse();
    $response->setAttachments($form['#attached']);

    $field_value = $form_state->getValue('iptc_field_file');

    // When the file field is not limited:
    $fids = [];
    foreach ($field_value as $val) {
      if (isset($val['fids']) && !empty($val['fids'])) {
        $fids[] = $val['fids'][0];
      }
    }

    if (!empty($fids)) {

      $fid = $fids[0];
      $file = File::load($fid);
      $filename = $file->getFilename();
      $filename_noext = substr($file->getFilename(), 0, strrpos($file->getFilename(), '.'));

      $iptc_data = self::extractIptcData($fid);



      // Set "media name".
      if (isset($filename_noext)) {
        $response->addCommand(new InvokeCommand('#edit-name-0-value', 'val', [$filename_noext]));
      }

      // Set "Auteur".
      if (isset($iptc_data['auteur'])) {
        $response->addCommand(new InvokeCommand('#edit-iptc-field-auteur-122-0-value', 'val', [$iptc_data['auteur']]));
      }

      // Set "categories".
      if (isset($iptc_data['categories'])) {
        // $i = 0;.
        $categories = implode(', ', $iptc_data['categories']);
        if (isset($categories) && $categories != '') {
          $response->addCommand(new InvokeCommand('#edit-iptc-field-category-015-0-value', 'val', [$categories]));
        }
      }

      // Set "Code pays".
      if (isset($iptc_data['codepays'])) {
        $response->addCommand(new InvokeCommand('#edit-iptc-field-codepays-100-0-value', 'val', [$iptc_data['codepays']]));
      }

      // Set "Contact".
      if (isset($iptc_data['contact'])) {
        $response->addCommand(new InvokeCommand('#edit-iptc-field-contact-118-0-values', 'val', [$iptc_data['contact']]));
      }

      // Set "Copyright".
      if (isset($iptc_data['copyright'])) {
        $response->addCommand(new InvokeCommand('#edit-iptc-field-copyright-116-0-value', 'val', [$iptc_data['copyright']]));
      }

      // Set "Heure de création".
      if (isset($iptc_data['creationHour'])) {
        $response->addCommand(new InvokeCommand('#edit-iptc-field-creationhour-060-0-value', 'val', [$iptc_data['creationHour']]));
      }

      // Set "Date de création".
      if (isset($iptc_data['created'])) {
        $response->addCommand(new InvokeCommand('#edit-iptc-field-creationdate-055-0-value', 'val', [$iptc_data['created']]));
      }

      // Set "Creator".
      if (isset($iptc_data['creator'])) {
        $response->addCommand(new InvokeCommand('#edit-iptc-field-creator-080-0-value', 'val', [$iptc_data['creator']]));
      }

      // Set "Credit".
      if (isset($iptc_data['credit'])) {
        $response->addCommand(new InvokeCommand('#edit-iptc-field-credit-110-0-value', 'val', [$iptc_data['credit']]));
      }

      // Set "Cycle".
      if (isset($iptc_data['cycle'])) {
        $response->addCommand(new InvokeCommand('#edit-iptc-field-cycle-075-0-value', 'val', [$iptc_data['cycle']]));
      }

      // Set "Identifier".
      if (isset($iptc_data['identifier'])) {
        $response->addCommand(new InvokeCommand('#edit-iptc-field-identifier-022-0-value', 'val', [$iptc_data['identifier']]));
      }

      // Set "Instructions spéciales".
      if (isset($iptc_data['instructions'])) {
        $response->addCommand(new InvokeCommand('#edit-iptc-field-instruction-040-0-value', 'val', [$iptc_data['instructions']]));
      }

      // Set Keywords.
      if (isset($iptc_data['keywords'])) {
        // $i = 0;.
        $keywords = implode(', ', $iptc_data['keywords']);
        if (isset($keywords) && $keywords != '') {
          $response->addCommand(new InvokeCommand('#edit-iptc-field-keywords-025-0-value', 'val', [$keywords]));
        }
      }

      // Set "legende/note".
      if (isset($iptc_data['note'])) {
        $response->addCommand(new InvokeCommand('#edit-iptc-field-legende-120-0-value', 'val', [$iptc_data['note']]));
      }

      // Set "location".
      if (isset($iptc_data['location'])) {
        $response->addCommand(new InvokeCommand('#edit-iptc-field-location-026-0-value', 'val', [$iptc_data['location']]));
      }

      // Set "Nom de l'objet".
      if (isset($iptc_data['object_name'])) {
        $response->addCommand(new InvokeCommand('#edit-iptc-field-objectname-005-0-value', 'val', [$iptc_data['object_name']]));
      }
      else {
        $response->addCommand(new InvokeCommand('#edit-iptc-field-objectname-005-0-value', 'val', [$filename]));
      }

      // Set "pays".
      if (isset($iptc_data['pays'])) {
        $response->addCommand(new InvokeCommand('#edit-iptc-field-pays-101-0-value', 'val', [$iptc_data['pays']]));
      }

      // Set "priority".
      if (isset($iptc_data['priority'])) {
        $response->addCommand(new InvokeCommand('#edit-iptc-field-priority-010-0-value', 'val', [$iptc_data['priority']]));
      }

      // Set "program".
      if (isset($iptc_data['program'])) {
        $response->addCommand(new InvokeCommand('#edit-iptc-field-program-065-0-value', 'val', [$iptc_data['program']]));
      }

      // Set "province".
      if (isset($iptc_data['province'])) {
        $response->addCommand(new InvokeCommand('#edit-iptc-field-province-095-0-value', 'val', [$iptc_data['province']]));
      }

      // Set "reference".
      if (isset($iptc_data['reference'])) {
        $response->addCommand(new InvokeCommand('#edit-iptc-field-reference-103-0-value', 'val', [$iptc_data['reference']]));
      }

      // Set "region".
      if (isset($iptc_data['region'])) {
        $response->addCommand(new InvokeCommand('#edit-iptc-field-region-092-0-value', 'val', [$iptc_data['region']]));
      }

      // Set "source".
      if (isset($iptc_data['source'])) {
        $response->addCommand(new InvokeCommand('#edit-iptc-field-source-115-0-value', 'val', [$iptc_data['source']]));
      }

      // Set "status".
      if (isset($iptc_data['status'])) {
        $response->addCommand(new InvokeCommand('#edit-iptc-field-status-007-0-value', 'val', [$iptc_data['status']]));
      }

      // Set "Title creator".
      if (isset($iptc_data['titlecreator'])) {
        $response->addCommand(new InvokeCommand('#edit-iptc-field-titlecreator-085-0-value', 'val', [$iptc_data['titlecreator']]));
      }

      // Set "Titre".
      if (isset($iptc_data['titre'])) {
        $response->addCommand(new InvokeCommand('#edit-iptc-field-titre-105-0-value', 'val', [$iptc_data['titre']]));
      }

      // Set "Version".
      if (isset($iptc_data['version'])) {
        $response->addCommand(new InvokeCommand('#edit-iptc-field-version-070-0-value', 'val', [$iptc_data['version']]));
      }

      // Set "Ville".
      if (isset($iptc_data['ville'])) {
        $response->addCommand(new InvokeCommand('#edit-iptc-field-ville-090-0-value', 'val', [$iptc_data['ville']]));
      }

    }

    return $response->addCommand(new ReplaceCommand(NULL, $output));
  }

  /**
   * Function extractIptcData.
   */
  public static function extractIptcData($fid) {
    $data = [];

       $file = File::load($fid);
        if ('image/jpeg' == $file->getMimeType()) {
          //$x1 = str_replace("public://", "http://localhost/iptc-on-84/sites/default/files/", $file->getFileUri());
          //$info = [];
          //$size = getimagesize($x1, $info);
          $info = [];
          $size = getimagesize($file->getFileUri(), $info);
          if (!empty($size)) {
           if (isset($info["APP13"])) {
          if ($iptc = iptcparse($info["APP13"])) {
            // 2#122 : auteur.
            if (isset($iptc['2#122'][0])) {
              $data['auteur'] = self::checkUtf8($iptc['2#122'][0]);
            }

            // 2#120 : legende/note.
            if (isset($iptc['2#120'][0])) {
              $data['note'] = self::checkUtf8($iptc['2#120'][0]);
            }

            // 2#118 : contact.
            if (isset($iptc['2#118'][0])) {
              $data['contact'] = self::checkUtf8($iptc['2#118'][0]);
            }

            // 2#116 : copyright.
            if (isset($iptc['2#116'][0])) {
              $data['copyright'] = self::checkUtf8($iptc['2#116'][0]);
            }

            // 2#115 : source.
            if (isset($iptc['2#115'][0])) {
              $data['source'] = self::checkUtf8($iptc['2#115'][0]);
            }

            // 2#110 : credit.
            if (isset($iptc['2#110'][0])) {
              $data['credit'] = self::checkUtf8($iptc['2#110'][0]);
            }

            // 2#105 : titre.
            if (isset($iptc['2#105'][0])) {
              $data['titre'] = self::checkUtf8($iptc['2#105'][0]);
            }

            // 2#103 : reference.
            if (isset($iptc['2#103'][0])) {
              $data['reference'] = self::checkUtf8($iptc['2#103'][0]);
            }

            // 2#101 : pays.
            if (isset($iptc['2#101'][0])) {
              $data['pays'] = self::checkUtf8($iptc['2#101'][0]);
            }

            // 2#100 : codepays.
            if (isset($iptc['2#100'][0])) {
              $data['codepays'] = self::checkUtf8($iptc['2#100'][0]);
            }

            // 2#095 : province.
            if (isset($iptc['2#095'][0])) {
              $data['province'] = self::checkUtf8($iptc['2#095'][0]);
            }

            // 2#092 : region.
            if (isset($iptc['2#092'][0])) {
              $data['region'] = self::checkUtf8($iptc['2#092'][0]);
            }

            // 2#090 : ville.
            if (isset($iptc['2#090'][0])) {
              $data['ville'] = self::checkUtf8($iptc['2#090'][0]);
            }

            // 2#085 : titlecreator.
            if (isset($iptc['2#085'][0])) {
              $data['titlecreator'] = self::checkUtf8($iptc['2#085'][0]);
            }

            // 2#080 : creator.
            if (isset($iptc['2#080'][0])) {
              $data['creator'] = self::checkUtf8($iptc['2#080'][0]);
            }

            // 2#070 : version.
            if (isset($iptc['2#070'][0])) {
              $data['version'] = self::checkUtf8($iptc['2#070'][0]);
            }

            // 2#075 : cycle.
            if (isset($iptc['2#075'][0])) {
              $data['cycle'] = self::checkUtf8($iptc['2#075'][0]);
            }

            // 2#005 : nom de l'objet.
            if (isset($iptc['2#005'][0])) {
              $data['object_name'] = self::checkUtf8($iptc['2#005'][0]);
            }

            // 2#007 : status.
            if (isset($iptc['2#007'][0])) {
              $data['status'] = self::checkUtf8($iptc['2#007'][0]);
            }

            // 2#040 : instructions.
            if (isset($iptc['2#040'][0])) {
              $data['instructions'] = self::checkUtf8($iptc['2#040'][0]);
            }

            // 2#022 : identifier.
            if (isset($iptc['2#022'][0])) {
              $data['identifier'] = self::checkUtf8($iptc['2#022'][0]);
            }

            // 2#026 : location.
            if (isset($iptc['2#026'][0])) {
              $data['location'] = self::checkUtf8($iptc['2#026'][0]);
            }

            // 2#010 : priority.
            if (isset($iptc['2#010'][0])) {
              $data['priority'] = self::checkUtf8($iptc['2#010'][0]);
            }

            // 2#065 : program.
            if (isset($iptc['2#065'][0])) {
              $data['program'] = self::checkUtf8($iptc['2#065'][0]);
            }

            // 2#015 : catégories.
            if (isset($iptc['2#015'])) {
              $data['categories'] = [];
              foreach ($iptc['2#015'] as $categorie) {
                $data['categories'][] = mb_strtolower(self::checkUtf8($categorie), 'UTF-8');
              }
            }

            // 2#025 : mot clé.
            if (isset($iptc['2#025'])) {
              $data['keywords'] = [];
              foreach ($iptc['2#025'] as $keyword) {
                $data['keywords'][] = mb_strtolower(self::checkUtf8($keyword), 'UTF-8');
              }
            }

            // 2#060 : Heure de création.
            if (isset($iptc['2#060'][0])) {
              $hour = $iptc['2#060'][0];
              $data['creationHour'] = substr($hour, 0, 2) . ':' . substr($hour, 2, 2) . ':' . substr($hour, 4, 2);
            }

            // 2#055 : date de création.
            if (isset($iptc['2#055'][0])) {
              $date = $iptc['2#055'][0];
              $data['created'] = substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2);
            }
          }
        }
      }
    }

    return $data;
  }

  /**
   * Class checkUtf8.
   */
  public static function checkUtf8($string) {
    if ('UTF-8' != mb_detect_encoding($string, 'UTF-8', TRUE)) {
      $string = utf8_encode($string);
    }

    return $string;
  }

}
