import type { Settings } from './interfaces/settings'
import type { EventsLib } from './interfaces/events-lib'
import type { Params } from './interfaces/params'
import type { Trustbadge } from "./interfaces/trustbadge";
import type { Channel } from './interfaces/channel'
import type { Widgets } from './interfaces/widgets'
import type { OrderStatus } from './interfaces/order-status'

class BaseLayer {
    private static _instance: BaseLayer;

    private settings: Settings|null;
    
    private eventsLib: EventsLib;

    private params: Params;

    private constructor() {
        this.settings = null;
        this.eventsLib = ( window as any ).eventsLib as EventsLib;
        this.params = ( window as any ).ts_easy_integration_params as Params;
        
        this.registerEvents();
    }

    private sendingNotification( event: any, status: string, type = 'save' ) {
        this.eventsLib.dispatchAction({
            action: this.eventsLib.EVENTS.NOTIFICATION,
            payload: {
                event: event,
                status: status,
                type: type
            },
        });
    }

    private getLocaleCallback() {
        this.eventsLib.dispatchAction({
            action: this.eventsLib.EVENTS.SET_LOCALE,
            payload: this.params.locale,
        });
    }

    private getInformationOfSystemCallback() {
        this.eventsLib.dispatchAction({
            action: this.eventsLib.EVENTS.SET_INFORMATION_OF_SYSTEM,
            payload: {
                nameOfSystem: this.params.name_of_system,
                versionNumberOfSystem: this.params.version_of_system,
                versionNumberOfPlugin: this.params.version,
                allowsEstimatedDeliveryDate: true,
                allowsEventsByOrderStatus: false,
                allowsSendReviewInvitesForPreviousOrders: true,
                allowsSendReviewInvitesForProduct: true,
                allowsEditIntegrationCode: true,
                allowsSupportWidgets: true,
                useVersionNumberOfConnector: '2.0',
            },
        });
    }

    private async getCredentialsCallback() {
        await this.getCredentials().then( credentials => {
            this.eventsLib.dispatchAction({
                action: this.eventsLib.EVENTS.SET_CREDENTIALS_PROVIDED,
                payload: credentials,
            });
        });
    }

    private async saveCredentialsCallback(event: { payload: { clientId: string; clientSecret: string; }; }) {
        try {
            const settings = await this.getSettings().then( settings => {
                settings.client_id = event.payload.clientId;
                settings.client_secret = event.payload.clientSecret;

                return settings;
            }).then( settings => {
                return this.updateSettings( settings, true );
            }).then( () => {
                this.sendingNotification( this.eventsLib.EVENTS.SAVE_CREDENTIALS, 'success' );
            });
        } catch(e) {
            this.sendingNotification( this.eventsLib.EVENTS.SAVE_CREDENTIALS, 'error' );
        }
    }

    private getSalesChannelsCallback() {
        this.eventsLib.dispatchAction({
            action: this.eventsLib.EVENTS.SET_SALES_CHANNELS_PROVIDED,
            payload: this.params.sales_channels,
        });
    }

    private getOrderStatusesCallback( event: { payload: { id: string; salesChannelRef: string; eTrustedChannelRef: string; }; } ) {
        this.eventsLib.dispatchAction({
            action: this.eventsLib.EVENTS.SET_AVAILABLE_ORDER_STATUSES,
            payload: this.params.order_statuses.hasOwnProperty( event.payload.salesChannelRef ) ? this.params.order_statuses[ event.payload.salesChannelRef ] : [],
        });
    }

    private async getMappedChannelsCallback() {
        await this.getChannels().then( channels => {
            this.eventsLib.dispatchAction({
                action: this.eventsLib.EVENTS.SET_MAPPED_CHANNELS,
                payload: channels,
            })
        });
    }

