# Webpack React

Provides React configuration for the webpack module.

## Dependencies

- [Webpack](https://drupal.org/project/webpack)
- [Webpack Babel](https://drupal.org/project/webpack_babel)

## Installation

- `yarn add react react-dom @babel/preset-react`

## Example usage

_module.libraries.yml_
```yaml
test:
  webpack: true
  js:
    index.js: {}
```

_index.js_
```javascript
import React from "react";
import ReactDOM from "react-dom";

class Greeting extends React.Component {

  render() {
    return (
      <div>
        {this.props.text}
      </div>
    );
  }

}

ReactDOM.render(<Greeting text="Hi!" />, document.getElementById('page'));
```
