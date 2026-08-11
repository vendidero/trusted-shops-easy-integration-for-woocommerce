export interface TrustbadgeChild {
    tag?: string;
    attributes: {
        [key: string]: {
            value?: string | number | boolean;
            attributeName?: string;
        };
    };
    children?: TrustbadgeChild[];
}
