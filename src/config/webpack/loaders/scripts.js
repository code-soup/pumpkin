/**
 * JavaScript processing configuration
 */

export default (config) => {
    // Validate config parameter
    if (!config || !config.paths) {
        throw new Error('[scripts loader] Config object with paths is required');
    }

    return {
        test: /\.jsx?$/,
        include: [config.paths.src, config.paths.templates],
        exclude: [/node_modules/],
        use: [
        {
            loader: 'babel-loader',
            options: {
                cacheDirectory: true,
                presets: [
                    '@babel/preset-env',
                ],
                plugins: [
                    [
                        '@babel/plugin-transform-runtime',
                        {
                            regenerator: true,
                        },
                    ],
                ],
            },
        },
    ],
    };
};