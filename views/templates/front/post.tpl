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

{assign var=postPath value=BeesBlog::GetBeesBlogLink('beesblog_post', ['blog_rewrite' => $post->link_rewrite])}
{capture name=path}
	<a href="{$blogHome|escape:'htmlall':'UTF-8'}">{l s='Blog' mod='beesblog'}</a>
	<span class="navigation-pipe">{$navigationPipe|escape:'htmlall':'UTF-8'}</span>{$post->title}
{/capture}

<article>
	<div id="sdsblogArticle" class="clearfix beesblog-post-list-item">
		<div id="beesblog-before-pos">
			{$displayBeesBlogBeforePost}
		</div>
		<div class="block">
			<h4 class="title_block">{$post->title|escape:'htmlall':'UTF-8'}</h4>
			{assign var=imagePath value=Media::getMediaPath(BeesBlog::getPostImagePath($post->id))}
			{if ($imagePath)}
				<img class="img-responsive" alt="{$post->title|escape:'htmlall':'UTF-8'}" src="{$imagePath|escape:'htmlall':'UTF-8'}">
			{/if}
		</div>
		<div class="block">
			{$post->content}
		</div>

		{include file="./post_info.tpl"}

		<div id="beesblog-after-post" class="row">
			{$displayBeesBlogAfterPost}
		</div>
	</div>

	{if isset($socialSharing) && $socialSharing}
		<br/>
		<section>
			<p class="socialsharing_beesblog hidden-print">
				<button data-type="twitter" type="button" class="btn btn-xs btn-twitter">
					<i class="icon-twitter"></i> Tweet
				</button>
				<button data-type="facebook" type="button" class="btn btn-xs btn-facebook">
					<i class="icon-facebook"></i> Share
				</button>
				<button data-type="pinterest" type="button" class="btn btn-xs btn-pinterest">
					<i class="icon-pinterest"></i> Pinterest
				</button>
			</p>
		</section>
	{/if}

	{if $showComments && $post->comments_enabled}
		{include "./disqus.tpl"}
	{/if}
</article>
{if isset($disqusUsername) && $disqusUsername && $showComments}
	<script id="dsq-count-scr" src="//{$disqusUsername|escape:'htmlall':'UTF-8'}.disqus.com/count.js" async></script>
{/if}
