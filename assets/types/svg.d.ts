/**
 * SVG files are imported as raw markup strings (webpack asset/source).
 */
declare module '*.svg' {
    const content: string;
    export default content;
}
