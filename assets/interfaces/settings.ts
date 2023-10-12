import type { Channel } from './channel'
import type { Trustbadge } from './trustbadge'
import type { Widgets } from './widgets'
import {OrderStatus} from "./order-status";

export interface Settings {
    client_id?: string;
    client_secret?: string;
    channels: Channel[],
    trustbadges: {
        [key: string]: Trustbadge
    }
    used_order_statuses: {
        [key: string]: {
            product?: OrderStatus,
            service?: OrderStatus
        }
    }
    widgets: {
        [key: string]: Widgets
    }
}