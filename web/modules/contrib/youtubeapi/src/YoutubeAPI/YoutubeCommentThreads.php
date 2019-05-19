<?php

namespace Drupal\youtubeapi\YoutubeAPI;

/**
 * YoutubeCommentThreads.
 * Youtube API Class.
 * Generated on : 2017-05-15 12:15:16
 */
class YoutubeCommentThreads extends API {

  // API URL.
  const request_uri = "https://www.googleapis.com/youtube/v3/commentThreads";
  // API URL Part.
  const method = "commentThreads";

  // Request Parameters.
  const part = 'part';
  const allThreadsRelatedToChannelId = 'allThreadsRelatedToChannelId';
  const channelId = 'channelId';
  const id = 'id';
  const videoId = 'videoId';
  const maxResults = 'maxResults';
  const moderationStatus = 'moderationStatus';
  const order = 'order';
  const pageToken = 'pageToken';
  const searchTerms = 'searchTerms';
  const textFormat = 'textFormat';

  // Response Parameters.
  const kind = 'kind';
  const etag = 'etag';
  const nextPageToken = 'nextPageToken';
  const pageInfo = 'pageInfo';
  const pageInfo_totalResults = 'pageInfo.totalResults';
  const pageInfo_resultsPerPage = 'pageInfo.resultsPerPage';
  const items = 'items';
}
