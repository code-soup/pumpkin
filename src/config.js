module.exports = {
    entry: {
        main: ["scripts/main.js", "styles/main.scss"],
        admin: ["scripts/admin.js", "styles/admin.scss"],
    },
    openBrowserOnWatch: false,
    showErrorsInBrowser: false,
    useSSLinDev: false,
    publicPath: "pumpkin/wp-content/themes/pumpkin",
    publicPathProd: "wp-content/themes/pumpkin",
    devUrl: "http://cs.lan",
    proxyUrl: "http://localhost",
    proxyPort: 3000,
    watch: ["inc/**/*.php", "templates/**/*.php"],
};