const path = require("path");
const ESLintPlugin = require("eslint-webpack-plugin");

const tsRule = ( compilerOptions = {} ) => ({
    test: /\.ts$/,
    exclude: /node_modules/,
    use: [
        "babel-loader",
        {
            loader: "ts-loader",
            options: { compilerOptions },
        },
    ],
});

module.exports = ({ development }) => {
    const shared = {
        devtool: development ? "inline-source-map" : false,
        mode: development ? "development" : "production",
        resolve: {
            extensions: [".ts", ".js", ".json"],
        },
    };

    return [
        {
            ...shared,
            entry: "./assets/base-layer.ts",
            output: {
                filename: "base-layer.js",
                path: path.resolve(__dirname, "dist")
            },
            module: {
                rules: [ tsRule() ],
            },
            plugins: [new ESLintPlugin({ extensions: ["ts"] })],
        },
        /**
         * #trstd login block editor script (classic script, uses the global wp.* packages).
         */
        {
            ...shared,
            entry: "./assets/blocks/trstd-login/index.ts",
            output: {
                filename: "index.js",
                path: path.resolve(__dirname, "dist/blocks/trstd-login")
            },
            module: {
                rules: [ tsRule() ],
            },
        },
        /**
         * #trstd login block view script module (Interactivity API).
         */
        {
            ...shared,
            entry: "./assets/blocks/trstd-login/view.ts",
            experiments: {
                outputModule: true,
            },
            output: {
                filename: "view.js",
                path: path.resolve(__dirname, "dist/blocks/trstd-login"),
                module: true,
                chunkFormat: "module",
                library: {
                    type: "module",
                },
            },
            externalsType: "module",
            externals: {
                "@wordpress/interactivity": "@wordpress/interactivity",
            },
            module: {
                rules: [ tsRule( { module: "ESNext" } ) ],
            },
        },
    ];
};
