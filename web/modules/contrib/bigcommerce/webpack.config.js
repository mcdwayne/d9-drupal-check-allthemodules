const path = require('path');

module.exports = {
  entry: './js/checkout.es6.js',
  output: {
    filename: 'checkout.js',
    path: path.resolve(__dirname, 'js')
  }
};
