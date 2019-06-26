/**
 * Copyright (C) 2019 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <modules@thirtybees.com>
 * @copyright 2019 thirty bees
 * @license   Academic Free License (AFL 3.0)
 */


(function () {
    var relatedProducts = {
        initAutoComplete: function () {
            $('#product_autocomplete_input')
                .autocomplete(window.currentIndex, {
                    minChars: 1,
                    autoFill: true,
                    max: 20,
                    matchContains: true,
                    mustMatch: false,
                    scroll: false,
                    cacheLength: 0,
                    dataType: 'json',
                    parse: function (data) {
                        return data ? data.map(function (item) {
                            return {
                                data: item,
                                value: item.name + ' - ' + item.reference
                            }
                        }) : [];
                    },
                    formatItem: relatedProducts.formatItem,
                })
                .result(function (e, data) {
                    if (data) {
                        relatedProducts.addProduct(data);
                    }
                });
            relatedProducts.updateAutoComplete();
        },
        getAutoCompleteParams: function() {
            return {
                ajax: true,
                token: window.token,
                action: 'searchProducts',
                excludeIds: relatedProducts.getProductIds()
            }
        },
        updateAutoComplete: function() {
            $('#product_autocomplete_input').setOptions({
                extraParams: relatedProducts.getAutoCompleteParams()
            });
            $('#products').val(relatedProducts.getProductIds().join('|'));
        },
        getProductIds: function() {
            var ids = [];
            $('[data-product-id]').each(function(i, e) {
                ids.push(parseInt($(e).data('productId'), 10));
            });
            return ids;
        },
        formatItem: function (product) {
            if (product) {
                return product.name + (product.reference ? ' (ref: ' + product.reference + ')' : '');
            }
            return '';
        },
        addProduct: function (product) {
            var $element = $(
                "<div class=\"form-control-static\" data-product-id=\"" + product.id_product + "\">" +
                "  <button type=\"button\" class=\"btn btn-default del-product\">\n" +
                "    <i class=\"icon-remove text-danger\"></i>\n" +
                "  </button>\n" +
                "  " + relatedProducts.formatItem(product) + "\n" +
                "</div>"
            );
            $('#div-products').append($element);
            $($element).on('click', relatedProducts.removeProduct);
            relatedProducts.updateAutoComplete();
        },

        removeProduct: function (e) {
            var node = e.target;
            while (node && node.parentElement !== node) {
                var id = $(node).data('product-id');
                if (id) {
                    $(node).remove();
                    relatedProducts.updateAutoComplete();
                    return;
                }
                node = node.parentElement;
            }
        },
    };

    $(function () {
        relatedProducts.initAutoComplete();
        $('.del-product').on('click', relatedProducts.removeProduct);
    });
})();
