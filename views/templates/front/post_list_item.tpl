{*
 * 2017 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 *  @author    thirty bees <modules@thirtybees.com>
 *  @copyright 2017 thirty bees
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}
<div itemtype="#" itemscope="" class="clearfix">
    <div id="beesblog-post-{$post->id|intval}">
        <h4 class="title_block">
            <a title="{$post->title}" href="{BeesBlog::GetBeesBlogLink('beesblog_post', ['blog_rewrite' => $post->link_rewrite])|escape:'htmlall':'UTF-8'}">{$post->title|escape:'htmlall':'UTF-8'}</a>
        </h4>
        <div class="beesblog-post-list-summary">
              <span class="clearfix">
                  {$post->getSummary()|escape:'htmlall':'UTF-8'}&nbsp;
              </span>
            <a title="{$post->title|escape:'htmlall':'UTF-8'}" href="{beesblog::GetBeesBlogLink('beesblog_post', ['blog_rewrite' => $post->link_rewrite])|escape:'htmlall':'UTF-8'}" class="beesblog-read-more-link">
                {l s='Read more' mod='beesblog'} {'>'|escape:'htmlall':'UTF-8'}
            </a>
        </div>
        <div class="beesblog-post-list-bottom">
            {if $showAuthor}
                <i class="icon icon-user"></i>&nbsp;
                {l s='Posted by' mod='beesblog'}
                <span itemprop="author">
                    {if $authorStyle}
                        {$post->firstname|escape:'htmlall':'UTF-8'} {$post->lastname|escape:'htmlall':'UTF-8'}
                    {else}
                        {$post->lastname|escape:'htmlall':'UTF-8'} {$post->firstname|escape:'htmlall':'UTF-8'}
                    {/if}
                </span>
            {/if}
            <i class="icon icon-calendar"></i>&nbsp;

            <i class="icon icon-object-group"></i>&nbsp;
            <span itemprop="articleSection">
                <a href="{BeesBlog::GetBeesBlogLink('beesblog_category', ['cat_rewrite' => $post->category->link_rewrite])}">
                    {$post->category->title|escape:'htmlall':'UTF-8'}
                </a>
            </span>
            {if $showComments}
                <span class="beesblog-comment-counter">
                    <i class="icon icon-comments"></i>&nbsp;
                    {*<a title="{$post.totalcomment|escape:'htmlall':'UTF-8'} Comments" href="{beesblog::GetBeesBlogLink('beesblog_post', $options)|escape:'htmlall':'UTF-8'}#disqus_thread">*}
                    {*{$post.totalcomment|escape:'htmlall':'UTF-8'} {l s=' Comments' mod='beesblog'}*}
                    {*</a>*}
                </span>
            {/if}
            {if $showViewed}
                <i class="icon icon-eye-open"></i>
                {l s=' views' mod='beesblog'} ({$post->viewed|escape:'htmlall':'UTF-8'})
            {/if}
        </div>
    </div>
</div>
