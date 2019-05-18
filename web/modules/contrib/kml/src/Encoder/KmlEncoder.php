<?php

namespace Drupal\kml\Encoder;

use Drupal\serialization\Encoder\XmlEncoder as SerializationXmlEncoder;

/**
 * Encodes KML data in XML.
 */
class KmlEncoder extends SerializationXmlEncoder {

  /**
   * The formats that this Encoder supports.
   *
   * @var string
   */
  protected static $format = ['kml', 'kmz'];

  /**
   * {@inheritdoc}
   */
  public function encode($data, $format, array $context = []) {

    $files2zip = [];
    $kmldata['Document'] = ['Placemark' => $data];

    foreach ($kmldata['Document']['Placemark'] as $i => &$placemark) {
      $folder = NULL;
      foreach ($placemark as $tag => $value) {
        switch ($tag) {
          case 'name':
          case 'description':
          case 'styleUrl':
            // Everything is ok as it is; no break here as we don't want to unset the perfectly correct original key.
            continue 2;

          case 'Folder':
            $folder = $value;
            break;

          // Leave 'coordinates' for backward compatibility.
          case 'coordinates':
          case 'Point_coordinates':
            $placemark['Point'] = ['coordinates' => $value];
            break;

          case 'LineString_coordinates':
            $placemark['LineString'] = ['coordinates' => $value];
            break;

          case 'icon':
            // If making a kmz, zip local icons into the archive and make sure the urls we write into kml are relative with no leading slash.
            if ($format == 'kmz' && !empty($value) && $this->isLocal($value)) {
              $placemark['Style']['IconStyle']['Icon'] = ['href' => urldecode(ltrim($this->relativizeUrl($value), '/'))];
              $files2zip[DRUPAL_ROOT . '/' . $placemark['Style']['IconStyle']['Icon']['href']] = $placemark['Style']['IconStyle']['Icon']['href'];
            }
            else {
              // Else make sure all icon urls are absolute.
              $placemark['Style']['IconStyle']['Icon'] = ['href' => $this->absolutizeUrl($value)];
            }
            break;

          case 'atom_author':
            $placemark['atom:author']['atom:name'] = $value;
            break;

          case 'atom_link':
            $placemark['atom:link'] = ['@href' => $value];
            break;

          case 'gx_media_links':
            $placemark['ExtendedData']['Data'] = ['@name' => 'gx_media_links', 'value' => $value];
            break;

          // Preformatted KML, as from GeoField in "escaped" mode.
          default:
            $value = html_entity_decode($value);
            if (substr($value, 0, 1) !== '<' || substr($value, -1) !== '>') {
              // Definitely not KML, just leave it alone.
              continue 2;
            }
            $kmlFragment = simplexml_load_string($value);
            // Merge recursively instead of simple assign to not accidentally overwrite the work of another field.
            // Recursively cast the SimpleXMLElementobject to array by means of json_encode/decode.
            // NB Can NOT just plant the object into the data array (even though Symfony xml encoder supports that)
            // for a variety of reasons too long to document here.
            $placemark = array_merge_recursive($placemark, [$kmlFragment->getName() => json_decode(json_encode($kmlFragment->children()), TRUE)]);
        }
        // Clean up the original item as we just created a new correct one.
        unset($placemark[$tag]);
      }
      if ($folder !== NULL) {
        if (!isset($kmldata['Document']['Folder'])) {
          $kmldata['Document']['Folder'] = [];
        }
        $folderIndex = array_search($folder, array_column($kmldata['Document']['Folder'], 'name'));
        if ($folderIndex === FALSE) {
          $folderIndex = count($kmldata['Document']['Folder']);
          $kmldata['Document']['Folder'][$folderIndex]['name'] = $folder;
        }
        $kmldata['Document']['Folder'][$folderIndex]['Placemark'][] = $placemark;
        unset($kmldata['Document']['Placemark'][$i]);
      }
    }

    if (!count($kmldata['Document']['Placemark'])) {
      unset($kmldata['Document']['Placemark']);
    }

    $kml_header = empty($context['views_style_plugin']->options['kml_settings']['kml_header']) ?
                   '' : \Drupal::token()->replace($context['views_style_plugin']->options['kml_settings']['kml_header']);

    $context['xml_format_output'] = 'formatOutput';
    $context['xml_encoding'] = 'UTF-8';
    $context['xml_root_node_name'] = 'kml';
    $kmldata['@xmlns'] = 'http://www.opengis.net/kml/2.2';

    $kml = parent::getBaseEncoder()->encode($kmldata, $format, $context);
    if (!empty($kml_header)) {
      $kml = str_replace("<Document>", "<Document>\n" . $kml_header, $kml);
    }

    if ($format == 'kmz') {
      $file_system = \Drupal::service('file_system');
      $temp_zip = file_unmanaged_save_data('', file_directory_temp() . '/kml_export.zip', FILE_EXISTS_RENAME);
      $zip = archiver_get_archiver($file_system->realpath($temp_zip))->getArchive();
      $temp_kml = $file_system->realpath(file_unmanaged_save_data($kml, file_directory_temp() . '/doc.kml', FILE_EXISTS_RENAME));
      $zip->addFile($temp_kml, 'doc.kml');
      foreach ($files2zip as $filename => $localname) {
        $zip->addFile($filename, $localname);
      }
      $zip->close();
      $kml = file_get_contents($temp_zip);

    }

    return $kml;
  }

  /**
   * Makes sure the url is absolute (appends current host's scheme/host/port if missing).
   */
  private function absolutizeUrl($url) {
    $components = parse_url($url);
    if (empty($components['scheme'])) {
      $components['scheme'] = \Drupal::request()->getScheme();
    }
    if (empty($components['host'])) {
      $components['host'] = \Drupal::request()->getHost();
    }
    if (empty($components['port']) && \Drupal::request()->getPort() != '80') {
      $components['port'] = \Drupal::request()->getPort();
    }

    return $this->unparse_url($components);
  }

  /**
   * Makes sure the url is relative (strips scheme/host/port if present).
   */
  private function relativizeUrl($url) {
    $components           = parse_url($url);
    $components['scheme'] = NULL;
    $components['host']   = NULL;
    $components['port']   = NULL;

    return ltrim($this->unparse_url($components), '/');
  }

  /**
   * Reassembles the url/path from an array of components.
   */
  private function unparse_url($parsed_url) {
    $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
    $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
    $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
    $path     = isset($parsed_url['path']) ? '/' . ltrim($parsed_url['path'], '/') : '';
    $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
    $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
    return "$scheme$host$port$path$query$fragment";
  }

  /**
   * Checks whether the url/path is local to this host.
   */
  private function isLocal($url) {
    $host = parse_url($url, PHP_URL_HOST);
    if (empty($host) || strcasecmp($host, \Drupal::request()->getHost()) === 0) {
      return TRUE;
    }
  }

}
