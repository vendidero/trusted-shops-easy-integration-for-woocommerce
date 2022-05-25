import type { SaleChannel } from './sale-channel';
export interface Params {
    ajax_url: string;
    update_settings_nonce: string;
    get_settings_nonce: string;
    disconnect_nonce: string;
    locale: string;
    name_of_system: string;
    version_of_system: string;
    version: string;
    sale_channels: SaleChannel[];
}
