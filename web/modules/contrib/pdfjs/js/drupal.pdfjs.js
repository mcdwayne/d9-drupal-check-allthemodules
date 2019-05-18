(function ($, Drupal, drupalSettings, PDFJS) {
  "use strict";

  // Setup PDFJS env
  PDFJS.workerSrc = drupalSettings.path.basePath + drupalSettings.pdfjs.basePath + '/pdf.worker.js';

  Drupal.behaviors.pdfjs = {
    attach: function (context) {
      $('.pdfjs-canvas', context).each(function() {
        var $el = $(this);
        $el.append('<div class="pdf-content"/>');
        PDFJS.getDocument($el.data('uri')).then(renderPdf);

        function renderPdf(pdf) {
          for (var i = 1; i <= pdf.pdfInfo.numPages; i++) {
            pdf.getPage(i).then(renderPage);
          }
        }
        function renderPage(page) {
          var viewport = page.getViewport(1.5);
          var $canvas = jQuery("<canvas></canvas>");
          var $pdfContainer = $('.pdf-content', $el);

          // Set the canvas height and width to the height and width of the viewport
          var canvas = $canvas.get(0);
          var context = canvas.getContext("2d");
          canvas.width = viewport.width;
          canvas.height = viewport.height;

          /*
           $pdfContainer.css("height", canvas.style.height)
           .css("width", canvas.style.width);
           */
          $pdfContainer.append($canvas);

          //var canvasOffset = $canvas.offset();
          var $textLayerDiv = jQuery("<div />")
            .addClass("textLayer")
            .css("height", canvas.style.height)
            .css("width", canvas.style.width)
            .offset({
              top: canvas.offsetTop,
              left: canvas.offsetLeft
            });

          $pdfContainer.append($textLayerDiv);

          page.getTextContent().then(function (textContent) {
            var textLayer = new TextLayerBuilder({
              textLayerDiv: $textLayerDiv.get(0),
              viewport: viewport,
              pageIndex: 0
            });
            textLayer.setTextContent(textContent);
          });
          var renderContext = {
            canvasContext: context,
            viewport: viewport
          };

          page.render(renderContext);
        }
      });
    }
  }
})(jQuery, Drupal, drupalSettings, PDFJS);