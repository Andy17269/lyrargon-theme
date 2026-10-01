import './editor.scss';
import { __ } from './../i18n/i18n.js';
import ServerSideRender from '@wordpress/server-side-render';
const { registerBlockType } = wp.blocks;

registerBlockType('argon/sfriendlinks', {
    title: __('简单友情链接'),
    icon: 'admin-links',
    category: 'argon',
    supports: {
        inserter: false
    },
    attributes: {},
    edit: (props) => {
        return <ServerSideRender block="argon/sfriendlinks" attributes={props.attributes} />;
    },
    save: () => { return null; }
});
