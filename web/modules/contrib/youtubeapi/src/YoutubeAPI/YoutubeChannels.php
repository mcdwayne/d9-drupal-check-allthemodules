<?php

namespace Drupal\youtubeapi\YoutubeAPI;

/**
 * YoutubeChannels.
 * Youtube API Class.
 * Generated on : 2017-05-15 12:15:14
 */
class YoutubeChannels extends API {

  // API URL.
  const request_uri = "https://www.googleapis.com/youtube/v3/channels";
  // API URL Part.
  const method = "channels";

  // Request Parameters.
  const part = 'part';
  const categoryId = 'categoryId';
  const forUsername = 'forUsername';
  const id = 'id';
  const managedByMe = 'managedByMe';
  const mine = 'mine';
  const mySubscribers = 'mySubscribers';
  const hl = 'hl';
  const maxResults = 'maxResults';
  const onBehalfOfContentOwner = 'onBehalfOfContentOwner';
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
