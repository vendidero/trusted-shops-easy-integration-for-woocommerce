import type { SaleChannel } from './sale-channel'
import type { WidgetLocation } from './widget-location'
import {OrderStatus} from "./order-status";

export interface Params {
    ajax_url: string;
    update_settings_nonce: string;
    get_settings_nonce: string;
    export_orders_nonce: string;
    disconnect_nonce: string;
    locale: string;
    name_of_system: string;
    i18n_change_credentials: string;
    i18n_disconnect: string;
    i18n_export: string;
    order_statuses: {
        [key: string]: OrderStatus[]
    }
    version_of_system: string;
    supports_estimated_delivery_date: boolean;
    version: string;
    widget_locations: [],
    sales_channels: WidgetLocation[];
}