// webpack.config.js
const path = require('path');
const webpack = require('webpack');

module.exports = {
    context: path.resolve(__dirname, './js'),
    module: {
        rules: [
            {
                test: /\.es6.js$/,
                exclude: [/node_modules/],
                use: [{
                    loader: 'babel-loader',
                    options: { presets: ['env'] }
                }],
            },
        ],
    },
    entry: {
        app: './say_hello_dialogflow.js',
    },
    output: {
        path: path.resolve(__dirname, './js'),
        filename: '[name].bundle.js',
    },
};