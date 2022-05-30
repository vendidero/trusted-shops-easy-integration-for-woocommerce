import type { Settings } from './interfaces/settings'
import type { EventsLib } from './interfaces/events-lib'
import type { Params } from './interfaces/params'
import type { SaleChannel } from './interfaces/sale-channel'
import type { Trustbadge } from "./interfaces/trustbadge";
import type { Channel } from './interfaces/channel'
import type { Widgets } from './interfaces/widgets'

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

    private sendingNotification(event: any, status: string, type = 'save') {
        console.log("Notification", event, status);

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
            action: this.eventsLib.EVENTS.SET_INFORMATION_OF_SYSTEAM,
            payload: {
                nameOfSystem: this.params.name_of_system,
                versionNumberOfSystem: this.params.version_of_system,
                versionNumberOfPlugin: this.params.version,
            },
        });
    }

    private async getCredentialsCallback() {
        await this.getCredentials().then( credentials => {
            this.eventsLib.dispatchAction({
                action: this.eventsLib.EVENTS.SET_CREDENTIALS_PROVIDED,
                payload: credentials,
            })
        });
    }

    private async saveCredentialsCallback(event: { payload: { clientId: string; clientSecret: string; }; }) {
        try {
            const settings = await this.getSettings().then( settings => {
                settings.client_id = event.payload.clientId;
                settings.client_secret = event.payload.clientSecret;

                return settings;
            }).then( settings => {
                return this.updateSettings( settings );
            });

            this.sendingNotification( this.eventsLib.EVENTS.SAVE_CREDENTIALS, 'success' );
        } catch(e) {
            this.sendingNotification( this.eventsLib.EVENTS.SAVE_CREDENTIALS, 'error' );
        }
    }

    private getSalesChannelsCallback() {
        this.eventsLib.dispatchAction({
            action: this.eventsLib.EVENTS.SET_SALES_CHANNELS_PROVIDED,
            payload: this.params.sale_channels,
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
                settings.channels = event.payload;

                return settings;
            }).then( settings => {
                return this.updateSettings(settings);
            });

            await this.getMappedChannelsCallback().then( () => {
                this.sendingNotification( this.eventsLib.EVENTS.SET_MAPPED_CHANNELS, 'success' );
            });
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

    private getUniqueId( tsChannelRef: string, salesChannelRef: string ) {
        return tsChannelRef + '_' + salesChannelRef;
    }

    private async getTrustbadgeCallback( event: { payload: { id: string; salesChannelRef: string }; } ) {
        await this.getTrustbadge( event.payload.salesChannelRef ).then( trustbadge => {
            this.eventsLib.dispatchAction({
                action: this.eventsLib.EVENTS.SET_TRUSTBADGE_CONFIGURATION_PROVIDED,
                payload: trustbadge ? trustbadge : { id: 'id' , children: [] },
            })
        });
    }

    private async saveTrustbadgeCallback(event: { payload: Trustbadge }) {
        try {
            const settings = await this.getSettings().then( settings => {
                settings.trustbadges[ event.payload.salesChannelRef ] = event.payload;

                return settings;
            }).then(settings => {
                return this.updateSettings( settings );
            });

            await this.getTrustbadgeCallback( { payload: event.payload } ).then( () => {
                this.sendingNotification( this.eventsLib.EVENTS.SAVE_TRUSTBADGE_CONFIGURATION, 'success' );
            });
        } catch(e) {
            this.sendingNotification( this.eventsLib.EVENTS.SAVE_TRUSTBADGE_CONFIGURATION, 'error' );
        }
    }

    private async getWidgetsCallback( event: { payload: { id: string; salesChannelRef: string }; } ) {
        await this.getWidgets( event.payload.salesChannelRef ).then( widgets => {
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

    private async saveWidgetsCallback(event: { payload: Widgets }) {
        try {
            const settings = await this.getSettings().then( settings => {
                settings.widgets[ event.payload.salesChannelRef ] = event.payload;

                return settings;
            }).then(settings => {
                return this.updateSettings( settings );
            });

            await this.getWidgetsCallback( { payload: event.payload } ).then( () => {
                this.sendingNotification( this.eventsLib.EVENTS.SAVE_WIDGET_CHANGES, 'success' );
            });
        } catch(e) {
            this.sendingNotification( this.eventsLib.EVENTS.SAVE_WIDGET_CHANGES, 'error' );
        }
    }

    private async getHasReviewInvitesCallback( event: { payload: { id: string; salesChannelRef: string }; } ) {
        await this.getChannelById( event.payload.salesChannelRef ).then( channel => {
            if ( ! channel ) {
                this.eventsLib.dispatchAction({
                    action: this.eventsLib.EVENTS.SET_PRODUCT_REVIEW_FOR_CHANNEL,
                    payload: null,
                });
            } else {
                this.hasEnabledReviewInvites( event.payload.salesChannelRef ).then( hasEnabled => {
                    this.eventsLib.dispatchAction({
                        action: this.eventsLib.EVENTS.SET_PRODUCT_REVIEW_FOR_CHANNEL,
                        payload: hasEnabled ? channel : null,
                    })
                });
            }
        });
    }

    private async updateReviewInvitesCallback(channel: Channel, isActivated = false) {
        const event = isActivated ? this.eventsLib.EVENTS.ACTIVATE_PRODUCT_REVIEW_FOR_CHANNEL : this.eventsLib.EVENTS.DEACTIVATE_PRODUCT_REVIEW_FOR_CHANNEL;

        try {
            const settings = await this.getSettings().then( settings => {
                if ( isActivated && ! settings.enable_invites.includes( channel.salesChannelRef ) ) {
                    settings.enable_invites.push( channel.salesChannelRef );
                } else if ( ! isActivated && settings.enable_invites.includes( channel.salesChannelRef ) ) {
                    settings.enable_invites = settings.enable_invites.filter( item => item !== channel.salesChannelRef );
                }

                console.log(settings.enable_invites);

                return settings;
            }).then(settings => {
                return this.updateSettings( settings );
            });

            await this.getHasReviewInvitesCallback( { payload: { id: channel.eTrustedChannelRef, salesChannelRef: channel.salesChannelRef } } ).then( () => {
                this.sendingNotification( event, 'success' );
            });
        } catch(e) {
            this.sendingNotification( event, 'error' );
        }
    }

    private async activateReviewInvitesCallback(event: { payload: Channel }) {
        await this.updateReviewInvitesCallback( event.payload, true );
    }

    private async deactivateReviewInvitesCallback(event: { payload: Channel }) {
        await this.updateReviewInvitesCallback( event.payload, false );
    }

    private async registerEvents() {
        this.eventsLib.registerEvents({
            [ this.eventsLib.EVENTS.GET_INFORMATION_OF_SYSTEAM ]: this.getInformationOfSystemCallback.bind( this ),
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
            [ this.eventsLib.EVENTS.GET_PRODUCT_REVIEW_FOR_CHANNEL ]: this.getHasReviewInvitesCallback.bind( this ),
            [ this.eventsLib.EVENTS.ACTIVATE_PRODUCT_REVIEW_FOR_CHANNEL ]: this.activateReviewInvitesCallback.bind( this ),
            [ this.eventsLib.EVENTS.DEACTIVATE_PRODUCT_REVIEW_FOR_CHANNEL ]: this.deactivateReviewInvitesCallback.bind( this ),
            [this.eventsLib.EVENTS.EXPORT_PREVIOUS_ORDER]: (event: { payload: any; }) => {
                console.log('DEMO:EXPORT_PREVIOUS_ORDER', event.payload);
            },
        })
    }

    private getAjaxUrl(action:string, security = ''): string {
        const nonce = action + '_nonce' in this.params ? this.params[action + '_nonce'] : security;
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

            this.settings = await response.json().then( data => {
                return data.settings as Settings;
            } ).catch( () => {
                throw new TypeError("Error message");
            });

            const client_id     = response.headers.get('Client-Id');
            const client_secret = response.headers.get('Client-Secret');

            this.settings.client_id     = client_id !== null ? atob( client_id ) : '';
            this.settings.client_secret = client_secret !== null ? atob( client_secret ) : '';
            // Force parsing index signatures as objects to make sure JSON.stringify works as expected
            this.settings.trustbadges  = {...this.settings.trustbadges}
            this.settings.widgets      = {...this.settings.widgets}
        }

        if ( ! this.settings ) {
            throw new TypeError("Error message");
        }

        return this.settings;
    }

    private updateSettingsData(result: Settings) {
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

    private async updateSettings( settings: Settings ): Promise<Settings> {
        this.settings = await fetch( this.getAjaxUrl( 'update_settings' ), {
            headers: {
                'Content-Type': 'application/json;charset=UTF-8'
            },
            method: 'POST',
            body: JSON.stringify( settings )
        }).then(
            res => res.json()
        ).then( result => {
            if ( ! result.success ) {
                throw new TypeError( result.message );
            }

            return this.updateSettingsData( result.settings );
        }).catch( () => {
            throw new TypeError( "Error while updating settings." );
        });

        if ( ! this.settings ) {
            throw new TypeError( "Error while updating settings." );
        }

        return this.settings;
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

    private async getChannelById( salesRef: string ): Promise<Channel|null> {
        try {
            return await this.getSettings().then( settings => {
                return settings.channels.filter( channel => channel.salesChannelRef === salesRef )[0];
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

    private async hasEnabledReviewInvites( id: string ): Promise<boolean> {
        try {
            return await this.getSettings().then( settings => {
                return settings.enable_invites.includes( id );
            });
        } catch(e) {
            return false;
        }
    }

    public static get Instance() {
        return this._instance || ( this._instance = new this() );
    }
}

const baseLayer = BaseLayer.Instance;