import './editor.scss';
import { __ } from './../i18n/i18n.js';
import ServerSideRender from '@wordpress/server-side-render';
const { registerBlockType } = wp.blocks;

registerBlockType('argon/hide-reading-time', {
    title: __('隐藏阅读时间'),
    icon: 'clock',
    category: 'argon',
    attributes: {},
    edit: (props) => {
        return <div style={{padding: '10px', background: '#ffe4e4', border: '1px solid #f75676', borderRadius: '4px', textAlign: 'center'}}>{__('已添加“隐藏阅读时间”标记')}</div>;
    },
    save: () => { return <div>[hide_reading_time]</div>; }
});
