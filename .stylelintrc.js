module.exports = {
    extends: "stylelint-config-wordpress/scss",
    ignoreFiles: ["src/styles/_npm-modules/_sprites.scss"],
    rules: {
        indentation: 4,
        "no-empty-source": null,
        "at-rule-no-unknown": [
            true,
            {
                ignoreAtRules: [
                    "extend",
                    "at-root",
                    "debug",
                    "warn",
                    "error",
                    "if",
                    "else",
                    "for",
                    "each",
                    "while",
                    "mixin",
                    "include",
                    "content",
                    "return",
                    "function",
                    "tailwind",
                    "apply",
                    "responsive",
                    "variants",
                    "screen",
                ],
            },
        ],
        "at-rule-empty-line-before": [
            "always",
            {
                except: [
                    "inside-block",
                    "first-nested",
                    "after-same-name",
                    "blockless-after-blockless",
                    "blockless-after-same-name-blockless",
                ],
            },
        ],
    },
};