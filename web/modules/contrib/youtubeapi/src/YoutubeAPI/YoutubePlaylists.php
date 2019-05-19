<?php

namespace Drupal\youtubeapi\YoutubeAPI;

/**
 * YoutubePlaylists.
 * Youtube API Class.
 * Generated on : 2017-05-15 12:15:18
 */
class YoutubePlaylists extends API {

  // API URL.
  const request_uri = "https://www.googleapis.com/youtube/v3/playlists";
  // API URL Part.
  const method = "playlists";

  // Request Parameters.
  const part = 'part';
  const channelId = 'channelId';
  const id = 'id';
  const mine = 'mine';
  const hl = 'hl';
  const maxResults = 'maxResults';
  const onBehalfOfContentOwner = 'onBehalfOfContentOwner';
  const onBehalfOfContentOwnerChannel = 'onBehalfOfContentOwnerChannel';
  const pageToken = 'pageToken';

  // Response Parameters.
  const kind = 'kind';
  const etag = 'etag';
  const nextPageToken = 'nextPageToken';
  const prevPageToken = 'prevPageToken';
  const pageInfo = 'pageInfo';
  const pageInfo_totalResults = 'pageInfo.totalResults';
  const pageInfo_resultsPerPage = 'pageInfo.resultsPerPage';
  const items = 'items';
}
