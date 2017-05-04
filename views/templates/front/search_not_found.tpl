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
<div id="pagenotfound" class="row">
	<div class="center_column col-xs-12 col-sm-12" id="center_column">
		<div class="pagenotfound">
			<h1>{l s="Sorry, but nothing matched your search terms." mod="beesblog"}</h1>
			<p>
				{l s="Please try again with some different keywords." mod="beesblog"}
			</p>
			{* TODO: remove call to BeesBlog *}
			<form class="std" method="post" action="{beesblog::GetBeesBlogLink('beesblog_search')}">
				<fieldset>
					<div>
						<input type="hidden" value="0" name="beesblogaction">
						<input type="text" class="form-control grey" value="{$beessearch|escape:'htmlall':'UTF-8'}" name="beessearch" id="search_query">
						<button class="btn btn-default button button-small" value="OK" name="beesblogsubmit" type="submit">
							<span>{l s="Ok" mod="beesblog"}</span>
						</button>
					</div>
				</fieldset>
			</form>
			{* TODO: remove call to BeesBlog *}
			<div class="buttons">
				<a title="Home" href="{BeesBlog::GetBeesBlogLink('beesblog')}" class="btn btn-default button button-medium">
					<span>
						<i class="icon-chevron-left left"></i>&nbsp;
						{l s="Home page" mod="beesblog"}
					</span>
				</a>
			</div>
		</div>
	</div>
</div>
