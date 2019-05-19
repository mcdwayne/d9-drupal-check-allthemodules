module.exports = {
  entry: {
    app: './src/app.js',
    client: './src/client.js',
    helpers: './src/helpers.js',
  },
  module: {
    rules: [
      {
        test: /\.(js|jsx)$/,
        exclude: /node_modules/,
        use: {
          loader: "babel-loader"
        }
      },
      {
        test: /\.css$/,
        use: [
          { loader: "style-loader" },
          { loader: "css-loader" }
        ]
      },
    ]
  },
  output: {
    path: __dirname + '/dist',
    filename: '[name].js',
  },
  devServer: {
    contentBase: './dist'
  }
};
