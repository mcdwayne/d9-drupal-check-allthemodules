<?php

namespace Drupal\youtubeapi\YoutubeAPI;

/**
 * YoutubeCaptions.
 * Youtube API Class.
 * Generated on : 2017-05-15 12:15:14
 */
class YoutubeCaptions extends API {

  // API URL.
  const request_uri = "https://www.googleapis.com/youtube/v3/captions";
  // API URL Part.
  const method = "captions";

  // Request Parameters.
  const part = 'part';
  const videoId = 'videoId';
  const id = 'id';
  const onBehalfOfContentOwner = 'onBehalfOfContentOwner';

  // Response Parameters.
  const kind = 'kind';
  const etag = 'etag';
  const items = 'items';
}
