/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'underscore',
    'matchMedia',
    'Magento_PageBuilder/js/utils/breakpoints',
    'Magento_PageBuilder/js/events',
    'slick'
], function ($, _, mediaCheck, breakpointsUtils, events) {
    'use strict';

    /**
     * Initialize slider.
     *
     * @param {jQuery} $carouselElement
     * @param {Object} config
     */
    function buildSlick($carouselElement, config) {
        /**
         * Prevent each slick slider from being initialized more than once which could throw an error.
         */
        if ($carouselElement.hasClass('slick-initialized')) {
            $carouselElement.slick('unslick');
        }

        config.slidesToScroll = config.slideAll ? config.slidesToShow : 1;
        $carouselElement.slick(config);
    }

    return function (config, element) {
        var $element = $(element),
            $carouselElement = $($element.children()),
            slickConfig = {
                slideAll: $element.data('slide-all'),
                autoplay: $element.data('autoplay'),
                autoplaySpeed: $element.data('autoplay-speed') || 0,
                infinite: $element.data('infinite-loop'),
                arrows: $element.data('show-arrows'),
                dots: $element.data('show-dots'),
                centerMode: $element.data('center-mode')
            };

        _.each(config.breakpoints, function (breakpoint) {
            mediaCheck({
                media: breakpointsUtils.buildMedia(breakpoint.conditions),

                /** @inheritdoc */
                entry: function () {
                    slickConfig.slidesToShow = parseFloat(breakpoint.options.products.slidesToShow);
                    buildSlick($carouselElement, slickConfig);
                }
            });
        });

        // Redraw slide after content type gets redrawn
        events.on('contentType:redrawAfter', function (args) {
            if ($carouselElement.closest(args.element).length) {
                $carouselElement.slick('setPosition');
            }
        });
    };
});