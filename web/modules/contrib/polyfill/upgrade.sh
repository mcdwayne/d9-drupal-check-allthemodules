#!/bin/bash

yarn upgrade
cp node_modules/babel-polyfill/dist/polyfill.min.js js/babel/polyfill.min.js
cp node_modules/whatwg-fetch/dist/fetch.umd.js js/fetch/fetch.js
cp node_modules/@webcomponents/webcomponentsjs/webcomponents-bundle.js js/webcomponents/webcomponents.min.js