    private async saveMappedChannelsCallback(event: { payload: Channel[]; }) {
        try {
            const settings = await this.getSettings().then( settings => {
                const currentChannels = settings.channels.map( (obj) => {
                    return obj.salesChannelRef + '_' + obj.eTrustedChannelRef;
                });

                const newChannels = event.payload.map( (obj) => {
                    return obj.salesChannelRef + '_' + obj.eTrustedChannelRef;
                });

                const orphantChannels = currentChannels.filter( item => newChannels.indexOf( item ) < 0 );

                orphantChannels.map( ( channelKey ) => {
                    delete settings.trustbadges[ channelKey ];
                    delete settings.widgets[ channelKey ];
                    delete settings.used_order_statuses[ channelKey ];
                });

                settings.channels = event.payload;

                return settings;
            }).then( settings => {
                return this.updateSettings( settings );
            }).then( () => {
                this.getMappedChannelsCallback().then( () => {
                    this.sendingNotification( this.eventsLib.EVENTS.SET_MAPPED_CHANNELS, 'success' );
                });
            } );
        } catch(e) {
            this.sendingNotification( this.eventsLib.EVENTS.SET_MAPPED_CHANNELS, 'error' );
        }
    }

    private async disconnectCallback() {
        try {
            await this.disconnect().then( result => {
                this.eventsLib.dispatchAction({ action: this.eventsLib.EVENTS.SET_DISCONNECTED, payload: null });
                this.settings = null;
            });
        } catch(e) {
            this.sendingNotification( this.eventsLib.EVENTS.SET_DISCONNECTED, 'error' );
        }
    }

    private getUniqueId( salesChannelRef: string, tsSalesChannelRef: string ) {
        return salesChannelRef + '_' + tsSalesChannelRef;
    }

    private async getTrustbadgeCallback( event: { payload: { id: string; salesChannelRef: string; eTrustedChannelRef: string; }; } ) {
        await this.getTrustbadge( this.getUniqueId( event.payload.salesChannelRef, event.payload.eTrustedChannelRef ) ).then( trustbadge => {
            this.eventsLib.dispatchAction({
                action: this.eventsLib.EVENTS.SET_TRUSTBADGE_CONFIGURATION_PROVIDED,
                payload: trustbadge ? trustbadge : { id: 'id' , children: [] },
            })
        });
    }

    private async saveTrustbadgeCallback(event: { payload: Trustbadge }) {
        try {
            const settings = await this.getSettings().then( settings => {
                settings.trustbadges[ this.getUniqueId( event.payload.salesChannelRef, event.payload.eTrustedChannelRef ) ] = event.payload;

                return settings;
            }).then( settings => {
                return this.updateSettings( settings );
            }).then( () => {
                this.getTrustbadgeCallback( { payload: event.payload } ).then( () => {
                    this.sendingNotification( this.eventsLib.EVENTS.SAVE_TRUSTBADGE_CONFIGURATION, 'success' );
                });
            });
        } catch(e) {
            this.sendingNotification( this.eventsLib.EVENTS.SAVE_TRUSTBADGE_CONFIGURATION, 'error' );
        }
    }

    private async getWidgetsCallback( event: { payload: { eTrustedChannelRef: string; salesChannelRef: string }; } ) {
        await this.getWidgets( this.getUniqueId( event.payload.salesChannelRef, event.payload.eTrustedChannelRef ) ).then( widgets => {
            this.eventsLib.dispatchAction({
                action: this.eventsLib.EVENTS.SET_WIDGET_PROVIDED,
                payload: widgets,
            })
        });
    }

    private getAdditionalWidgetLocationsCallback() {
        this.eventsLib.dispatchAction({
            action: this.eventsLib.EVENTS.SET_LOCATION_FOR_WIDGET,
            payload: this.params.widget_locations,
        });
    }

    private async getUsedOrderStatusesCallback( event: { payload: { id: string, eTrustedChannelRef: string; salesChannelRef: string }; } ) {
        await this.getUsedOrderStatuses( this.getUniqueId( event.payload.salesChannelRef, event.payload.eTrustedChannelRef ) ).then( usedOrderStatuses => {
            if ( usedOrderStatuses ) {
                this.eventsLib.dispatchAction({
                    action: this.eventsLib.EVENTS.SET_USED_ORDER_STATUSES,
                    payload: {
                        activeStatus: usedOrderStatuses,
                        id: event.payload.id,
                        eTrustedChannelRef: event.payload.eTrustedChannelRef,
                        salesChannelRef: event.payload.salesChannelRef
                    },
                });
            } else {
                const defaultStatus = {
                    'name': 'checkout',
                    'ID': 'checkout',
                    'event_type': 'checkout',
                };

                this.eventsLib.dispatchAction({
                    action: this.eventsLib.EVENTS.SET_USED_ORDER_STATUSES,
                    payload: {
                        activeStatus: {
                            'service': defaultStatus,
                            'product': defaultStatus,
                        },
                        id: event.payload.id,
                        eTrustedChannelRef: event.payload.eTrustedChannelRef,
                        salesChannelRef: event.payload.salesChannelRef
                    },
                });
            }
        });
    }

