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
<div itemtype="#" itemscope="" class="sdsarticleCat clearfix">
    <div id="beesblogpost-{$post->id|intval}">
        <div class="sdsarticleHeader">
            <p>
                <a title="{$post->title}" href="{BeesBlog::GetBeesBlogLink('beesblog_post', ['blog_rewrite' => $post->link_rewrite])|escape:'htmlall':'UTF-8'}">{$post->title|escape:'htmlall':'UTF-8'}</a>
            </p>
            {*TODO: move assign out of template*}
            {if $showAuthor}
            <i class="icon icon-user"></i>&nbsp;
            <span>{l s='Posted by' mod='beesblog'}
                <span itemprop="author">
                    {if $authorStyle}
                        {$post->firstname|escape:'htmlall':'UTF-8'} {$post->lastname|escape:'htmlall':'UTF-8'}
                    {else}
                        {$post->lastname|escape:'htmlall':'UTF-8'} {$post->firstname|escape:'htmlall':'UTF-8'}
                    {/if}
				</span>
                {/if} &nbsp;<i class="icon icon-object-group"></i>&nbsp;
				<span itemprop="articleSection">
					<a
                            href="{BeesBlog::GetBeesBlogLink('beesblog_category', ['cat_rewrite' => $post->category->link_rewrite])}"
                    >
                        {$post->category->title|escape:'htmlall':'UTF-8'}
					</a>
				</span>
                {if $showComments}
				<span class="comment">
					<i class="icon icon-comments"></i>&nbsp;
                    {*TODO: fix disqus comment counter*}
                    {*<a title="{$post.totalcomment|escape:'htmlall':'UTF-8'} Comments" href="{beesblog::GetBeesBlogLink('beesblog_post', $options)|escape:'htmlall':'UTF-8'}#disqus_thread">*}
                    {*{$post.totalcomment|escape:'htmlall':'UTF-8'} {l s=' Comments' mod='beesblog'}*}
                    {*</a>*}
				</span>
                {/if}
                {if $showViewed}
                    <i class="icon icon-eye-open"></i>
                    {l s=' views' mod='beesblog'} ({$post->viewed|escape:'htmlall':'UTF-8'})
                {/if}
            </span>
        </div>
        <div class="articleContent">
            <a itemprop="url" title="{$post->title}"
                    {*href="{beesblog::GetBeesBlogLink('beesblog_post', $options)|escape:'htmlall':'UTF-8'}"*}
               class="imageFeaturedLink">
            </a>
        </div>
        <div class="sdsarticle-des">
		  <span itemprop="description" class="clearfix">
			  <div id="lipsum">
					{$post->short_description|escape:'htmlall':'UTF-8'}
			  </div></span>
        </div>
        <div class="sdsreadMore">
            {*<span class="more">*}
            {*<a title="{$post->title|escape:'htmlall':'UTF-8'}" href="{beesblog::GetBeesBlogLink('beesblog_post', $options)|escape:'htmlall':'UTF-8'}" class="r_more button-medium">*}
            {*{l s='Read more' mod='beesblog'}*}
            {*</a>*}
            {*</span>*}
        </div>
    </div>
</div>
