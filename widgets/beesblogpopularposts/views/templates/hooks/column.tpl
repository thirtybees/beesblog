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

{if !empty($beesblogPopularPostsPosts)}
    <section>
        <div id="beesblog_column" class="block">
            <h4 class="title_block">
                <a href="{$beesblogPopularPostsBlogUrl|escape:'htmlall':'UTF-8'}"
                   title="{l s='Popular posts' mod='beesblogpopularposts'}">{l s='Popular posts' mod='beesblogpopularposts'}</a>
            </h4>
            <div class="block_content">
                <ul>
                    {foreach $beesblogPopularPostsPosts as $post}
                        <li class="clearfix">
                            <article>
                                <div class="beesblogpopularposts-content">
                                    <h5>
                                        <a class="beesblogpopularposts-title"
                                           href="{$post->link|escape:'htmlall':'UTF-8'}"
                                           title="{$post->title|escape:'htmlall':'UTF-8'}">
                                            {$post->title|truncate:'20'|escape:'htmlall':'UTF-8'}
                                        </a>
                                    </h5>
                            <span>
                                <i class="icon icon-eye"></i> {$post->viewed|intval}
                                <i class="icon icon-calendar"></i> {$post->published|date_format}
                            </span>
                                </div>
                            </article>
                        </li>
                    {/foreach}
                </ul>
                <br/>
                <div>
                    <a href="{$beesblogPopularPostsBlogUrl|escape:'htmlall':'UTF-8'}"
                       title="{l s='Bees blog' mod='beesblogpopularposts'}"
                       class="btn btn-primary"><span>{l s='All posts' mod='beesblogpopularposts'} <i
                                    class="icon icon-chevron-right"></i></span></a>
                </div>
            </div>
        </div>
    </section>
{/if}