    private async saveUsedOrderStatusesCallback(event: { payload: { id: string, eTrustedChannelRef: string, salesChannelRef: string, activeStatus: { product: OrderStatus, service: OrderStatus } } }) {
        try {
            const settings = await this.getSettings().then( settings => {
                settings.used_order_statuses[ this.getUniqueId( event.payload.salesChannelRef, event.payload.eTrustedChannelRef ) ] = event.payload.activeStatus;

                return settings;
            }).then(settings => {
                return this.updateSettings( settings );
            }).then( () => {
                this.getUsedOrderStatusesCallback( { payload: event.payload } ).then( () => {
                    this.sendingNotification( this.eventsLib.EVENTS.SAVE_USED_ORDER_STATUSES, 'success' );
                });
            });
        } catch(e) {
            this.sendingNotification( this.eventsLib.EVENTS.SAVE_USED_ORDER_STATUSES, 'error' );
        }
    }

    private async saveWidgetsCallback(event: { payload: Widgets }) {
        try {
            const settings = await this.getSettings().then( settings => {
                settings.widgets[ this.getUniqueId( event.payload.salesChannelRef, event.payload.eTrustedChannelRef ) ] = event.payload;

                return settings;
            }).then(settings => {
                return this.updateSettings( settings );
            }).then( () => {
                this.getWidgetsCallback( { payload: event.payload } ).then( () => {
                    this.sendingNotification( this.eventsLib.EVENTS.SAVE_WIDGET_CHANGES, 'success' );
                });
            });
        } catch(e) {
            this.sendingNotification( this.eventsLib.EVENTS.SAVE_WIDGET_CHANGES, 'error' );
        }
    }

    private async exportOrdersCallback( event: { payload: { id: string; numberOfDays: number, salesChannelRef: string, includeProductData: boolean } } ) {
        try {
            await this.exportOrders( 1, event.payload.numberOfDays, event.payload.includeProductData, event.payload.salesChannelRef ).then( ( url ) => {
                const a = document.createElement( 'a' );
                a.href = url;
                a.download = 'orders.csv';
                document.body.appendChild( a );
                a.click();
                document.body.removeChild( a );

                this.eventsLib.dispatchAction({
                    action: this.eventsLib.EVENTS.SET_EXPORT_PREVIOUS_ORDER,
                    payload: event.payload as { id: string; numberOfDays: number },
                });
            } );
        } catch(e) {
            this.sendingNotification( event, 'error', 'exportTimeout' );
        }
    }

