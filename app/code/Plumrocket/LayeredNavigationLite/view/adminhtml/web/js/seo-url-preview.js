/**
 * @package     Plumrocket_Amp
 * @copyright   Copyright (c) 2020 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

requirejs([
    'underscore',
    'domReady!'
], function (_) {
    'use strict';

    class SeoUrlPreview
    {
        /**
         * @var {{}}
         */
        variants;

        /**
         * @var {HTMLSelectElement}
         */
        previewSpan;

        /**
         * @var {HTMLSelectElement}
         */
        insertFiltersIn;

        /**
         * @var {HTMLInputElement}
         */
        valueType;

        /**
         * @param {String} previewId
         * @param {String} insertFiltersInId
         * @param {String} valueTypeId
         * @param {{}} variants
         */
        constructor({previewId, insertFiltersInId, valueTypeId, variants})
        {
            this.variants = variants;
            this.previewSpan = document.getElementById(previewId);
            this.valueType = document.getElementById(valueTypeId);
            this.insertFiltersIn = document.getElementById(insertFiltersInId);
            if (! this.previewSpan || ! this.insertFiltersIn) {
                return;
            }
            this.initListeners();
            this.updatePreview();
        }

        /**
         * Init event listeners for all elements that might change url
         */
        initListeners()
        {
            this.insertFiltersIn.addEventListener('change', this.updatePreview.bind(this));
            if (this.valueType) {
                this.valueType.addEventListener('change', this.updatePreview.bind(this));
            }
        }

        /**
         * Build and update amp url preview
         */
        updatePreview()
        {
            let key = '';

            if (1 === Number(this.insertFiltersIn.value)) {
                key += 'get|'
            } else {
                key += 'path|'
            }
            if (this.valueType && 1 === Number(this.valueType.value)) {
                key += 'label'
            } else {
                key += 'id'
            }
            this.previewSpan.innerHTML = this.variants[key];
        }
    }

    new SeoUrlPreview({
        previewId: 'prproductfilter_seo_url_url_preview',
        insertFiltersInId: 'prproductfilter_seo_url_insert_in',
        valueTypeId: 'prproductfilter_seo_url_seo_friendly_url',
        variants: {
            'path|label': 'https://example.com/jackets/<strong>color-black</strong>.html',
            'path|id': 'https://example.com/jackets/<strong>color-35</strong>.html',
            'get|label': 'https://example.com/jackets.html?<strong>color=black</strong>',
            'get|id': 'https://example.com/jackets.html?<strong>color=35</strong>',
        },
    });
});
