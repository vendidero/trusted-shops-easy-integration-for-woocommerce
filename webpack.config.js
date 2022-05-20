const path = require("path");
const ESLintPlugin = require("eslint-webpack-plugin");

module.exports = ({ development }) => ({
    entry: "./assets/base-layer.ts",
    devtool: development ? "inline-source-map" : false,
    mode: development ? "development" : "production",
    output: {
        filename: "base-layer.js",
        path: path.resolve(__dirname, "dist")
    },
    resolve: {
        extensions: [".ts"],
    },
    module: {
        rules: [
            {
                test: /\.ts$/,
                exclude: /node_modules/,
                use: ["babel-loader", "ts-loader"],
            },
        ],
    },
    plugins: [new ESLintPlugin({ extensions: ["ts"] })],
});