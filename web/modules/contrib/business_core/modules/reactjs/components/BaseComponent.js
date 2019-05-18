import PropTypes from 'prop-types';
import React from 'react';
import { connect } from 'react-redux';

class BaseComponentUI extends React.Component {

  static propTypes = {
    id: PropTypes.string.isRequired,
  };

}

const mapStateToProps = (state, ownProps) => {
  var id = ownProps.id;
  if (id != undefined && state.api['page--page'] != undefined) {
    const findComponent = (components, id) => {
      if (id == undefined) {
        return;
      }
      if (Object.prototype.toString.call(components) === '[object Array]') {
        components.forEach(function (component) {
          if (component.id == id) {
            return component;
          }
          if (component.Type == 'Container') {
            component = findComponent(component.settings.components, id);
            if (component != undefined) {
              return component;
            }
          }
        });
      }
      else {
        if (components.id == id) {
          return components;
        }
        if (components.Type == 'Container') {
          component = findComponent(components.settings.components, id);
          if (component != undefined) {
            return component;
          }
        }
      }
    };
    var design = findComponent(state.api['page--page']['data'][0]['attributes'], id);
    if (design != undefined) {
      return { design };
    }
  }

  return {};
};

const BaseComponent = connect(
  mapStateToProps
)(BaseComponentUI);

export default BaseComponent;
