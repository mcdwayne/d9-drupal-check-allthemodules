<?php

namespace Drupal\youtubeapi\YoutubeAPI;

/**
 * YoutubeChannelSections.
 * Youtube API Class.
 * Generated on : 2017-05-15 12:15:15
 */
class YoutubeChannelSections extends API {

  // API URL.
  const request_uri = "https://www.googleapis.com/youtube/v3/channelSections";
  // API URL Part.
  const method = "channelSections";

  // Request Parameters.
  const part = 'part';
  const channelId = 'channelId';
  const id = 'id';
  const mine = 'mine';
  const hl = 'hl';
  const onBehalfOfContentOwner = 'onBehalfOfContentOwner';

  // Response Parameters.
  const kind = 'kind';
  const etag = 'etag';
  const items = 'items';
}
