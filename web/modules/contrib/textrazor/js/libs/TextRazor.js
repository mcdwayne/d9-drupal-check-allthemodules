/**
 * @file
 * Defines TextRazor helper for operations in JavaScript.
 */

var TextRazor = new Object();

/**
 * Translates a term label from the TextRazor service response.
 * Uses Wikipedia and WikiData APIs.
 */
TextRazor.translateLabel = function (term = {}, languageCode = 'en', fallbackLabel = '') {
  let translation = TextRazor.translateLabel.getFromWikipedia(term, languageCode)
  if (translation === false) {
    translation = TextRazor.translateLabel.getFromWikidata(term, languageCode);
  }
  if (translation) {
    return translation;
  }
  else {
    return fallbackLabel;
  }
}

/**
 * Looks for label translation if Wikipedia reference is present.
 */
TextRazor.translateLabel.getFromWikipedia = function (term, languageCode) {
  if (term.wikiLink === undefined && term.wikiLink === '') {
    return false;
  }
  var urlElements = /([0-9A-Za-z]*)$/g
  var wikiKey = urlElements.exec(term.wikiLink)[0];
  var url = "https://en.wikipedia.org/w/api.php?action=query&prop=langlinks&lllang=" + languageCode + "&format=json&titles=" + wikiKey;
  jQuery.ajax({
    url: url,
    type: 'GET',
    crossDomain: true,
    dataType: 'jsonp'
  }).done(function (data) {
    if (data.query !== undefined) {
      var pageId = Object.keys(data.query.pages);
      if (data.query.pages[pageId] !== undefined) {
        let page = data.query.pages[pageId];
        if (page.langlinks !== undefined && page.langlinks[0] !== undefined) {
          return data.query.pages[pageId].langlinks[0]['*'];
        }
      }
    }
    return false;
  });
}

/**
 * Looks for label translation if Wikipedia reference is present.
 */
TextRazor.translateLabel.getFromWikidata = function (term, languageCode) {
  if (term.wikidataId === undefined) {
    return false;
  }
  var url = "https://www.wikidata.org/wiki/Special:EntityData/" + term.wikidataId + ".json";
  jQuery.get(url).done(function (data) {
    if (data.entities[term.wikidataId].labels[languageCode] !== undefined && data.entities[term.wikidataId].labels[languageCode].value !== undefined) {
      return data.entities[term.wikidataId].labels.de.value;
    }
    return false;
  });
}
