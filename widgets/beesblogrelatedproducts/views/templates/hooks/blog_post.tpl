{**
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
 *}

<section>
    <h3 class="page-product-heading"><span>{l s='Related products' mod='beesblogrelatedproducts'}</span></h3>
    <div class="row">
        {foreach $related_products as $product}
            <article>
                <div class="col-xs-12 col-sm-4 col-md-4">
                    <div class="beesblog-related-products-content">
                        <h3 class="beesblog-related-products-header">
                            <a class="beesblog-related-products-title" href="{$product.link|escape:'html':'UTF-8'}" title="{$product.name|escape:'htmlall':'UTF-8'}">
                                <img src="{$product.image|escape:'html':'UTF-8'}" class="img-responsive" title="{$product.name|escape:'htmlall':'UTF-8'}" />
                                <p class="beesblog-related-products-product">
                                    {$product.name|escape:'htmlall':'UTF-8'}
                                </p>
                            </a>
                        </h3>
                    </div>
                </div>
            </article>
        {/foreach}
    </div>
</section>
