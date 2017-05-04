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
	<a href="{$blogHome|escape:'htmlall':'UTF-8'}">{l s='Blog' mod='beesblog'}</a>
	{if $totalPostsOnThisPage > 0}
		<span class="navigation-pipe">{$navigationPipe|escape:'htmlall':'UTF-8'}</span>{$category->title}
	{/if}
{/capture}
{if $totalPostsOnThisPage <= 0}
	<p class="error">{l s='No posts' mod='beesblog'}</p>
{else}
	{if isset($beesdisablecatimg)}
		{assign var="activeimgincat" value='0'}
		{$activeimgincat = $beesshownoimg}
		{if $title_category != ''}
			{foreach from=$categoryinfo item=category}
				<div>
					{if ($cat_image != "no" && $activeimgincat == 0) || $activeimgincat == 1}
						<img alt="{$category.meta_title|escape:'htmlall':'UTF-8'}"
							 src="{$img_dir|escape:'htmlall':'UTF-8'}/beesblog/beesblog_category/{$category->id|intval}-home-default.jpg"
						>
					{/if}
					{$category.description}
				</div>
			{/foreach}
		{/if}
	{/if}
	<div id="beesblogcat" class="block">
		{foreach from=$posts item=post}
			{include file="./post_list_item.tpl" post=$post}
		{/foreach}
	</div>
	{if !empty($pagenums)}
		<div class="row">
			<div class="post-page col-md-12">
				<div class="col-md-6">
					<ul class="pagination">
						{for $k=0 to $pagenums}
							{if $title_category != ''}
								{assign var="options" value=null}
								{$options.page = $k+1}
								{$options.id_category = $id_category}
								{$options.url_key = $cat_link_rewrite}
							{else}
								{assign var="options" value=null}
								{$options.page = $k+1}
							{/if}
							{if ($k+1) == $c}
								<li><span class="page-active">{$k+1|intval}</span></li>
							{else}
								{if $category->meta_title != ''}
									{* TODO: replace this call *}
									<li><a class="page-link" href="{BeesBlog::getBeesBlogLink('beesblog_category_pagination', $options)}">{$k+1|intval}</a>
									</li>
								{else}
									{* TODO: replace this call *}
									<li><a class="page-link" href="{BeesBlog::getBeesBlogLink('beesblog_list_pagination', $options)}">{$k+1|intval}</a>
									</li>
								{/if}
							{/if}
						{/for}
					</ul>
				</div>
				<div class="col-md-6">
					<div class="results">{l s="Showing" mod="beesblog"} {if $limit_start!=0}{$limit_start}{else}1{/if} {l s="to" mod="beeslatestnews"} {if $limit_start+$limit >= $total}{$total}{else}{$limit_start+$limit}{/if} {l s="of" mod="beesblog"} {$total|intval}
						({$page|intval} {l s="Pages" mod="beesblog"})
					</div>
				</div>
			</div>
		</div>
	{/if}
{/if}
{if isset($beescustomcss)}
	<style>
		{$beescustomcss|escape:'htmlall':'UTF-8'}
	</style>
{/if}
