<?php

namespace Drupal\search_file_attachments;

/**
 * Service that handles the mimetypes of file extensions.
 */
class MimetypeService {

  protected $mimetypes = array();

  /**
   * Contstructor that defines the mimetypes and her file extensions.
   */
  public function __construct() {
    $this->mimetypes = [
      '7z' => 'application/x-7z-compressed',
      'aac' => 'audio/x-aac',
      'ai' => 'application/postscript',
      'aif' => 'audio/x-aiff',
      'asc' => 'text/plain',
      'asf' => 'video/x-ms-asf',
      'atom' => 'application/atom+xml',
      'avi' => 'video/x-msvideo',
      'bmp' => 'image/bmp',
      'bz2' => 'application/x-bzip2',
      'cer' => 'application/pkix-cert',
      'crl' => 'application/pkix-crl',
      'crt' => 'application/x-x509-ca-cert',
      'css' => 'text/css',
      'csv' => 'text/csv',
      'cu' => 'application/cu-seeme',
      'deb' => 'application/x-debian-package',
      'doc' => 'application/msword',
      'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      'dvi' => 'application/x-dvi',
      'eot' => 'application/vnd.ms-fontobject',
      'eps' => 'application/postscript',
      'epub' => 'application/epub+zip',
      'etx' => 'text/x-setext',
      'flac' => 'audio/flac',
      'flv' => 'video/x-flv',
      'gif' => 'image/gif',
      'gz' => 'application/gzip',
      'htm' => 'text/html',
      'html' => 'text/html',
      'ico' => 'image/x-icon',
      'ics' => 'text/calendar',
      'ini' => 'text/plain',
      'iso' => 'application/x-iso9660-image',
      'jar' => 'application/java-archive',
      'jpe' => 'image/jpeg',
      'jpeg' => 'image/jpeg',
      'jpg' => 'image/jpeg',
      'js' => 'text/javascript',
      'json' => 'application/json',
      'latex' => 'application/x-latex',
      'log' => 'text/plain',
      'm4a' => 'audio/mp4',
      'm4v' => 'video/mp4',
      'mid' => 'audio/midi',
      'midi' => 'audio/midi',
      'mov' => 'video/quicktime',
      'mp3' => 'audio/mpeg',
      'mp4' => 'video/mp4',
      'mp4a' => 'audio/mp4',
      'mp4v' => 'video/mp4',
      'mpe' => 'video/mpeg',
      'mpeg' => 'video/mpeg',
      'mpg' => 'video/mpeg',
      'mpg4' => 'video/mp4',
      'oga' => 'audio/ogg',
      'ogg' => 'audio/ogg',
      'ogv' => 'video/ogg',
      'ogx' => 'application/ogg',
      'pbm' => 'image/x-portable-bitmap',
      'pdf' => 'application/pdf',
      'pgm' => 'image/x-portable-graymap',
      'png' => 'image/png',
      'pnm' => 'image/x-portable-anymap',
      'ppm' => 'image/x-portable-pixmap',
      'ppt' => 'application/vnd.ms-powerpoint',
      'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
      'ps' => 'application/postscript',
      'qt' => 'video/quicktime',
      'rar' => 'application/x-rar-compressed',
      'ras' => 'image/x-cmu-raster',
      'rss' => 'application/rss+xml',
      'rtf' => 'application/rtf',
      'sgm' => 'text/sgml',
      'sgml' => 'text/sgml',
      'svg' => 'image/svg+xml',
      'swf' => 'application/x-shockwave-flash',
      'tar' => 'application/x-tar',
      'tif' => 'image/tiff',
      'tiff' => 'image/tiff',
      'torrent' => 'application/x-bittorrent',
      'ttf' => 'application/x-font-ttf',
      'txt' => 'text/plain',
      'wav' => 'audio/x-wav',
      'webm' => 'video/webm',
      'wma' => 'audio/x-ms-wma',
      'wmv' => 'video/x-ms-wmv',
      'woff' => 'application/x-font-woff',
      'wsdl' => 'application/wsdl+xml',
      'xbm' => 'image/x-xbitmap',
      'xls' => 'application/vnd.ms-excel',
      'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      'xml' => 'application/xml',
      'xpm' => 'image/x-xpixmap',
      'xwd' => 'image/x-xwindowdump',
      'yaml' => 'text/yaml',
      'yml' => 'text/yaml',
      'zip' => 'application/zip',
    ];
  }

  /**
   * Returns a list of valid file mimetypes.
   *
   * @return array
   *   The array of mimetypes, with the file extension as the array key.
   */
  public function getMimetypes() {
    return $this->mimetypes;
  }

  /**
   * Convert file extensions to the corresponding mimetype.
   *
   * @param string $filetypes
   *   A comma-separated string of file extensions.
   *
   * @return array
   *   The array of corresponsing mimetypes.
   */
  public function extensionsToMimetypes($filetypes) {
    $filetypes = preg_replace('/[^a-zA-z0-9\/\-,]/', '', $filetypes);
    $filetypes = explode(',', $filetypes);

    $mimetypes = array();
    if (!empty($filetypes)) {
      foreach ($filetypes as $type) {
        if ($mt = $this->extensionToMimetype($type)) {
          $mimetypes[] = $mt;
        }
      }
    }

    return $mimetypes;
  }

  /**
   * Convert a file extension to the corresponding mimetype.
   *
   * @param string $extension
   *   A file extension.
   *
   * @return null|string
   *   The corresponding mimetype or null if no mimetype for the extension
   *   was found.
   */
  public function extensionToMimetype($extension) {
    if ($mimetype = $this->fromExtension($extension)) {
      return $mimetype;
    }
    elseif (in_array($extension, $this->getMimetypes())) {
      return $mimetype;
    }
  }

  /**
   * Maps a file extension to a mimetype.
   *
   * @param string $extension
   *   The file extension.
   *
   * @return string|null
   *   The mimetype.
   *
   * @see mimetype_from_extension()
   */
  public function fromExtension($extension) {
    $extension = strtolower($extension);

    return isset($this->mimetypes[$extension]) ? $this->mimetypes[$extension] : NULL;
  }

}
