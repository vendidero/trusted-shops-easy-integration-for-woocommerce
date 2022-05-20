import type { Settings } from './interfaces/settings'
import type { EventsLib } from './interfaces/events-lib'
import type { Params } from './interfaces/params'
import type { SaleChannel } from './interfaces/sale-channel'
import type { Channel } from './interfaces/channel'

const getTrustedBadge = (id: string) => {
    return {
        id,
        children: [
            {
                tag: 'script',
                attributes: {
                    async: {
                        attributeName: 'async',
                    },
                    'data-desktop-y-offset': {
                        value: 8,
                        attributeName: 'data-desktop-y-offset',
                    },
                    'data-mobile-y-offset': {
                        value: 10,
                        attributeName: 'data-mobile-y-offset',
                    },
                    'data-desktop-disable-reviews': {
                        value: false,
                        attributeName: 'data-desktop-disable-reviews',
                    },
                    'data-desktop-enable-custom': {
                        value: false,
                        attributeName: 'data-desktop-enable-custom',
                    },
                    'data-desktop-position': {
                        value: 'right',
                        attributeName: 'data-desktop-position',
                    },
                    'data-desktop-custom-width': {
                        value: 156,
                        attributeName: 'data-desktop-custom-width',
                    },
                    'data-desktop-enable-fadeout': {
                        value: false,
                        attributeName: 'data-desktop-enable-fadeout',
                    },
                    'data-disable-mobile': {
                        value: false,
                        attributeName: 'data-disable-mobile',
                    },
                    'data-disable-trustbadge': {
                        value: false,
                        attributeName: 'data-disable-trustbadge',
                    },
                    'data-mobile-custom-width': {
                        value: 156,
                        attributeName: 'data-mobile-custom-width',
                    },
                    'data-mobile-disable-reviews': {
                        value: false,
                        attributeName: 'data-mobile-disable-reviews',
                    },
                    'data-mobile-enable-custom': {
                        value: false,
                        attributeName: 'data-mobile-enable-custom',
                    },
                    'data-mobile-position': {
                        value: 'right',
                        attributeName: 'data-mobile-position',
                    },
                    charset: {
                        value: 'UTF-8',
                        attributeName: 'charset',
                    },
                    src: {
                        value: `//widgets.trustedshops.com/js/${id}.js`,
                        attributeName: 'src',
                    },
                },
            },
        ],
    }
}

const mappedChannelsData = [
    {
        eTrustedChannelRef: 'chl-7e52920a-2722-4881-9908-ecec98c716e4',
        eTrustedLocale: 'de_DE',
        eTrustedName: 'eTrusted Any Shop',
        eTrustedUrl: 'www.newurl.com',
        eTrustedAccountRef: 'acc-9be350d7-bd85-4465-b4da-62fec5939f3c',
        salesChannelLocale: 'de_DE',
        salesChannelName: 'eTrusted Any Shop',
        salesChannelRef: 'shop-7e52920a-2722-4881-9908-ecec98c716e4',
        salesChannelUrl: 'www.newurl.com',
    },
]

const widgetLocation = [
    { id: '21d3d933eb93', name: 'Home Page' },
    {
        id: '21d3d933eb93',
        name: 'Footer',
    },
]

