/**
 * @file
 * Defines Javascript behaviors for the paragraphs_react module.
 */


(function ($, Drupal, drupalSettings) {

  'use strict';

  var paragraphs_react_data = $.map(drupalSettings.paragraphs_react, function(value, index) {
    return [{"index":index,"markup" : value.markup, "state" : value.paragraph_state}];
  });

  for(var i=0;i<paragraphs_react_data.length;i++) {
    paragraphs_react_data[i].markup = paragraphs_react_data[i].markup.replace('preactstate','{...paragraphs_react_data[i].state}');
    class ParagraphsReact extends React.Component {
      render() {
          //use babel with the paragraph JSX template
          var rcode = Babel.transform(paragraphs_react_data[i].markup, {presets: ['react']}).code;
          return eval(rcode);
      }
    }
    ReactDOM.render(
        React.createElement(ParagraphsReact, paragraphs_react_data[i].state, null),
        document.getElementById('preact-'+paragraphs_react_data[i].index)
    );
  }

})(jQuery, Drupal, drupalSettings);
