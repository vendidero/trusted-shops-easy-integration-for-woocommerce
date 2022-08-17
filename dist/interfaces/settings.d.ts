import type { Channel } from './channel';
import type { Trustbadge } from './trustbadge';
import type { Widgets } from './widgets';
export interface Settings {
    client_id?: string;
    client_secret?: string;
    channels: Channel[];
    enable_invites: string[];
    trustbadges: {
        [key: string]: Trustbadge;
    };
    widgets: {
        [key: string]: Widgets;
    };
}