let dataWidgets = {
    children: [
        {
            tag: 'script',
            attributes: {
                src: {
                    value: 'https://integrations.etrusted.site/applications/widget.js/v2',
                    attributeName: 'src',
                },
                async: {
                    attributeName: 'async',
                },
                defer: {
                    attributeName: 'defer',
                },
            },
            children: [
                {
                    tag: 'etrusted-widget',
                    applicationType: 'product_star',
                    widgetId: 'wdg-deleted-in-api',
                    widgetLocation: {
                        id: '21d3d933eb93',
                        name: 'Footer',
                    },
                    attributes: {
                        id: {
                            value: 'wdg-deleted-in-api',
                            attributeName: 'data-etrusted-widget-id',
                        },
                        productIdentifier: {
                            attributeName: 'data-sku',
                        },
                    },
                },

                {
                    tag: 'etrusted-widget',
                    applicationType: 'product_review_list',
                    widgetId: 'wdg-92de735d-6d02-4ccf-941c-9d72d3c0cc46',
                    widgetLocation: {
                        id: '21d3d933eb93',
                        name: 'Footer',
                    },
                    extensions: {
                        product_star: {
                            tag: 'etrusted-product-review-list-widget-product-star-extension',
                        },
                    },
                    attributes: {
                        id: {
                            value: 'wdg-92de735d-6d02-4ccf-941c-9d72d3c0cc46',
                            attributeName: 'data-etrusted-widget-id',
                        },
                        productIdentifier: {
                            attributeName: 'data-mpn',
                        },
                    },
                },
                {
                    tag: 'etrusted-widget',
                    applicationType: 'product_star',
                    widgetId: 'wdg-deleted-in-api-2',
                    widgetLocation: {
                        id: '21d3d933eb93',
                        name: 'Footer',
                    },
                    attributes: {
                        id: {
                            value: 'wdg-deleted-in-api-2',
                            attributeName: 'data-etrusted-widget-id',
                        },
                        productIdentifier: {
                            attributeName: 'data-sku',
                        },
                    },
                },
                {
                    tag: 'etrusted-widget',
                    applicationType: 'trusted_stars_service',
                    widgetId: 'wdg-52c26016-c3e7-42d5-972b-9851a28809ea',
                    widgetLocation: { id: '21d3d933eb93', name: 'Home Page' },
                    attributes: {
                        id: {
                            value: 'wdg-52c26016-c3e7-42d5-972b-9851a28809ea',
                            attributeName: 'data-etrusted-widget-id',
                        },
                        productIdentifier: {
                            attributeName: 'data-gtin',
                        },
                    },
                },
                {
                    tag: 'etrusted-widget',
                    applicationType: 'review_carousel_service',
                    widgetId: 'wdg-b893f1f2-c178-4fd8-b067-a66613bc3329',
                    widgetLocation: { id: '21d3d933eb93', name: 'Home Page' },
                    attributes: {
                        id: {
                            value: 'wdg-b893f1f2-c178-4fd8-b067-a66613bc3329',
                            attributeName: 'data-etrusted-widget-id',
                        },
                    },
                },
            ],
        },
    ],
}

let reviewChannel:any = null

class BaseLayer {
    private static _instance: BaseLayer;

    private settings: Settings|null;
    
    private eventsLib: EventsLib;

    private params: Params;

    private constructor() {
        this.settings = null;
        this.eventsLib = (window as any).eventsLib as EventsLib;
        this.params = (window as any).ts_easy_integration_params as Params;
        
        this.registerEvents();
    }

    private sendingNotification(event: any, status: string, type = 'save') {
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
        await this.getCredentials().then(credentials => {
            this.eventsLib.dispatchAction({
                action: this.eventsLib.EVENTS.SET_CREDENTIALS_PROVIDED,
                payload: credentials,
            })
        });
    }

    private async saveCredentialsCallback(event: { payload: { clientId: string; clientSecret: string; }; }) {
        try {
            const settings = await this.getSettings().then(settings => {
                settings.client_id = event.payload.clientId;
                settings.client_secret = event.payload.clientSecret;

                return settings;
            }).then(settings => {
                return this.updateSettings(settings);
            });

            this.sendingNotification(this.eventsLib.EVENTS.SAVE_CREDENTIALS, 'success');
        } catch(e) {
            this.sendingNotification(this.eventsLib.EVENTS.SAVE_CREDENTIALS, 'error');
        }
    }

    private getSalesChannelsCallback() {
        this.eventsLib.dispatchAction({
            action: this.eventsLib.EVENTS.SET_SALES_CHANNELS_PROVIDED,
            payload: this.params.sale_channels,
        });
    }

    private async getMappedChannelsCallback() {
        await this.getChannels().then(channels => {
            this.eventsLib.dispatchAction({
                action: this.eventsLib.EVENTS.SET_MAPPED_CHANNELS,
                payload: channels,
            })
        });
    }

