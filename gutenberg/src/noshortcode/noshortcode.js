import './editor.scss';
import { __ } from './../i18n/i18n.js';
import { RichText } from "@wordpress/block-editor";
const { registerBlockType } = wp.blocks;

registerBlockType('argon/noshortcode', {
    title: __('防短代码解析 (NoShortcode)'),
    icon: 'editor-code',
    category: 'argon',
    attributes: {
        content: { type: 'string', default: '' }
    },
    edit: (props) => {
        return (
            <div style={{padding: '10px', background: '#f5f5f5', border: '1px dashed #ccc'}}>
                <RichText tagName="div" placeholder={__("在此输入的不包含格式的文本将不会被解析短代码")} value={props.attributes.content} onChange={(val) => props.setAttributes({ content: val })} />
            </div>
        );
    },
    save: (props) => {
        return (
            <div>[noshortcode]<span dangerouslySetInnerHTML={{ __html: props.attributes.content }} />[/noshortcode]</div>
        );
    }
});
