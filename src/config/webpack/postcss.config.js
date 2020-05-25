const cssnanoConfig = {
    preset: ['default'],
};

module.exports = ({ options }) => {
    return {
        plugins: {
            autoprefixer: true,
            cssnano: options.enabled.production ? cssnanoConfig : false,
        },
    };
};
