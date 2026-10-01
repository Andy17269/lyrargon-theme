import './editor.scss';
import { __ } from './../i18n/i18n.js';
import { InspectorControls } from "@wordpress/block-editor";
import { PanelBody, PanelRow, TextControl, ToggleControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
const { registerBlockType } = wp.blocks;

registerBlockType('argon/video', {
    title: __('视频 (MP4/URL)'),
    icon: 'video-alt3',
    category: 'argon',
    attributes: {
        url: { type: 'string', default: '' },
        width: { type: 'string', default: '' },
        height: { type: 'string', default: '' },
        autoplay: { type: 'string', default: 'false' }
    },
    edit: (props) => {
        return (
            <div>
                {props.attributes.url ? (
                    <ServerSideRender block="argon/video" attributes={props.attributes} />
                ) : (
                    <div style={{padding: '20px', background: '#eee', textAlign: 'center'}}>{__('视频区块：请在右侧设置中输入视频 URL')}</div>
                )}
                <InspectorControls key="setting">
                    <PanelBody title={__("区块设置")} initialOpen={true}>
                        <PanelRow>
                            <TextControl label={__("视频 URL (MP4 等)")} value={props.attributes.url} onChange={(val) => props.setAttributes({ url: val })} />
                        </PanelRow>
                        <PanelRow>
                            <TextControl label={__("宽度 (如 100%)")} value={props.attributes.width} onChange={(val) => props.setAttributes({ width: val })} />
                        </PanelRow>
                        <PanelRow>
                            <TextControl label={__("高度")} value={props.attributes.height} onChange={(val) => props.setAttributes({ height: val })} />
                        </PanelRow>
                        <PanelRow>
                            <ToggleControl label={__("自动播放")} checked={props.attributes.autoplay === 'true'} onChange={(val) => props.setAttributes({ autoplay: val ? 'true' : 'false' })} />
                        </PanelRow>
                    </PanelBody>
                </InspectorControls>
            </div>
        );
    },
    save: () => { return null; }
});
