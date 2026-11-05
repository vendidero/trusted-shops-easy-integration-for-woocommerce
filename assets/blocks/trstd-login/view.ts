/**
 * Interactivity API store of the #trstd login block.
 *
 * The <trstd-login> element itself is rendered and controlled by the Trusted Shops integration script.
 * This store does not touch the element - it only flags the wrapper (is-ready class) as soon as the custom element
 * has been defined by the integration script, e.g. to allow themes to adjust their layout.
 */
import { store, getContext } from '@wordpress/interactivity';

interface TrstdLoginContext {
    isReady: boolean;
    tag: string;
}

store( 'ts-easy-integration/trstd-login', {
    callbacks: {
        init() {
            const context = getContext<TrstdLoginContext>();

            if ( ! context.tag || ! window.customElements ) {
                return;
            }

            window.customElements.whenDefined( context.tag ).then( () => {
                context.isReady = true;
            } ).catch( () => undefined );
        },
    },
} );
