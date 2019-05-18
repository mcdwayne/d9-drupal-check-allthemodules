It provides a screenshot_widget widget type for image type field.

The widget has two settings: "Screenshot Selector" and "Screenshot Controls".

"Screenshot Selector" uses for searching of an element which should be used as an area for a screenshot.
By default, document.body is used for the screenshot.

"Screenshot Controls" uses for define event which should be used for making of a screenshot.
By default, submitting event of the form is used, either "Make screenshot" button will be added to the widget. 
In this case, clicking on this button will be used for making of a screenshot. 

To install the html2canvas library:

1. Download it (version 1.0.0-alpha.12 or later) from
   https://html2canvas.hertzen.com/.
2. Unzip it into libraries folder, so that there's a
   libraries/html2canvas/html2canvas.min.js file, in addition to the other
   files included in the library.