    private async registerEvents() {
        this.eventsLib.registerEvents({
            [ this.eventsLib.EVENTS.GET_INFORMATION_OF_SYSTEM ]: this.getInformationOfSystemCallback.bind( this ),
            [ this.eventsLib.EVENTS.GET_LOCALE ]: this.getLocaleCallback.bind( this ),
            [ this.eventsLib.EVENTS.SAVE_CREDENTIALS ]: this.saveCredentialsCallback.bind( this ),
            [ this.eventsLib.EVENTS.GET_CREDENTIALS_PROVIDED ]: this.getCredentialsCallback.bind( this ),
            [ this.eventsLib.EVENTS.GET_SALES_CHANNELS_PROVIDED ]: this.getSalesChannelsCallback.bind( this ),
            [ this.eventsLib.EVENTS.GET_MAPPED_CHANNELS ]: this.getMappedChannelsCallback.bind( this ),
            [ this.eventsLib.EVENTS.SAVE_MAPPED_CHANNEL ]: this.saveMappedChannelsCallback.bind( this ),
            [ this.eventsLib.EVENTS.DISCONNECTED ]: this.disconnectCallback.bind( this ),
            [ this.eventsLib.EVENTS.GET_TRUSTBADGE_CONFIGURATION_PROVIDED ]: this.getTrustbadgeCallback.bind( this ),
            [ this.eventsLib.EVENTS.SAVE_TRUSTBADGE_CONFIGURATION ]: this.saveTrustbadgeCallback.bind( this ),
            [ this.eventsLib.EVENTS.GET_LOCATION_FOR_WIDGET ]: this.getAdditionalWidgetLocationsCallback.bind( this ),
            [ this.eventsLib.EVENTS.GET_WIDGET_PROVIDED ]: this.getWidgetsCallback.bind( this ),
            [ this.eventsLib.EVENTS.SAVE_WIDGET_CHANGES ]: this.saveWidgetsCallback.bind( this ),
            [ this.eventsLib.EVENTS.EXPORT_PREVIOUS_ORDER ]: this.exportOrdersCallback.bind( this ),
            [ this.eventsLib.EVENTS.GET_AVAILABLE_ORDER_STATUSES ]: this.getOrderStatusesCallback.bind( this ),
            [ this.eventsLib.EVENTS.GET_USED_ORDER_STATUSES ]: this.getUsedOrderStatusesCallback.bind( this ),
            [ this.eventsLib.EVENTS.SAVE_USED_ORDER_STATUSES ]: this.saveUsedOrderStatusesCallback.bind( this ),
        });
    }

    private async exportOrders( step: number, numberOfDays: number, includeProductData: boolean, salesChannelRef = '', filenameSuffix = '' ) : Promise<string> {
        return await fetch( this.getAjaxUrl( 'export_orders' ), {
            headers: {
                'Content-Type': 'application/json;charset=UTF-8'
            },
            method: 'POST',
            body: JSON.stringify({ 'step': step, 'sales_channel': salesChannelRef, 'number_of_days': numberOfDays, 'include_product_data': includeProductData, 'filename_suffix': filenameSuffix } )
        }).catch( () => {
            throw new TypeError( "Error while exporting orders." );
        }).then(
            res => res.json()
        ).then( result => {
            if ( 'done' === result.step ) {
                return result.url;
            } else if ( result.hasOwnProperty( 'step' ) && result.step > step ) {
                return this.exportOrders( result.step, result.number_of_days, result.include_product_data, result.sales_channel, result.filename_suffix );
            } else {
                throw new TypeError( "Error while exporting orders." );
            }
        }).catch( () => {
            throw new TypeError( "Error while exporting orders." );
        });
    }

    private getAjaxUrl( action:string, security = '' ): string {
        const nonce = action + '_nonce' in this.params ? this.params[ action + '_nonce' ] : security;
        action      = 'ts_easy_integration_' + action;

        return this.params.ajax_url + '?action=' + action + '&security=' + nonce;
    }

    private async getSettings(): Promise<Settings> {
        if ( ! this.settings ) {
            const response = await fetch( this.getAjaxUrl( 'get_settings' ), {
                headers: {
                    'Content-Type': 'application/json;charset=UTF-8'
                },
                method: 'POST',
                body: JSON.stringify({})
            }).catch( () => {
                throw new TypeError("Error message");
            });

            return await response.json().then( data => {
                this.settings = data.settings as Settings;

                const client_id     = response.headers.get('Client-Id');
                const client_secret = response.headers.get('Client-Secret');

                this.settings.client_id     = client_id !== null ? atob( client_id ) : '';
                this.settings.client_secret = client_secret !== null ? atob( client_secret ) : '';

                // Force parsing index signatures as objects to make sure JSON.stringify works as expected
                this.settings.trustbadges         = { ...this.settings.trustbadges }
                this.settings.widgets             = { ...this.settings.widgets }
                this.settings.used_order_statuses = { ...this.settings.used_order_statuses }

                return this.settings;
            } ).catch( () => {
                throw new TypeError("Error message");
            });
        }

        if ( ! this.settings ) {
            throw new TypeError("Error message");
        }

        return this.settings;
    }

