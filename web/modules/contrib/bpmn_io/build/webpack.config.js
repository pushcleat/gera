var CopyPlugin = require('copy-webpack-plugin');

var path = require('path');

module.exports = {
  mode: 'production',
  entry: './src/bpmn-modeller.js',
  output: {
    path: path.resolve(__dirname, '../webpack'),
    filename: 'bpmn-modeller.js'
  },
  plugins: [
    new CopyPlugin({
      patterns: [
        { from: 'node_modules/bpmn-js/dist/assets', to: 'vendor/bpmn-js/assets' },
        { from: 'node_modules/bpmn-js-element-templates/dist/assets', to: 'vendor/bpmn-js-element-templates/assets' },
        { from: 'node_modules/bpmn-js-properties-panel/dist/assets', to: 'vendor/bpmn-js-properties-panel/assets' },
        { from: 'node_modules/@bpmn-io/element-template-chooser/dist/element-template-chooser.css', to: 'vendor/bpmn-js-properties-panel/assets/element-template-chooser.css' },
      ]
    })
  ]
};
