module.exports = {
    extends: "stylelint-config-standard",
    rules: {
        "indentation": 4,
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
    },
};
