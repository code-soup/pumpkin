/**
 * Pre-loaders configuration (executed before other loaders)
 */

export default (config) => {
    // Validate config parameter
    if (!config || !config.paths) {
        throw new Error('[pre loader] Config object with paths is required');
    }

    return {
        enforce: 'pre',
        test: /\.(js|s?[ca]ss)$/,
        include: [config.paths.src, config.paths.templates],
        loader: 'import-glob',
    };
};