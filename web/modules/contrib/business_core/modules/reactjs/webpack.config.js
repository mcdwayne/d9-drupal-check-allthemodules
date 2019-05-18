var path = require('path');

module.exports = {
  entry: './page.js',
  output: {
    path: path.resolve(__dirname + "/js"),
    filename: "page.js"
  },
  module: {
    loaders: [
      {
        test: /\.jsx?/,
        loader: 'babel-loader',

        query: {
          presets: ['stage-2', 'react']
        }
      }
    ]
  }
};

