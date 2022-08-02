import type { Widget } from './widget';
export interface Widgets {
    id: string;
    salesChannelRef: string;
    eTrustedChannelRef: string;
    children: Array<{
        tag?: string;
        attributes?: {
            [key: string]: {
                value?: string;
                attributeName?: string;
            };
        };
        children: Widget[];
    }>;
}
