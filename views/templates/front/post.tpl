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
{capture name=path}
	<a href="{BeesBlog::GetBeesBlogLink()}">
		{l s='Blog' mod='beesblog'}
	</a>
	<span class="navigation-pipe">{$navigationPipe|escape:'htmlall':'UTF-8'}</span>{$post->title|escape:'htmlall':'UTF-8'}{/capture}
<div id="content" class="block">
	<div itemtype="#" itemscope="" id="sdsblogArticle" class="blog-post">
		<div>
			{$displayBeesBlogBeforePost}
		</div>
		<div class="page-item-title">
			<h1>{$post->title|escape:'htmlall':'UTF-8'}</h1>
		</div>
		<div class="post-info">
			<span>
				{if $showAuthor}
                    {l s='Posted by ' mod='beesblog'}
					<i class="icon icon-user"></i>
					<span itemprop="author">
						{if $authorStyle}
							{$post->firstname|escape:'htmlall':'UTF-8'} {$post->lastname|escape:'htmlall':'UTF-8'}
						{else}
							{$post->lastname|escape:'htmlall':'UTF-8'} {$post->firstname|escape:'htmlall':'UTF-8'}
						{/if}
					</span>&nbsp;
					<i class="icon icon-calendar"></i>&nbsp;
					<span itemprop="dateCreated">{$post->published|date_format}</span>
				{/if}
				{if $showComments}
				<i class="icon icon-comments"></i>&nbsp;
                {/if}
			</span>
			<a title="" style="display:none" itemprop="url" href="#"></a>
		</div>
		<div itemprop="articleBody">
			{*<div id="lipsum" class="articleContent">*}
				{*{assign var="activeimgincat" value='0'}*}
				{*{$activeimgincat = $beesshownoimg}*}
				{*{if ($post_img != "no" && $activeimgincat == 0) || $activeimgincat == 1}*}
					{*<a id="post_images" href="{$modules_dir|escape:'htmlall':'UTF-8'}/beesblog/images/{$post_img|escape:'htmlall':'UTF-8'}-single-default.jpg">*}
						{*<img src="{$modules_dir|escape:'htmlall':'UTF-8'}/beesblog/images/{$post_img|escape:'htmlall':'UTF-8'}-single-default.jpg" alt="{$title_post|escape:'htmlall':'UTF-8'}">*}
					{*</a>*}
				{*{/if}*}
			{*</div>*}
			<div class="sdsarticle-des">
				{$post->content}
			</div>
			{*{if $tags != ''}*}
				{*<div class="sdstags-update">*}
					{*<span class="tags"><b>{l s='Tags:' mod='beesblog'} </b>*}
						{*{foreach from=$tags item=tag}*}
							{*{assign var="options" value=null}*}
							{*{$options.tag = $tag.name|urlencode}*}
							{*<a title="tag" href="{beesblog::GetBeesBlogLink('beesblog_tag', $options)|escape:'html':'UTF-8'}">{$tag.name|escape:'htmlall':'UTF-8'}</a>*}
						{*{/foreach}*}
					{*</span>*}
				{*</div>*}
			{*{/if}*}
		</div>
		<div class="sdsarticleBottom">
			{$displayBeesBlogAfterPost}
		</div>
	</div>
	<div id="disqus_thread"></div>

	<script>
		/**
		 * RECOMMENDED CONFIGURATION VARIABLES: EDIT AND UNCOMMENT THE SECTION BELOW TO INSERT DYNAMIC VALUES FROM YOUR PLATFORM OR CMS.
		 * LEARN WHY DEFINING THESE VARIABLES IS IMPORTANT: https://disqus.com/admin/universalcode/#configuration-variables
		 */
		var disqus_config = function () {
			this.page.url = '{Tools::getHttpHost(true)|cat:$smarty.server.REQUEST_URI|escape:'javascript':'UTF-8'}'; // Replace PAGE_URL with your page's canonical URL variable
			this.page.identifier = '{'blog-'|cat:Context::getContext()->language->iso_code|strtolower|cat:'-'|cat:$id_post|escape:'javascript':'UTF-8'}'; // Replace PAGE_IDENTIFIER with your page's unique identifier variable
		};

		(function () { // DON'T EDIT BELOW THIS LINE
			var d = document, s = d.createElement('script');

			s.src = '//{$disqusUser|escape:'javascript':'UTF-8'}.disqus.com/embed.js';

			s.setAttribute('data-timestamp', +new Date());
			(d.head || d.body).appendChild(s);
		})();
	</script>
	<noscript>Please enable JavaScript to view the <a href="https://disqus.com/?ref_noscript" rel="nofollow">comments
			powered by Disqus.</a></noscript>
</div>
