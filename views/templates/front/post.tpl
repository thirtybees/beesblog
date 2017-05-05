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
{capture name=path}
	<a href="{$blogHome|escape:'htmlall':'UTF-8'}">{l s='Blog' mod='beesblog'}</a>
	<span class="navigation-pipe">{$navigationPipe|escape:'htmlall':'UTF-8'}</span>{$post->title}
{/capture}
<div id="content" class="block">
	<div itemtype="#" itemscope="" id="sdsblogArticle" class="blog-post">
		<div id="beesblog-before-pos" class="row">
			{$displayBeesBlogBeforePost}
		</div>
		<div class="row">
			<h4 class="title_block">{$post->title|escape:'htmlall':'UTF-8'}</h4>
            {assign var=imagePath value=Media::getMediaPath(BeesBlog::getPostImagePath($post->id))}
            {if ($imagePath)}
				<img class="img-responsive" alt="{$post->title|escape:'htmlall':'UTF-8'}" src="{$imagePath|escape:'htmlall':'UTF-8'}">
            {/if}
		</div>
		<div class="row">
            {$post->content}
		</div>
		{include file="./post_info.tpl"}
		<div id="beesblog-after-post" class="row">
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
