import type { Channel } from './channel';
import type { Trustbadge } from './trustbadge';
import type { Widgets } from './widgets';
import type { StructuredMarkup } from './structured-markup';
import type { OrderStatus } from "./order-status";
export interface Settings {
    client_id?: string;
    client_secret?: string;
    channels: Channel[];
    trustbadges: {
        [key: string]: Trustbadge;
    };
    structured_markup: {
        [key: string]: StructuredMarkup;
    };
    used_order_statuses: {
        [key: string]: {
            product?: OrderStatus;
            service?: OrderStatus;
        };
    };
    widgets: {
        [key: string]: Widgets;
    };
}
