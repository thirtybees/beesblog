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

{extends file="helpers/form/form.tpl"}

{block name="input"}
    {if $input.type === 'product-selector'}
        <div class="row">
            <div class="col-lg-9">
                <input type="hidden" name="products" id="products" value="{foreach from=$input.products item=product}{$product.id_product}-{/foreach}" />
                <div id="ajax_choose_product">
                    <div class="input-group">
                        <input type="text" id="product_autocomplete_input" name="product_autocomplete_input" />
                        <span class="input-group-addon"><i class="icon-search"></i></span>
                    </div>
                </div>

                <div id="div-products">
                    {foreach from=$input.products item=product}
                        <div class="form-control-static" data-product-id="{$product.id_product}">
                            <button type="button" class="btn btn-default del-product">
                                <i class="icon-remove text-danger"></i>
                            </button>
                            {$product.name|escape:'html':'UTF-8'}{if !empty($product.reference)}&nbsp;{l s='(ref: %s)' sprintf=$product.reference}{/if}
                        </div>
                    {/foreach}
                </div>
            </div>
        </div>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}

