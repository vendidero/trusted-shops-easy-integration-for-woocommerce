export interface TrstdLoginAttribute {
    value?: string | boolean;
    attributeName?: string;
}
export interface TrstdLogin {
    id: string;
    salesChannelRef: string;
    eTrustedChannelRef: string;
    configuration: {
        script?: {
            tag?: string;
            attributes?: {
                [key: string]: TrstdLoginAttribute | string;
            };
        };
        integration?: {
            applicationType?: string;
            tag?: string;
            location?: {
                id: string;
                name?: string;
            };
            trstdLoginEnabled?: boolean;
        };
        [key: string]: unknown;
    };
    [key: string]: unknown;
}
export interface TrstdLoginLocation {
    id: string;
    name: string;
}
