{*
 * 2017 Thirty Bees
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
 *  @author    Thirty Bees <modules@thirtybees.com>
 *  @copyright 2017 Thirty Bees
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}
<div itemtype="#" itemscope="" class="sdsarticleCat clearfix">
	<div id="beesblogpost-{$post.id_post|escape:'htmlall':'UTF-8'}">
		<div class="sdsarticleHeader">
			 {*TODO: move assign out of template*}
			{assign var="options" value=null}
			{$options.id_post = $post.id_post}
			{$options.url_key = $post.link_rewrite}
			<p class='sdstitle_block'>
				 {*TODO: remove call to BeesBlog*}
				{*<a title="{$post.meta_title}" href="{BeesBlog::GetBeesBlogLink('beesblog_post', $options)|escape:'htmlall':'UTF-8'}">{$post.meta_title|escape:'htmlall':'UTF-8'}</a>*}
			</p>
			 {*TODO: move assign out of template*}
			{assign var="options" value=null}
			{$options.id_post = $post.id_post}
			{$options.url_key = $post.link_rewrite}
			{assign var="catlink" value=null}
			{$catlink.id_category = $post.id_category}
			{$catlink.slug = $post.cat_link_rewrite}
			{if $beesshowauthor ==1}
			<span>{l s='Posted by' mod='beesblog'}
				<span itemprop="author">&nbsp;
					<i class="icon icon-user"></i>&nbsp;
					{if $beesshowauthorstyle != 0}
						{$post.firstname|escape:'htmlall':'UTF-8'} {$post.lastname|escape:'htmlall':'UTF-8'}
					{else}
						{$post.lastname|escape:'htmlall':'UTF-8'} {$post.firstname|escape:'htmlall':'UTF-8'}
					{/if}
				</span>
				{/if} &nbsp;<i class="icon icon-tags"></i>&nbsp;
				<span itemprop="articleSection">
					<a
							{*href="{beesblog::GetBeesBlogLink('beesblog_category', $categoryLink)}"*}
					>
						{if $title_category != ''}
							{$title_category|escape:'htmlall':'UTF-8'}
						{else}
							{$post.cat_name|escape:'htmlall':'UTF-8'}
						{/if}
					</a>
				</span>
				<span class="comment">
					<i class="icon icon-comments"></i>&nbsp;
					 {*TODO: fix disqus comment counter*}
					{*<a title="{$post.totalcomment|escape:'htmlall':'UTF-8'} Comments" href="{beesblog::GetBeesBlogLink('beesblog_post', $options)|escape:'htmlall':'UTF-8'}#disqus_thread">*}
						{*{$post.totalcomment|escape:'htmlall':'UTF-8'} {l s=' Comments' mod='beesblog'}*}
					{*</a>*}
				</span>
				{if $beesshowviewed ==1}&nbsp;
                    <i class="icon icon-eye-open"></i>{l s=' views' mod='beesblog'} ({$post.viewed|escape:'htmlall':'UTF-8'})
				{/if}
            </span>
		</div>
		<div class="articleContent">
			<a itemprop="url" title="{$post.meta_title}"
			   {*href="{beesblog::GetBeesBlogLink('beesblog_post', $options)|escape:'htmlall':'UTF-8'}"*}
			   class="imageFeaturedLink">
				{assign var="activeimgincat" value='0'}
				{$activeimgincat = $beesshownoimg}
				{if ($post.post_img != "no" && $activeimgincat == 0) || $activeimgincat == 1}
					<img itemprop="image" alt="{$post.meta_title|escape:'htmlall':'UTF-8'}" src="{$modules_dir|escape:'htmlall':'UTF-8'}beesblog/images/{$post.post_img|escape:'htmlall':'UTF-8'}-single-default.jpg" class="imageFeatured">
				{/if}
			</a>
		</div>
		<div class="sdsarticle-des">
		  <span itemprop="description" class="clearfix">
			  <div id="lipsum">
					{$post.short_description|escape:'htmlall':'UTF-8'}
			  </div></span>
		</div>
		<div class="sdsreadMore">
			 {*TODO: move assign out of template*}
			{assign var="options" value=null}
			{$options.id_post = $post.id_post}
			{$options.url_key = $post.link_rewrite}
			{*<span class="more">*}
				{*<a title="{$post.meta_title|escape:'htmlall':'UTF-8'}" href="{beesblog::GetBeesBlogLink('beesblog_post', $options)|escape:'htmlall':'UTF-8'}" class="r_more button-medium">*}
					{*{l s='Read more' mod='beesblog'}*}
				{*</a>*}
			{*</span>*}
		</div>
	</div>
</div>
