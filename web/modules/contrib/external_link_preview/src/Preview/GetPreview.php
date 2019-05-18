<?php

/**
 * @file
 * Contains link preview functionality.
 */

namespace Drupal\extlink_preview\Preview;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;

/**
 * Class GetPreview.
 */
class GetPreview {

  /**
 * Extracting the metatags of the url.
 */
public function extlink_preview_get_preview($preview_item, $settings) {
  if (isset($preview_item)) {
    if (preg_match('/www./', $preview_item)) {
      $newstr = explode(' ', strstr($preview_item, 'www.'))[0];
      $preview_item = substr_replace($newstr, 'http://', 0, 0);
    }
    elseif (!(preg_match('/http:/', $preview_item)) && preg_match('/bit.ly/', $preview_item)) {
      $newstr = explode(' ', strstr($preview_item, 'bit.ly'))[0];
      $preview_item = substr_replace($newstr, 'http://', 0, 0);
    }
    if (preg_match("/(http|https)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/", $preview_item, $match)) {
      $link = $match[0];
    }
    if (isset($link)) {
      $arr = preg_split( "/(\"|')/", $link);
      $link = $arr[0];
      $html = $this->extlink_preview_get_contents_curl($link);
      if (isset($html) && !empty($html)) {
        $result = $this->extlink_preview_get_all_metatags($html);
        $data = $this->extlink_preview_get_og_metatags($html, $link);
      }
      if ($settings['show_title'] == 'Y') {
        if (!empty($data['og:title'])) {
          $output['title'] = $data['og:title'];
          $output['url'] = $link;
        }
        elseif (isset($result['title'])) {
          $output['title'] = $result['title'];
          $output['url'] = $link;
        }
      }
      if ($settings['trim_link_description'] != '0') {
        $description_length = $settings['trim_link_description'];
        if (!empty($data['og:description'])) {
          $description = strip_tags($data['og:description']);
          if (strlen($description) > $description_length) {
            $stringcut = substr($description, 0, $description_length);
            $description = substr($stringcut, 0, strrpos($stringcut, ' ')) . '...';
          }
          $output['description'] = $description;
        }
        else {
          if (isset($result['metaTags']['description']['value'])) {
            $description = strip_tags($result['metaTags']['description']['value']);
            if (strlen($description) > $description_length) {
              $stringcut = substr($description, 0, $description_length);
              $description = substr($stringcut, 0, strrpos($stringcut, ' ')) . '...';
            }
            $output['description'] = $description;
          }
        }
      }
      if ($settings['show_image'] == 'Y') {
        if (!empty($data['og:image'])) {
          if (preg_match('/www./', $data['og:image'])) {
            $newstr = explode(' ', strstr($data['og:image'], 'www.'))[0];
            $img_src = substr_replace($newstr, 'http://', 0, 0);
          }
          else {
            $img_src = $data['og:image'];
          }
          $raw = $this->extlink_preview_ranger($img_src);
          if (preg_match("/.png/i", $img_src)) {
            $im = @imagecreatefrompng($img_src);
          }
          else {
            if (!empty($raw)) {
              $im = @imagecreatefromstring($raw);
            }
          }
          if (!empty($im)) {
            $width = imagesx($im);
            $height = imagesy($im);
          }
          else {
            if (!empty($img_src)) {
              $image_size = getimagesize($img_src);
              if (!empty($image_size)) {
                $width = $image_size['0'];
                $height = $image_size['1'];
              }
            }
          }
          if (isset($width) && isset($height)) {
            $image_aspect_ratio = $width / $height;
            if (isset($image_aspect_ratio)) {
              if ($width >= 380  && $image_aspect_ratio >= 1.2) {
                $img_class = "og-long-image";
              }
              elseif ($width > 140) {
                $img_class = "og-short-image";
              }
            }
          }
        }
        elseif (isset($result['metaTags']['twitter:image']['value'])) {
          $img_src = $result['metaTags']['twitter:image']['value'];
          if (preg_match('/www./', $result['metaTags']['twitter:image']['value'])) {
            $newstr = explode(' ', strstr($result['metaTags']['twitter:image']['value'], 'www.'))[0];
            $img_src = substr_replace($newstr, 'http://', 0, 0);
          }
          else {
            $img_src = $result['metaTags']['twitter:image']['value'];
          }
          $raw = $this->extlink_preview_ranger($img_src);
          if (preg_match("/.png/i", $img_src)) {
            $im = @imagecreatefrompng($img_src);
          }
          else {
            if (!empty($raw)) {
              $im = @imagecreatefromstring($raw);
            }
          }
          if (!empty($im)) {
            $width = imagesx($im);
            $height = imagesy($im);
          }
          else {
            if (!empty($img_src)) {
              $image_size = getimagesize($img_src);
              if (!empty($image_size)) {
                $width = $image_size['0'];
                $height = $image_size['1'];
              }
            }
          }
          if (isset($width) && isset($height)) {
            $image_aspect_ratio = $width / $height;
            if (isset($image_aspect_ratio)) {
              if ($width >= 380  && $image_aspect_ratio >= 1.2) {
                $img_class = "og-long-image";
              }
              elseif ($width > 140) {
                $img_class = "og-short-image";
              }
            }
          }
        }
        else {
          $images = $this->extlink_preview_get_images($html);
          if (isset($images)) {
            $images_data = array();
            foreach ($images as $key => $value) {
              if ((substr($images[$key], 0, 7) == 'http://') || (substr($images[$key], 0, 8) == 'https://')) {
                $raw = $this->extlink_preview_ranger($images[$key]);
                if (!empty($raw)) {
                  $im = @imagecreatefromstring($raw);
                  if (!empty($im)) {
                    $width = imagesx($im);
                    $height = imagesy($im);
                    if (isset($width) && isset($height)) {
                      $images_data[$key]['width'] = $width;
                      $images_data[$key]['height'] = $height;
                      $images_data[$key]['url'] = $images[$key];
                    }
                  }
                }
              }
            }
            if (!empty($images_data)) {
              foreach ($images_data as $key => $value) {
                $twidth[$key] = $images_data[$key]['width'];
              }
              $max_width = max($twidth);
              foreach ($images_data as $key => $value) {
                if ($images_data[$key]['width'] == $max_width && $images_data[$key]['height'] >= 140) {
                  $image_width = $max_width;
                  $image_height = $images_data[$key]['height'];
                  if (isset($image_width) && isset($image_height)) {
                    $image_aspect_ratio = $image_width / $image_height;
                  }
                  $img_src = $images_data[$key]['url'];;
                  break;
                }
              }
              if (isset($img_src) && isset($image_width)) {
                if ($max_width >= 380 && $image_aspect_ratio >= 1.2) {
                  $img_class = "og-long-image";
                }
                else {
                  $img_class = "og-short-image";
                }
              }
            }
          }
        }
        if (isset($img_class) && isset($img_src)) {
          $image = array(
            '#theme' => 'image',
            '#uri' => $img_src,
            '#class' => $img_class,
          );

          $image = drupal_render($image);
          $output['image'] = $image;
        }
      }
      if (!empty($output)) {
        return $output;
      }
    }
  }
}

/**
 * Extracts the Open Graph Tags.
 */
public function extlink_preview_get_og_metatags($html, $link) {
  libxml_use_internal_errors(TRUE);
  $doc = Html::load($html);
  $doc = new \DomDocument();
  $doc->loadHTML($html);
  $xpath = new \DOMXPath($doc);
  $query = '//*/meta[starts-with(@property, \'og:\')]';
  $metas = $xpath->query($query);
  $rmetas = array();
  foreach ($metas as $meta) {
    $property = $meta->getAttribute('property');
    $content = $meta->getAttribute('content');
    $rmetas[$property] = $content;
  }
  if (empty($rmetas)) {
    $result_test = @file_get_contents($link);
    if (!empty($result_test)) {
      libxml_use_internal_errors(TRUE);
      $doc = new \DomDocument();
      $doc->loadHTML($result_test);
      $xpath = new \DOMXPath($doc);
      $query = '//*/meta[starts-with(@property, \'og:\')]';
      $metas = $xpath->query($query);
      $rmetas = array();
      foreach ($metas as $meta) {
        $property = $meta->getAttribute('property');
        $content = $meta->getAttribute('content');
        $rmetas[$property] = $content;
      }
    }
  }
  return $rmetas;
}

/**
 * Extracts title and all the other metatags.
 */
public function extlink_preview_get_all_metatags($html) {
  $result = FALSE;
  $contents = $html;
  if (isset($contents) && is_string($contents)) {
    $title = NULL;
    $metatags = NULL;
    preg_match('/<title>([^>]*)<\/title>/si', $contents, $match);
    if (isset($match) && is_array($match) && count($match) > 0) {
      $title = strip_tags($match[1]);
    }
    preg_match_all('/<[\s]*meta[\s]*name="?' . '([^>"]*)"?[\s]*' . 'content="?([^>"]*)"?[\s]*[\/]?[\s]*>/si', $contents, $match);
    if (isset($match) && is_array($match) && count($match) == 3) {
      $originals = $match[0];
      $names = $match[1];
      $values = $match[2];
      if (count($originals) == count($names) && count($names) == count($values)) {
        $metatags = array();
        for ($i = 0, $limiti = count($names); $i < $limiti; $i++) {
          $metatags[$names[$i]] = array(
            'html' => htmlentities($originals[$i]),
            'value' => $values[$i],
          );
        }
      }
    }
    $result = array(
      'title' => $title,
      'metaTags' => $metatags,
    );
  }
  return $result;
}

/**
 * Returns the html of the url.
 */
public function extlink_preview_get_contents_curl($url) {
  $ch = curl_init();
  $agent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)';
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_USERAGENT, $agent);
  $data = curl_exec($ch);
  curl_close($ch);
  return $data;
}

/**
 * Extracts all the images from the html.
 */
public function extlink_preview_get_images($html) {
  $image_regex_src_url = '/<img[^>]*src=[\"|\'](.*)[\"|\']/Ui';
  if (preg_match_all($image_regex_src_url, $html, $out, PREG_PATTERN_ORDER)) {
    $images_url_array = $out[1];
    return $images_url_array;
  }
}

/**
 * Returns the data of the image.
 */
public function extlink_preview_ranger($url) {
  $headers = array(
    "Range: bytes=0-32768",
  );
  $curl = curl_init($url);
  curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
  $data = curl_exec($curl);
  curl_close($curl);
  return $data;
}

}
