(function ($) {
  'use strict';

  // Handling for the language prompt.
  var showPrompt = function () {
    var langPromptSettings = drupalSettings.langPrompt;

    // First check for preferred language. This works in Chrome and FF.
    if (typeof window.navigator.languages !== 'undefined') {
      var browserLanguageLocalized = window.navigator.languages[0];
    }
    else {
      // Otherwise fall back to OS language.
      var browserLanguageLocalized = window.navigator.userLanguage || window.navigator.language;
    }

    var pageLangcode = langPromptSettings.langcode;
    var selector = langPromptSettings.appendToSelector;
    var targetElList = document.querySelectorAll(selector);

    if (pageLangcode && typeof browserLanguageLocalized === 'string' && targetElList.length) {
      var targetEl = targetElList[0];
      // Try both the localized language and the langcode.
      var browserLanguage = browserLanguageLocalized.split('-')[0];
      var browserLangCodeCandidates = [browserLanguageLocalized, browserLanguage];

      // Determine whether the browser language matches the page languages.
      for (var i = 0; i < browserLangCodeCandidates.length; i++) {
        var browserLangCode = browserLangCodeCandidates[i];
        if (langPromptSettings[browserLangCode] === undefined) {
          continue
        }

        if (!$.cookie('langPromptDismissed') && browserLangCode !== pageLangcode) {

          // Find the correct language switcher link to use as the href/title
          // for the page we are jumping to.
          if (typeof langPromptSettings.languageLinks[browserLangCode] !== 'undefined') {
            var href = langPromptSettings.languageLinks[browserLangCode].url;
            var msgHtml = langPromptSettings[browserLangCode].messageHtml;

            // Replace the href and title placeholders.
            var msgHtmlReplaced = msgHtml.replace(/!href/g, href);
            msgHtmlReplaced = msgHtmlReplaced.replace(/!title/g, langPromptSettings.languageLinks[browserLangCode].title);
            targetEl.insertAdjacentHTML('afterbegin', msgHtmlReplaced);

            // Click event for "dismiss prompt" link.
            var dismissBtn = document.querySelectorAll('#dismiss-lang-prompt');
            if (dismissBtn.length) {
              dismissBtn[0].addEventListener('click', function (event) {
                var langPromptWrapper = document.querySelectorAll('#lang-prompt-wrapper')[0];
                langPromptWrapper.classList.add('visually-hidden');

                $.cookie('langPromptDismissed', 1, {
                  path: '/'
                });
                event.preventDefault();
              });
            }
          }

          break;
        }
      }
    }
  };

  showPrompt();

}(jQuery));
