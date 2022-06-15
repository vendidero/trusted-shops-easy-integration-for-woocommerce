import type { WidgetLocation } from './widget-location';
export interface Params {
    ajax_url: string;
    update_settings_nonce: string;
    get_settings_nonce: string;
    disconnect_nonce: string;
    locale: string;
    name_of_system: string;
    version_of_system: string;
    version: string;
    widget_locations: [];
    sale_channels: WidgetLocation[];
}
