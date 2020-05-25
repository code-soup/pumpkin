/**
 * Webpack modules
 */

const config = require("../config");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");

module.exports = {
    rules: [
        {
            enforce: "pre",
            test: /\.js$/,
            include: config.paths.src,
            use: "eslint",
        },
        {
            enforce: "pre",
            test: /\.(js|s?[ca]ss)$/,
            include: config.paths.src,
            loader: "import-glob",
        },
        {
            test: /\.js$/,
            exclude: [/node_modules/],
            use: [{ loader: "cache" }, { loader: "babel" }],
        },
        {
            test: /\.s[ac]ss$/i,
            include: config.paths.src,
            use: [
                config.enabled.watcher
                    ? "style"
                    : MiniCssExtractPlugin.loader,
                {
                    loader: "css",
                    options: { importLoaders: 3 },
                },
                {
                    loader: "postcss",
                    options: {
                        config: {
                            path: __dirname,
                            ctx: config,
                        },
                    },
                },
                { loader: "resolve-url" },
                { loader: "sass" },
            ],
        },
        {
            test: /\.(ttf|otf|eot|woff2?|png|jpe?g|gif|svg|ico)$/,
            include: config.paths.src,
            loader: "url",
            options: {
                limit: 4096,
                name: `[path]${config.fileName}.[ext]`,
            },
        },
        {
            test: /\.(ttf|otf|eot|woff2?|png|jpe?g|gif|svg|ico)$/,
            include: /node_modules/,
            loader: "url",
            options: {
                limit: 4096,
                outputPath: "vendor/",
                name: `${config.fileName}.[ext]`,
            },
        },
    ],
};