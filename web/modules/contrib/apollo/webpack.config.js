const path = require('path');

module.exports = {
  entry: './js/src/index.js',
  output: {
    path: path.resolve(__dirname, 'js/dist'),
    filename: 'apollo-client.js',
  },
};
