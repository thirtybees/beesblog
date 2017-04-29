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
{if $comment.id_bees_blog_comment != ''}
	<div id="comment-{$comment.id_bees_blog_comment|escape:'htmlall':'UTF-8'}">
		<ul class="commentList">
			<li class="even">
				<img class="avatar" alt="Avatar" src="{$modules_dir|escape:'htmlall':'UTF-8'}/beesblog/images/avatar/avatar-author-default.jpg">
				<div class="name">{$childcommnets.name|escape:'htmlall':'UTF-8'}</div>
				<div class="created">
					<span itemprop="commentTime">{$childcommnets.created|date_format}</span>
				</div>
				<p>{$childcommnets.content}</p>
				{* TODO: remove direct configuration get *}
				{if Configuration::get('beesenablecomment') == 1}
					{if $comment_status == 1}
						<div class="reply">
							{* TODO: remove smarty.get *}
							<a onclick="return addComment.moveForm('comment-{$comment.id_bees_blog_comment|escape:'htmlall':'UTF-8'}', '{$comment.id_bees_blog_comment|escape:'htmlall':'UTF-8'}', 'respond', '{$smarty.get.id_post|escape:'htmlall':'UTF-8'}')"
							   class="comment-reply-link">{l s="Reply" mod="beesblog"}</a>
						</div>
					{/if}
				{/if}
				{if isset($childcommnets.child_comments)}
					{foreach from=$childcommnets.child_comments item=comment}
						{if isset($childcommnets.child_comments)}
							{include file="./comment_loop.tpl" childcommnets=$comment}
							{$i=$i+1}
						{/if}
					{/foreach}
				{/if}
			</li>
		</ul>
	</div>
{/if}
