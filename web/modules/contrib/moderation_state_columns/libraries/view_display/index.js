import React from 'react';
import ReactDOM from 'react-dom';
import Display from './Display';

(function (Drupal) {

  Drupal.behaviors.moderation_state_columns_view_display = {
    attach: function (context) {
      const displays = context.querySelectorAll('.moderation-state-columns--component-container .moderation-state-columns--json-content');
      displays.forEach(element => {
        const { entities, states } = JSON.parse(element.innerText);
        ReactDOM.render(<Display entities={entities} states={states} />, element.parentNode);
        element.remove();
      });
    }
  };

})(Drupal);
