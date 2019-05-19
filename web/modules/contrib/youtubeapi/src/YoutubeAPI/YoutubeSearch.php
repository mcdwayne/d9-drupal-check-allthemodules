<?php

namespace Drupal\youtubeapi\YoutubeAPI;

/**
 * YoutubeSearch.
 * Youtube API Class.
 * Generated on : 2017-05-15 12:15:19
 */
class YoutubeSearch extends API {

  // API URL.
  const request_uri = "https://www.googleapis.com/youtube/v3/search";
  // API URL Part.
  const method = "search";

  // Request Parameters.
  const part = 'part';
  const forContentOwner = 'forContentOwner';
  const forDeveloper = 'forDeveloper';
  const forMine = 'forMine';
  const relatedToVideoId = 'relatedToVideoId';
  const channelId = 'channelId';
  const channelType = 'channelType';
  const eventType = 'eventType';
  const location = 'location';
  const locationRadius = 'locationRadius';
  const maxResults = 'maxResults';
  const onBehalfOfContentOwner = 'onBehalfOfContentOwner';
  const order = 'order';
  const pageToken = 'pageToken';
  const publishedAfter = 'publishedAfter';
  const publishedBefore = 'publishedBefore';
  const q = 'q';
  const regionCode = 'regionCode';
  const relevanceLanguage = 'relevanceLanguage';
  const safeSearch = 'safeSearch';
  const topicId = 'topicId';
  const type = 'type';
  const videoCaption = 'videoCaption';
  const videoCategoryId = 'videoCategoryId';
  const videoDefinition = 'videoDefinition';
  const videoDimension = 'videoDimension';
  const videoDuration = 'videoDuration';
  const videoEmbeddable = 'videoEmbeddable';
  const videoLicense = 'videoLicense';
  const videoSyndicated = 'videoSyndicated';
  const videoType = 'videoType';

  // Response Parameters.
  const kind = 'kind';
  const etag = 'etag';
  const nextPageToken = 'nextPageToken';
  const prevPageToken = 'prevPageToken';
  //const regionCode = 'regionCode';//Also exist here
  const pageInfo = 'pageInfo';
  const pageInfo_totalResults = 'pageInfo.totalResults';
  const pageInfo_resultsPerPage = 'pageInfo.resultsPerPage';
  const items = 'items';
}
