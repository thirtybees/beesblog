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

{if !empty($beesblogCategoriesCategories)}
    <div id="beesblogcategories_column" class="block">
        <h4 class="title_block">
            <a href="{$beesblogCategoriesBlogUrl|escape:'htmlall':'UTF-8'}" title="{l s='Blog categories' mod='beesblogcategories'}">{l s='Blog categories' mod='beesblogcategories'}</a>
        </h4>
        <div class="block_content">
            <ul>
                {foreach $beesblogCategoriesCategories as $category}
                    <li class="clearfix">
                        <div class="beesblogcategories-content">
                            <h5>
                                <a class="beesblogcategories-title" href="{$category->link|escape:'htmlall':'UTF-8'}" title="{$category->title|escape:'htmlall':'UTF-8'}">
                                    {$category->title|truncate:'20'|escape:'htmlall':'UTF-8'}
                                </a>
                            </h5>
                        </div>
                    </li>
                {/foreach}
            </ul>
            <br />
            <div>
                <a href="{$beesblogCategoriesBlogUrl|escape:'htmlall':'UTF-8'}" title="{l s='Bees blog' mod='beesblogcategories'}" class="btn btn-primary"><span>{l s='Blog' mod='beesblogcategories'} <i class="icon icon-chevron-right"></i></span></a>
            </div>
        </div>
    </div>
{/if}
