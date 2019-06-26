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

{if !empty($beesblogRecentPostsPosts)}
    <section>
        <div id="beesblog_column" class="block">
            <h4 class="title_block">
                <a href="{$beesblogRecentPostsBlogUrl|escape:'htmlall':'UTF-8'}"
                   title="{l s='Recent posts' mod='beesblogrecentposts'}">{l s='Recent posts' mod='beesblogrecentposts'}</a>
            </h4>
            <div class="block_content">
                <ul>
                    {foreach $beesblogRecentPostsPosts as $post}
                        <li class="clearfix">
                            <article>
                                <div class="beesblogrecentposts-content">
                                    <h5>
                                        <a class="beesblogrecentposts-title"
                                           href="{$post->link|escape:'htmlall':'UTF-8'}"
                                           title="{$post->title|escape:'htmlall':'UTF-8'}">
                                           {assign var=imagePath value=Media::getMediaPath(BeesBlog::getPostImagePath($post->id))}
                                             {if ($imagePath)}
                                               <img class="img-responsive" src="{$imagePath|escape:'htmlall':'UTF-8'}" title="{$post->title|escape:'htmlall':'UTF-8'}" />
                                             {/if}
                                            {$post->title|truncate:'35'|escape:'htmlall':'UTF-8'}
                                        </a>
                                    </h5>
                            <span>
                                <i class="icon icon-calendar"></i> {$post->published|date_format}
                                <i class="icon icon-eye"></i> {$post->viewed|intval}
                            </span>
                                </div>
                        </li>
                        </article>
                    {/foreach}
                </ul>
                <br/>
                <div>
                    <a href="{$beesblogRecentPostsBlogUrl|escape:'htmlall':'UTF-8'}"
                       title="{l s='Bees blog' mod='beesblogrecentposts'}"
                       class="btn btn-primary"><span>{l s='All posts' mod='beesblogrecentposts'} <i
                                    class="icon icon-chevron-right"></i></span></a>
                </div>
            </div>
        </div>
    </section>
{/if}
