import './editor.scss';
import { __ } from './../i18n/i18n.js';
import { RichText, InspectorControls } from "@wordpress/block-editor";
import { PanelBody, PanelRow, TextControl, SelectControl } from '@wordpress/components';
const { registerBlockType } = wp.blocks;

registerBlockType('argon/hidden', {
    title: __('隐藏文本 (Spoiler)'),
    icon: 'hidden',
    category: 'argon',
    attributes: {
        content: { type: 'string', default: '' },
        tip: { type: 'string', default: '' },
        type: { type: 'string', default: 'blur' }
    },
    edit: (props) => {
        return (
            <div>
                <span className={"argon-hidden-text argon-hidden-text-" + props.attributes.type}>
                    <RichText tagName="span" placeholder={__("输入隐藏内容")} value={props.attributes.content} onChange={(val) => props.setAttributes({ content: val })} />
                </span>
                <InspectorControls key="setting">
                    <PanelBody title={__("区块设置")} initialOpen={true}>
                        <PanelRow>
                            <TextControl label={__("提示语")} value={props.attributes.tip} onChange={(val) => props.setAttributes({ tip: val })} />
                        </PanelRow>
                        <PanelRow>
                            <SelectControl label={__("遮挡类型")} value={props.attributes.type} options={[{ label: '模糊 (blur)', value: 'blur' }, { label: '背景块 (background)', value: 'background' }]} onChange={(val) => props.setAttributes({ type: val })} />
                        </PanelRow>
                    </PanelBody>
                </InspectorControls>
            </div>
        );
    },
    save: (props) => {
        return (
            <span className={"argon-hidden-text argon-hidden-text-" + props.attributes.type} title={props.attributes.tip}>
                <span dangerouslySetInnerHTML={{ __html: props.attributes.content }} />
            </span>
        );
    }
});