    private updateSettingsData( result: Settings ) {
        const client_id     = this.settings ? this.settings.client_id : '';
        const client_secret = this.settings ? this.settings.client_secret : '';

        const settings         = result;
        settings.client_id     = client_id;
        settings.client_secret = client_secret;

        return settings;
    }

    private async disconnect(): Promise<boolean> {
        return await fetch( this.getAjaxUrl( 'disconnect' ), {
            headers: {
                'Content-Type': 'application/json;charset=UTF-8'
            },
            method: 'POST',
            body: JSON.stringify({})
        }).then(
            res => res.json()
        ).then( result => {
            if ( ! result.success ) {
                throw new TypeError( result.message );
            }

            return true;
        }).catch( () => {
            throw new TypeError( "Error while updating settings." );
        });
    }

    private async updateSettings( settings: Settings, andCredentials = false ): Promise<Settings> {
        const settingsToUpdate       = { ...settings };
        settingsToUpdate.trustbadges = { ...settings.trustbadges }
        settingsToUpdate.widgets     = { ...settings.widgets }
        settingsToUpdate.used_order_statuses = { ...settings.used_order_statuses }

        const headers = {
            'Content-Type': 'application/json;charset=UTF-8',
        };

        if ( ! andCredentials ) {
            delete settingsToUpdate.client_secret;
            delete settingsToUpdate.client_id;
        }

        return await fetch( this.getAjaxUrl( 'update_settings' ), {
            headers,
            method: 'POST',
            body: JSON.stringify( { ...settingsToUpdate } )
        }).then(
            res => res.json()
        ).then( result => {
            if ( ! result.success ) {
                throw new TypeError( result.message );
            }

            this.settings = this.updateSettingsData( result.settings );

            if ( ! this.settings ) {
                throw new TypeError( "Error while updating settings." );
            }

            return this.settings;
        }).catch( () => {
            throw new TypeError( "Error while updating settings." );
        });
    }

    private async getCredentials() {
        const credentials = {
            'clientId': '',
            'clientSecret': '',
        }

        try {
            return await this.getSettings().then( settings => {
                return {
                    'clientId': settings.client_id,
                    'clientSecret': settings.client_secret
                }
            });
        } catch(e) {
            return credentials;
        }
    }

    private async getChannels(): Promise<Channel[]> {
        try {
            return await this.getSettings().then( settings => {
                return settings.channels;
            });
        } catch(e) {
            return [];
        }
    }

    private async getChannelBySalesRef( salesRef: string ): Promise<Channel|null> {
        try {
            return await this.getSettings().then( settings => {
                return settings.channels.filter( channel => channel.salesChannelRef === salesRef )[0];
            });
        } catch(e) {
            return null;
        }
    }

    private async getChannelById( salesRef: string, etrustedRef: string ): Promise<Channel|null> {
        try {
            return await this.getSettings().then( settings => {
                return settings.channels.filter( channel => channel.salesChannelRef === salesRef && channel.eTrustedChannelRef === etrustedRef )[0];
            });
        } catch(e) {
            return null;
        }
    }

    private async getTrustbadge( id: string ): Promise<Trustbadge|null> {
        try {
            return await this.getSettings().then( settings => {
                return settings.trustbadges.hasOwnProperty( id ) ? settings.trustbadges[ id ] : null;
            });
        } catch(e) {
            return null;
        }
    }

    private async getWidgets( id: string ): Promise<Widgets|null> {
        try {
            return await this.getSettings().then( settings => {
                return settings.widgets.hasOwnProperty( id ) ? settings.widgets[ id ] : null;
            });
        } catch(e) {
            return null;
        }
    }

    private async getUsedOrderStatuses( id: string ): Promise<{ product?: OrderStatus, service?: OrderStatus }|null> {
        try {
            return await this.getSettings().then( settings => {
                return settings.used_order_statuses.hasOwnProperty( id ) ? settings.used_order_statuses[ id ] : null;
            });
        } catch(e) {
            return null;
        }
    }

    public static get Instance() {
        return this._instance || ( this._instance = new this() );
    }
}

const baseLayer = BaseLayer.Instance;