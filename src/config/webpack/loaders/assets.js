/**
 * Asset (images, fonts, etc.) processing configuration
 * Note: SVG files in src/icons are handled by SVGSpritemapPlugin, not this loader
 */
export default (config) => ({
    test: /\.(ttf|otf|eot|woff2?|png|jpe?g|webp|svg|gif|ico)$/,
    exclude: config?.paths?.icons ? [config.paths.icons] : [],
    type: 'asset',
    parser: {
        dataUrlCondition: {
            maxSize: 4 * 1024, // 4kb
        },
    },
    generator: {
        // Define the output filename for assets that are emitted as files.
        filename: 'static/[name].[contenthash][ext]',
    },
});