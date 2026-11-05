/**
 * Minimal type declarations for the WordPress Interactivity API script module
 * which is provided by WordPress core at runtime (import map).
 */
declare module '@wordpress/interactivity' {
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    export function store( namespace: string, definition?: Record<string, any> ): any;
    export function getContext<T = Record<string, unknown>>( namespace?: string ): T;
    export function getElement(): { ref: HTMLElement | null; attributes: Record<string, unknown> };
}
