/**
 * MyPCO Online — Blended Architecture Webpack Config
 *
 * Uses @wordpress/scripts as the base, with custom entry points for:
 * 1. Settings Page React app (the "Skin" for admin settings)
 * 2. Gutenberg Blocks (the "Skin" for content editing)
 *
 * Output goes to build/ which is loaded by the PHP block registrar
 * and settings page enqueue functions.
 */

const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
    ...defaultConfig,
    entry: {
        // React-powered Settings Page (replaces PHP forms)
        'settings': path.resolve( __dirname, 'src/settings/index.js' ),

        // Gutenberg Blocks (Calendar Events block, etc.)
        'blocks': path.resolve( __dirname, 'src/blocks/index.js' ),
    },
    output: {
        path: path.resolve( __dirname, 'build' ),
        filename: '[name].js',
    },
};
