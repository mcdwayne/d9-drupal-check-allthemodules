<?php

namespace Drupal\youtubeapi\YoutubeAPI;

/**
 * YoutubeSubscriptions.
 * Youtube API Class.
 * Generated on : 2017-05-15 12:15:19
 */
class YoutubeSubscriptions extends API {

  // API URL.
  const request_uri = "https://www.googleapis.com/youtube/v3/subscriptions";
  // API URL Part.
  const method = "subscriptions";

  // Request Parameters.
  const part = 'part';
  const channelId = 'channelId';
  const id = 'id';
  const mine = 'mine';
  const myRecentSubscribers = 'myRecentSubscribers';
  const mySubscribers = 'mySubscribers';
  const forChannelId = 'forChannelId';
  const maxResults = 'maxResults';
  const onBehalfOfContentOwner = 'onBehalfOfContentOwner';
  const onBehalfOfContentOwnerChannel = 'onBehalfOfContentOwnerChannel';
  const order = 'order';
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
