/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'Magento_PageBuilder/js/content-type/slider/appearance/default/widget',
    'jquery'
], function (sliderWidgetInitializer, $) {
    'use strict';

    describe('Magento_PageBuilder/js/content-type/slider/appearance/default/widget', function () {
        it('Should call unslick if element has class slick-initialized', function () {
            var el = document.createElement('div');

            spyOn($.fn, 'slick');

            el.classList.add('slick-initialized');

            sliderWidgetInitializer(undefined, el);

            expect($.fn.slick).toHaveBeenCalled();
        });

        it('Should call slick on element based on its data', function () {
            var el = document.createElement('div');

            spyOn($.fn, 'slick');

            el.setAttribute('data-autoplay', 1);
            el.setAttribute('data-autoplay-speed', 500);
            el.setAttribute('data-fade', 1);
            el.setAttribute('data-is-infinite', 1);
            el.setAttribute('data-show-arrows', 1);
            el.setAttribute('data-show-dots', 1);

            sliderWidgetInitializer(undefined, el);

            expect($.fn.slick).toHaveBeenCalledWith({
                autoplay: true,
                autoplaySpeed: 500,
                fade: true,
                infinite: true,
                arrows: true,
                dots: true
            });
        });
    });
});
