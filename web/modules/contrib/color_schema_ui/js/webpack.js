const path = require('path')
  fs = require('fs')
  MiniCssExtractPlugin = require("mini-css-extract-plugin");

module.exports = {
  entry: [
    'babel-polyfill',
    './src/drupal-behavior-function/node-form-client.js',
    './node_modules/awesome-notifications/dist/style.css'
  ],
  output: {
    path: path.join(__dirname, '/webpack-dist/'),
    filename: 'bundle.js'
  },
  plugins: [
    new MiniCssExtractPlugin({
      // Options similar to the same options in webpackOptions.output
      // both options are optional
      filename: "[name].css",
      chunkFilename: "[id].css"
    })
  ],
  module: {
    rules: [
      {
        exclude: [/(node_modules)/],
        use: [{
          loader: 'babel-loader',
          options: {
            ...JSON.parse(fs.readFileSync(path.resolve(__dirname, '.babelrc'))),
          }
        }]
      },
      {
        test: [/\.scss$/, /\.css$/],
        exclude: [/install\.scss/],
        use: [
          MiniCssExtractPlugin.loader,
          { loader: 'css-loader', options: { url: false, sourceMap: true } },
          { loader: 'sass-loader', options: { sourceMap: true } }
        ],
      },
      {
        test: [/install\.scss$/],
        use: [
          MiniCssExtractPlugin.loader,
          { loader: 'css-loader', options: { url: false, sourceMap: true } },
          { loader: 'sass-loader', options: { sourceMap: true } }
        ],
      }
    ]
  },
  devtool: 'source-map',
};
