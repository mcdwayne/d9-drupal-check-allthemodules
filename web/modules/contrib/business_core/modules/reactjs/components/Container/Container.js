import BaseComponent from '../BaseComponent';
import EntityList from '../EntityList/EntityList';
import React from 'react';

class Container extends BaseComponent {
  render() {
    var design = this.props.design;
    if (design != undefined) {
      var components = design.components;
      if (Object.prototype.toString.call(components) === '[object Array]') {
        return (
          <div className="container">
            <div className="row">
              { components.map((component) => {
                if (component.id == undefined) {
                  component.id = Math.random().toString();
                }
                this.createElement(component, {id: component.id});
              })}
            </div>
          </div>
        );
      }
      else {
        if (components.id == undefined) {
          components.id = Math.random().toString();
        }
        return this.createElement(components, {id: components.id});
      }
    }
    else {
      return null;
    }
  }

  createElement(component, config = []) {
    if (component.type in componentTypes) {
      return React.createElement(componentTypes[component.type], config);
    }
    else {
      let content = 'Component type ' + component.type + ' not found.';
      return (
        <div>{ content }</div>
      );
    }
  }
}

const componentTypes = {
  'Container': Container,
  'EntityList': EntityList,
};

export default Container;
