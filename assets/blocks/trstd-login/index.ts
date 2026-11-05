/**
 * Editor registration of the #trstd login block. The block is rendered server-side (dynamic block),
 * the editor shows a static, layout-only preview using the dimensions of the default toggle
 * (34px * 38 / 24 + 2px rail x 34px + 2px rail) as the element itself is filled by Trusted Shops at runtime.
 */
import metadata from './block.json';

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const wp = ( window as any ).wp;
const { registerBlockType } = wp.blocks;
const { createElement: el } = wp.element;
// useBlockProps is available since WP 5.6 - fallback to plain props for older editors (block wrapper added by the editor).
// eslint-disable-next-line @typescript-eslint/no-explicit-any
const useBlockProps = wp.blockEditor && wp.blockEditor.useBlockProps ? wp.blockEditor.useBlockProps : ( props: any ) => props;
const { __ } = wp.i18n;

const height = 'var(--ts-trstd-login-height, 34px)';

registerBlockType( metadata.name, {
    edit: () => {
        const blockProps = useBlockProps( {
            className: 'ts-trstd-login-hd-wrapper',
            title: __( '#trstd login - displayed in your storefront as soon as it is enabled in your Trusted Shops settings.', 'trusted-shops-easy-integration-for-woocommerce' ),
            style: {
                display: 'inline-flex',
                alignItems: 'center',
                justifyContent: 'center',
                flex: '0 0 auto',
            },
        } );

        return el(
            'div',
            blockProps,
            el(
                'div',
                {
                    className: 'ts-trstd-login-hd-wrapper__inner',
                    style: {
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        boxSizing: 'content-box',
                        padding: '0 var(--ts-trstd-login-padding-x, .5em)',
                    },
                },
                el(
                'span',
                {
                    'aria-label': '#trstd login',
                    style: {
                        boxSizing: 'border-box',
                        display: 'inline-flex',
                        alignItems: 'center',
                        width: `calc(${ height } * 38 / 24 + 2px)`,
                        height: `calc(${ height } + 2px)`,
                        border: '1px solid currentColor',
                        borderRadius: '999px',
                        opacity: 0.6,
                    },
                },
                el( 'span', {
                    style: {
                        boxSizing: 'border-box',
                        width: height,
                        height,
                        border: '1px solid currentColor',
                        borderRadius: '50%',
                        transform: 'scale(0.8)',
                    },
                } )
            )
            )
        );
    },
    save: () => null,
} );
