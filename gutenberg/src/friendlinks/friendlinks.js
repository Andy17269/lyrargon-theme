import './editor.scss';
import { __ } from './../i18n/i18n.js';
import { InspectorControls } from "@wordpress/block-editor";
import { PanelBody, PanelRow, SelectControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
const { registerBlockType } = wp.blocks;

registerBlockType('argon/friendlinks', {
    title: __('友情链接'),
    icon: 'admin-links',
    category: 'argon',
    supports: {
        inserter: false
    },
    attributes: {
        sort: { type: 'string', default: 'name' },
        order: { type: 'string', default: 'ASC' },
        style: { type: 'string', default: '1' }
    },
    edit: (props) => {
        return (
            <div>
                <ServerSideRender block="argon/friendlinks" attributes={props.attributes} />
                <InspectorControls key="setting">
                    <PanelBody title={__("区块设置")} initialOpen={true}>
                        <PanelRow>
                            <SelectControl label={__("排序规则")} value={props.attributes.sort} options={[{ label: '名称 (name)', value: 'name' }, { label: 'ID', value: 'id' }, { label: 'URL', value: 'url' }, { label: '评分 (rating)', value: 'rating' }, { label: '随机 (rand)', value: 'rand' }]} onChange={(val) => props.setAttributes({ sort: val })} />
                        </PanelRow>
                        <PanelRow>
                            <SelectControl label={__("升降序")} value={props.attributes.order} options={[{ label: '升序 (ASC)', value: 'ASC' }, { label: '降序 (DESC)', value: 'DESC' }]} onChange={(val) => props.setAttributes({ order: val })} />
                        </PanelRow>
                        <PanelRow>
                            <SelectControl label={__("显示样式")} value={props.attributes.style} options={[{ label: '样式 1 (默认)', value: '1' }, { label: '样式 1 (方形头像)', value: '1-square' }, { label: '样式 2 (居中)', value: '2' }, { label: '样式 2 (大头像)', value: '2-big' }]} onChange={(val) => props.setAttributes({ style: val })} />
                        </PanelRow>
                    </PanelBody>
                </InspectorControls>
            </div>
        );
    },
    save: () => { return null; }
});
