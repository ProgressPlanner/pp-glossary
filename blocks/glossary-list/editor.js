/**
 * Glossary List Block Editor
 */

(function (wp) {
	const { registerBlockType } = wp.blocks;
	const { __ } = wp.i18n;
	const { createElement: el } = wp.element;

	registerBlockType('pp-glossary/glossary-list', {
		edit: function (props) {
			return el(
				'div',
				{
					className: 'pp-glossary-block-editor',
					style: {
						padding: '2rem',
						backgroundColor: '#f9f9f9',
						border: '1px solid #ddd',
						borderRadius: '4px',
						textAlign: 'center',
					},
				},
				el('span', { className: 'dashicons dashicons-book-alt', style: { fontSize: '48px', color: '#0073aa' } }),
				el('h3', {}, __('Glossary List', 'pp-glossary')),
				el('p', {}, __('The glossary entries will be displayed here on the frontend.', 'pp-glossary'))
			);
		},

		save: function () {
			// Server-side rendered block
			return null;
		},
	});
})(window.wp);
