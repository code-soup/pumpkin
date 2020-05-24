const cssnanoConfig = {
    preset: ['default', { discardComments: { removeAll: true } }],
};

module.exports = ({ options }) => {
    return {
        parser: options.enabled.production ? 'postcss-safe-parser' : undefined,
        plugins: {
            autoprefixer: true,
            cssnano: options.enabled.production ? cssnanoConfig : false,
        },
    };
};