    private async saveMappedChannelsCallback(event: { payload: Channel[]; }) {
        try {
            const settings = await this.getSettings().then(settings => {
                settings.channels = event.payload;

                return settings;
            }).then(settings => {
                return this.updateSettings(settings);
            });

            await this.getMappedChannelsCallback().then( () => {
                this.sendingNotification(this.eventsLib.EVENTS.SET_MAPPED_CHANNELS, 'success');
            });
        } catch(e) {
            this.sendingNotification(this.eventsLib.EVENTS.SET_MAPPED_CHANNELS, 'error');
        }
    }

    private async disconnectCallback() {
        try {
            await this.disconnect().then(result => {
                this.eventsLib.dispatchAction({ action: this.eventsLib.EVENTS.SET_DISCONNECTED, payload: null });
                this.settings = null;
            });
        } catch(e) {
            this.sendingNotification(this.eventsLib.EVENTS.SET_DISCONNECTED, 'error');
        }
    }

    private async registerEvents() {
        this.eventsLib.registerEvents({
            [this.eventsLib.EVENTS.GET_INFORMATION_OF_SYSTEAM]: this.getInformationOfSystemCallback.bind( this ),
            [this.eventsLib.EVENTS.GET_LOCALE]: this.getLocaleCallback.bind( this ),
            [this.eventsLib.EVENTS.SAVE_CREDENTIALS]: this.saveCredentialsCallback.bind( this ),
            [this.eventsLib.EVENTS.GET_CREDENTIALS_PROVIDED]: this.getCredentialsCallback.bind( this ),
            [this.eventsLib.EVENTS.GET_SALES_CHANNELS_PROVIDED]: this.getSalesChannelsCallback.bind( this ),
            [this.eventsLib.EVENTS.GET_MAPPED_CHANNELS]: this.getMappedChannelsCallback.bind( this ),
            [this.eventsLib.EVENTS.SAVE_MAPPED_CHANNEL]: this.saveMappedChannelsCallback.bind( this ),
            [this.eventsLib.EVENTS.DISCONNECTED]: this.disconnectCallback.bind( this ),

            [this.eventsLib.EVENTS.GET_TRUSTBADGE_CONFIGURATION_PROVIDED]: (event: { payload: { id: string; }; }) => {
                console.log('DEMO:GET_TRUSTBADGE_CONFIGURATION_PROVIDED')
                setTimeout(() => {
                    this.eventsLib.dispatchAction({
                        action: this.eventsLib.EVENTS.SET_TRUSTBADGE_CONFIGURATION_PROVIDED,
                        payload: getTrustedBadge(event.payload.id),
                    })
                }, 3000)
            },
            [this.eventsLib.EVENTS.SAVE_TRUSTBADGE_CONFIGURATION]: (event: { payload: any; }) => {
                console.log('DEMO:SAVE_TRUSTBADGE_CONFIGURATION_BaseLayer', event.payload)
                try {
                    setTimeout(() => {
                        this.eventsLib.dispatchAction({
                            action: this.eventsLib.EVENTS.SET_TRUSTBADGE_CONFIGURATION_PROVIDED,
                            payload: event.payload,
                        })
                        this.sendingNotification(
                            this.eventsLib.EVENTS.SAVE_TRUSTBADGE_CONFIGURATION,
                            'TRUSTBADGE CONFIGURATION SAVED',
                            'success'
                        )
                    }, 3000)
                } catch (error) {
                    setTimeout(() => {
                        this.sendingNotification(
                            this.eventsLib.EVENTS.SAVE_TRUSTBADGE_CONFIGURATION,
                            'TRUSTBADGE CONFIGURATION NOT SAVED',
                            'error'
                        )
                    }, 400)
                }
            },

            [this.eventsLib.EVENTS.GET_LOCATION_FOR_WIDGET]: () => {
                this.eventsLib.dispatchAction({
                    action: this.eventsLib.EVENTS.SET_LOCATION_FOR_WIDGET,
                    payload: widgetLocation,
                })
            },
            [this.eventsLib.EVENTS.GET_WIDGET_PROVIDED]: (event: { payload: { id: string; }; }) => {
                setTimeout(() => {
                    this.eventsLib.dispatchAction({
                        action: this.eventsLib.EVENTS.SET_WIDGET_PROVIDED,
                        payload:
                            event.payload.id === 'chl-7e52920a-2722-4881-9908-ecec98c716e4'
                                ? dataWidgets
                                : { children: [] },
                    })
                }, 3000)
            },
            [this.eventsLib.EVENTS.SAVE_WIDGET_CHANGES]: (event: { payload: { children: { tag: string; attributes: { src: { value: string; attributeName: string; }; async: { attributeName: string; }; defer: { attributeName: string; }; }; children: ({ tag: string; applicationType: string; widgetId: string; widgetLocation: { id: string; name: string; }; attributes: { id: { value: string; attributeName: string; }; productIdentifier: { attributeName: string; }; }; extensions?: undefined; } | { tag: string; applicationType: string; widgetId: string; widgetLocation: { id: string; name: string; }; extensions: { product_star: { tag: string; }; }; attributes: { id: { value: string; attributeName: string; }; productIdentifier: { attributeName: string; }; }; } | { tag: string; applicationType: string; widgetId: string; widgetLocation: { id: string; name: string; }; attributes: { id: { value: string; attributeName: string; }; productIdentifier?: undefined; }; extensions?: undefined; })[]; }[]; }; }) => {
                try {
                    console.log('DEMO:SAVE_WIDGET_CHANGES', event.payload)
                    dataWidgets = event.payload
                    setTimeout(() => {
                        this.eventsLib.dispatchAction({
                            action: this.eventsLib.EVENTS.SET_WIDGET_PROVIDED,
                            payload: dataWidgets,
                        })
                        this.sendingNotification(this.eventsLib.EVENTS.SAVE_WIDGET_CHANGES, 'WIDGET SAVED', 'success')
                    }, 3000)
                } catch (error) {
                    setTimeout(() => {
                        this.sendingNotification(this.eventsLib.EVENTS.SAVE_WIDGET_CHANGES, 'WIDGET NOT SAVED', 'error')
                    }, 400)
                }
            },

            [this.eventsLib.EVENTS.GET_PRODUCT_REVIEW_FOR_CHANNEL]: (event: { payload: any; }) => {
                console.log('DEMO:GET_PRODUCT_REVIEW_FOR_CHANNEL', event.payload)
                setTimeout(() => {
                    this.eventsLib.dispatchAction({
                        action: this.eventsLib.EVENTS.SET_PRODUCT_REVIEW_FOR_CHANNEL,
                        payload: null,
                    })
                }, 3000)
            },

            [this.eventsLib.EVENTS.ACTIVATE_PRODUCT_REVIEW_FOR_CHANNEL]: (event: { payload: null; }) => {
                try {
                    console.log('DEMO:ACTIVATE_PRODUCT_REVIEW_FOR_CHANNEL', event.payload)
                    reviewChannel = event.payload
                    setTimeout(() => {
                        this.eventsLib.dispatchAction({
                            action: this.eventsLib.EVENTS.SET_PRODUCT_REVIEW_FOR_CHANNEL,
                            payload: reviewChannel,
                        })
                        this.sendingNotification(
                            this.eventsLib.EVENTS.ACTIVATE_PRODUCT_REVIEW_FOR_CHANNEL,
                            'PRODUCT REVIEW FOR CHANNEL ACTIVATED',
                            'success'
                        )
                    }, 3000)
                } catch (error) {
                    setTimeout(() => {
                        this.sendingNotification(
                            this.eventsLib.EVENTS.ACTIVATE_PRODUCT_REVIEW_FOR_CHANNEL,
                            'PRODUCT REVIEW FOR CHANNEL NOT ACTIVATED',
                            'error'
                        )
                    }, 400)
                }
            },
            [this.eventsLib.EVENTS.DEACTIVATE_PRODUCT_REVIEW_FOR_CHANNEL]: (event: { payload: any; }) => {
                try {
                    console.log('DEMO:DEACTIVATE_PRODUCT_REVIEW_FOR_CHANNEL', event.payload)

                    setTimeout(() => {
                        this.eventsLib.dispatchAction({
                            action: this.eventsLib.EVENTS.SET_PRODUCT_REVIEW_FOR_CHANNEL,
                            payload: reviewChannel,
                        })
                        this.sendingNotification(
                            this.eventsLib.EVENTS.DEACTIVATE_PRODUCT_REVIEW_FOR_CHANNEL,
                            'PRODUCT REVIEW FOR CHANNEL DEACTIVATED',
                            'success'
                        )
                    }, 3000)
                } catch (error) {
                    setTimeout(() => {
                        this.sendingNotification(
                            this.eventsLib.EVENTS.ACTIVATE_PRODUCT_REVIEW_FOR_CHANNEL,
                            'PRODUCT REVIEW FOR CHANNEL NOT DEACTIVATED',
                            'error'
                        )
                    }, 400)
                }
            },

            [this.eventsLib.EVENTS.GET_USE_ESTIMATED_DELIVERY_DATE_FOR_CHANNEL]: (event: { payload: { id: any; }; }) => {
                console.log('DEMO:GET_USE_ESTIMATED_DELIVERY_DATE_FOR_CHANNEL')
                setTimeout(() => {
                    this.eventsLib.dispatchAction({
                        action: this.eventsLib.EVENTS.SET_USE_ESTIMATED_DELIVERY_DATE_FOR_CHANNEL,
                        payload: { id: event.payload.id, isUseDateToSendReviewInvites: true },
                    })
                }, 3000)
            },
            [this.eventsLib.EVENTS.SAVE_USE_ESTIMATED_DELIVERY_DATE_FOR_CHANNEL]: (event: { payload: any; }) => {
                try {
                    console.log('DEMO:SAVE_USE_ESTIMATED_DELIVERY_DATE_FOR_CHANNEL', event.payload)
                    setTimeout(() => {
                        this.eventsLib.dispatchAction({
                            action: this.eventsLib.EVENTS.SET_USE_ESTIMATED_DELIVERY_DATE_FOR_CHANNEL,
                            payload: event.payload,
                        })
                        this.sendingNotification(
                            this.eventsLib.EVENTS.SAVE_USE_ESTIMATED_DELIVERY_DATE_FOR_CHANNEL,
                            'USE ESTIMATED DELIVERY DATE FOR CHANNEL SAVED',
                            'success'
                        )
                    }, 3000)
                } catch (error) {
                    setTimeout(() => {
                        this.sendingNotification(
                            this.eventsLib.EVENTS.ACTIVATE_PRODUCT_REVIEW_FOR_CHANNEL,
                            'USE ESTIMATED DELIVERY DATE FOR CHANNEL NOT SAVED',
                            'error'
                        )
                    }, 400)
                }
            },
            [this.eventsLib.EVENTS.EXPORT_PREVIOUS_ORDER]: (event: { payload: any; }) => {
                console.log('DEMO:EXPORT_PREVIOUS_ORDER', event.payload)
                setTimeout(() => {
                    const link = document.createElement('a')
                    link.download = `./Brand_Logo_Trusted_Shops.svg`
                    const blob = new Blob(['Hello, world!'], { type: 'text/plain' })
                    link.href = URL.createObjectURL(blob)
                    link.click()
                    URL.revokeObjectURL(link.href)
                    this.eventsLib.dispatchAction({
                        action: this.eventsLib.EVENTS.SET_EXPORT_PREVIOUS_ORDER,
                        payload: event.payload,
                    })
                }, 3000)
            },
            [this.eventsLib.EVENTS.ERROR]: (error: any) => console.log('DEMO:eventError', error),
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
        }

        if ( ! this.settings ) {
            throw new TypeError("Error message");
        }

        return this.settings;
    }

    private updateSettingsData(result:Settings) {
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

    private async updateSettings( settings:Settings ): Promise<Settings> {
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

            return this.updateSettingsData(result.settings);
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
            return await this.getSettings().then(settings => {
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
            return await this.getSettings().then(settings => {
                return settings.channels;
            });
        } catch(e) {
            return [];
        }
    }

    public static get Instance() {
        return this._instance || (this._instance = new this());
    }
}

const baseLayer = BaseLayer.Instance;