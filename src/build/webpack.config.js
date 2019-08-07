"use strict"; // eslint-disable-line

const webpack = require("webpack");
const merge = require("webpack-merge");
const CleanPlugin = require("clean-webpack-plugin");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const StyleLintPlugin = require("stylelint-webpack-plugin");
const CopyGlobsPlugin = require("copy-globs-webpack-plugin");
const FriendlyErrorsWebpackPlugin = require("friendly-errors-webpack-plugin");
const SVGSpritemapPlugin = require("svg-spritemap-webpack-plugin");

const desire = require("./util/desire");
const resolver = require("./util/resolve");
const config = require("./config");

const assetsFilenames = config.enabled.cacheBusting ? config.cacheBusting : "[name]";
const publicPath = config.env.production ? config.publicPathProduction : config.publicPath;

let webpackConfig = {
    context: config.paths.assets,
    entry: config.entry,
    devtool: config.enabled.sourceMaps ? "#source-map" : undefined,
    output: {
        path: config.paths.dist,
        publicPath: publicPath,
        filename: `scripts/${assetsFilenames}.js`,
    },
    mode: config.env.production ? "production" : "development",
    stats: {
        hash: false,
        version: false,
        timings: false,
        children: false,
        errors: false,
        errorDetails: false,
        warnings: false,
        chunks: false,
        modules: false,
        reasons: false,
        source: false,
        publicPath: false,
    },
    module: {
        rules: [
            {
                enforce: "pre",
                test: /\.js$/,
                include: config.paths.assets,
                use: "eslint",
            },
            {
                enforce: "pre",
                test: /\.(js|s?[ca]ss)$/,
                include: config.paths.assets,
                loader: "import-glob",
            },
            {
                test: /\.js$/,
                exclude: [/node_modules(?![/|\\](bootstrap|foundation-sites))/],
                use: [
                    { loader: "cache" },
                    {
                        loader: "babel",
                    },
                ],
            },
            {
                test: /\.css$/,
                include: config.paths.assets,
                use: [
                    config.enabled.watcher ? "style" : MiniCssExtractPlugin.loader,
                    { loader: "cache" },
                    {
                        loader: "css",
                        options: {
                            sourceMap: config.enabled.sourceMaps,
                        },
                    },
                    {
                        loader: "postcss",
                        options: {
                            config: { path: __dirname, ctx: config },
                            sourceMap: config.enabled.sourceMaps,
                        },
                    },
                ],
            },
            {
                test: /\.scss$/,
                include: config.paths.assets,
                use: [
                    config.enabled.watcher ? "style" : MiniCssExtractPlugin.loader,
                    {
                        loader: "css",
                        options: { sourceMap: config.enabled.sourceMaps },
                    },
                    {
                        loader: "postcss",
                        options: {
                            config: {
                                path: __dirname, ctx: config,
                            },
                            sourceMap: config.enabled.sourceMaps,
                        },
                    },
                    {
                        loader: "resolve-url",
                        options: {
                            sourceMap: config.enabled.sourceMaps,
                        },
                    },
                    {
                        loader: "sass",
                        options: {
                            sourceMap: config.enabled.sourceMaps,
                            sourceComments: true,
                        },
                    },
                ],
            },
            {
                test: /\.(ttf|otf|eot|woff2?|png|jpe?g|gif|svg|ico)$/,
                include: config.paths.assets,
                loader: "url",
                options: {
                    limit: 4096,
                    name: `[path]${assetsFilenames}.[ext]`,
                },
            },
            {
                test: /\.(ttf|otf|eot|woff2?|png|jpe?g|gif|svg|ico)$/,
                include: /node_modules/,
                loader: "url",
                options: {
                    limit: 4096,
                    outputPath: "vendor/",
                    name: `${config.cacheBusting}.[ext]`,
                },
            },
        ],
    },
    resolveLoader: {
        moduleExtensions: ["-loader"],
    },
    resolve: {
        modules: [config.paths.assets, "node_modules"],
        enforceExtension: false,
        alias: {
            "@utils": resolver("../scripts/util"),
            "@configs": resolver("../config.json"),
        },
    },
    externals: {
        jquery: "jQuery",
    },
    plugins: [
        new CleanPlugin(),
        new SVGSpritemapPlugin("src/icons/*.svg", {
            output: {
                svg4everybody: true,
                filename: "sprite/spritemap.svg",
                svgo: true,
            },
            styles: "src/styles/autoload/_sprites.scss",
        }),
        new CopyGlobsPlugin({
            pattern: config.copy,
            output: `[path]${assetsFilenames}.[ext]`,
            manifest: config.manifest,
        }),
        new CopyGlobsPlugin({
            pattern: config.copyUnmodified,
            output: "[path][name].[ext]",
        }),
        new MiniCssExtractPlugin({
            filename: `styles/${assetsFilenames}.css`,
            chunkFilename: `styles/[id].${assetsFilenames}.css`,
        }),
        new webpack.ProvidePlugin({
            $: "jquery",
            jQuery: "jquery",
            "window.jQuery": "jquery",
        }),
        new webpack.LoaderOptionsPlugin({
            minimize: config.enabled.optimize,
            debug: config.enabled.watcher,
            stats: { colors: true },
        }),
        new webpack.LoaderOptionsPlugin({
            test: /\.s?css$/,
            options: {
                output: { path: config.paths.dist },
                context: config.paths.assets,
            },
        }),
        new webpack.LoaderOptionsPlugin({
            test: /\.js$/,
            options: {
                eslint: {failOnWarning: false, failOnError: true },
            },
        }),
        /* new StyleLintPlugin({
            failOnError: !config.enabled.watcher,
            syntax: "scss",
        }), */
        new FriendlyErrorsWebpackPlugin(),
    ],
}; /** Let's only load dependencies as needed */

/* eslint-disable global-require */ if (config.enabled.optimize) {
    webpackConfig = merge(webpackConfig, require("./webpack.config.optimize"));
}

if (config.env.production) {
    webpackConfig.plugins.push(new webpack.NoEmitOnErrorsPlugin());
}

if (config.enabled.cacheBusting) {
    const WebpackAssetsManifest = require("webpack-assets-manifest");

    webpackConfig.plugins.push(
        new WebpackAssetsManifest({
            output: "assets.json",
            space: 2,
            writeToDisk: false,
            assets: config.manifest,
            replacer: require("./util/assetManifestsFormatter"),
        })
    );
}

if (config.enabled.watcher) {
    webpackConfig.entry = require("./util/addHotMiddleware")(
        webpackConfig.entry
    );
    webpackConfig = merge(webpackConfig, require("./webpack.config.watch"));
}

/**
 * During installation via sage-installer (i.e. composer create-project) some
 * presets may generate a preset specific config (webpack.config.preset.js) to
 * override some of the default options set here. We use webpack-merge to merge
 * them in. If you need to modify Sage's default webpack config, we recommend
 * that you modify this file directly, instead of creating your own preset
 * file, as there are limitations to using webpack-merge which can hinder your
 * ability to change certain options.
 */
module.exports = merge.smartStrategy({
    "module.loaders": "replace",
})(webpackConfig, desire(`${__dirname}/webpack.config.preset`));