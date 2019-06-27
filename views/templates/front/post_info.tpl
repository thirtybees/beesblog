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

<div class="beesblog-post-info">
    {if isset($showAuthor) && $showAuthor}
        <i class="icon icon-user"></i>
        &nbsp;
        {l s='Posted by' mod='beesblog'}
        <span>
            {if $authorStyle}
                {$post->employee->firstname|escape:'htmlall':'UTF-8'} {$post->employee->lastname|escape:'htmlall':'UTF-8'}
            {else}
                {$post->employee->lastname|escape:'htmlall':'UTF-8'} {$post->employee->firstname|escape:'htmlall':'UTF-8'}
            {/if}
        </span>
    {/if}
    {if isset($showDate) && $showDate}
        <i class="icon icon-calendar"></i>
        &nbsp;
        <time>
            {$post->published|date_format}
        </time
    {/if}

    <i class="icon icon-object-group"></i>&nbsp;
    <span>
        <a href="{BeesBlog::GetBeesBlogLink('beesblog_category', ['cat_rewrite' => $post->category->link_rewrite])}">
            {$post->category->title|escape:'htmlall':'UTF-8'}
        </a>
    </span>
    {if isset($showComments) && $showComments && $post->comments_enabled}
        <span class="beesblog-comment-counter">
            <i class="icon icon-comments"></i>&nbsp;
            <a title="{l s='0 Comments' mod='beesblog'}"
               href="{$postPath|escape:'htmlall':'UTF-8'}#disqus_thread"
               data-disqus-identifier="{$post->id|intval}">
                {l s='0 Comments' mod='beesblog'}
            </a>
        </span>
    {/if}
    {if isset($showViewed) && $showViewed}
        <i class="icon icon-eye-open"></i>
        {l s=' views' mod='beesblog'} ({$post->viewed|escape:'htmlall':'UTF-8'})
    {/if}
</div>
