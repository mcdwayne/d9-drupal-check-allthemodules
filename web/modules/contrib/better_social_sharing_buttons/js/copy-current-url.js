/**
 * Copy the current page url to clipboard when clicking on the .btnCopy button.
 * Based on https://stackoverflow.com/questions/400212/how-do-i-copy-to-the-clipboard-in-javascript/30810322#30810322
 */

// Selector of the element that copies link when clicked.
var copyButtonElements = document.querySelectorAll('.btnCopy');

// Add click event listener to the button(s).
copyButtonElements.forEach(function (element) {
  'use strict';
  // Adding click event on each anchor element.
  element.addEventListener('click', function (e) {
    var popupElements = document.querySelectorAll('.social-sharing-buttons__popup');
    copyTextToClipboard(window.location.href, popupElements);
  });
});

/*
 * Function to copy current url to clipboard. Shows a popupmessage on screen if url was copied successful.
 */
function copyTextToClipboard(text, popupElements) {
  'use strict';
  if (!navigator.clipboard) {
    fallbackCopyTextToClipboard(text, popupElements);
    return;
  }

  navigator.clipboard.writeText(text, popupElements).then(function () {
    showCopiedMessage(popupElements);
  }, function (err) {
    console.error('Error copying current url to clipboard: ', err);
  });
}

/*
 * Fallback copy functionality using using older document.execCommand('copy') for when the normal clipboard
 * functionality (navigator.clipboard) does not work. This generates a textarea with url as content and the copies that
 * content using the document.execCommand('copy') command.
 */
function fallbackCopyTextToClipboard(text, popupElements) {
  'use strict';
  var textArea = document.createElement('textarea');
  textArea.value = text;
  document.body.appendChild(textArea);
  textArea.focus();
  textArea.select();

  try {
    document.execCommand('copy');
    showCopiedMessage(popupElements);
  }
  catch (err) {
    console.error('Error copying current url to clipboard', err);
  }

  document.body.removeChild(textArea);
}

/*
 * Show a popup if the current url was successfully copied.
 */
function showCopiedMessage(popupElements) {
  'use strict';
  var visibleClass = 'visible';

  popupElements.forEach(function (element) {
    element.classList.add(visibleClass);
  });

  setTimeout(function () {
    popupElements.forEach(function (element) {
      element.classList.remove(visibleClass);
    });
  }, 4000);
}
