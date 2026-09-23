{assign var=escapedCategoryTitle value=$category->title|escape:'htmlall':'UTF-8'}
{capture name=path}
    <a href="{$blogHome|escape:'htmlall':'UTF-8'}">{l s='Blog' mod='beesblog'}</a>
    {if $totalPostsOnThisPage > 0}
        {if $category->id}
            <span class="navigation-pipe">{$navigationPipe|escape:'htmlall':'UTF-8'}</span>{l s='Category: %s' mod='beesblog' sprintf=[$escapedCategoryTitle]}
        {else}
            <span class="navigation-pipe">{$navigationPipe|escape:'htmlall':'UTF-8'}</span>{$category->title|escape:'htmlall':'UTF-8'}
        {/if}
    {/if}
{/capture}
{if $totalPostsOnThisPage <= 0}
    <p class="error">{l s='No posts' mod='beesblog'}</p>
{else}
    {if isset($showCategoryImage) && $showCategoryImage && isset($categoryImage) && $categoryImage}
        <div>
            {include file="./responsive_image.tpl" responsiveImage=$categoryImage responsiveAlt=$category->title responsiveContext='full'}
            <em>{$category->description}</em>
            <br><br><br><br>
        </div>
    {/if}
    <div id="beesblog-category-list" class="block">
        {foreach from=$posts item=post}
            {assign var=postImage value=false}
            {if isset($postImages[$post->id])}
                {assign var=postImage value=$postImages[$post->id]}
            {/if}
            {include file="./post_list_item.tpl" post=$post postImage=$postImage}
        {/foreach}
    </div>
    {if $totalPages}
        <div class="beesblog-category-pagination row">
            <div class="col-md-6 col-sm-6">
                <ul class="pagination">
                    {for $k = 1 to $totalPages}
                        {if $k === $pageNumber}
                            <li><span class="page-active">{$k|intval}</span></li>
                        {elseif Validate::isLoadedObject($category)}
                            <li><a class="page-link" href="{BeesBlog::getBeesBlogLink('beesblog_category_pagination', ['page' => $k, 'cat_rewrite' => $category->link_rewrite])|escape:'htmlall':'UTF-8'}">{$k|intval}</a></li>
                        {else}
                            <li><a class="page-link" href="{BeesBlog::getBeesBlogLink('beesblog_list_pagination', ['page' => $k])|escape:'htmlall':'UTF-8'}">{$k|intval}</a></li>
                        {/if}
                    {/for}
                </ul>
            </div>
            <div class="col-md-6 col-sm-6">
                <div class="beesblog-results">{l s='Showing' mod='beesblog'} {if $start !== 0}{$start}{else}1{/if} {l s='to' mod='beesblog'} {if $start + $postsPerPage >= $totalPosts}{$totalPosts|intval}{else}{$start + $postsPerPage|intval}{/if} {l s='of' mod='beesblog'} {$totalPosts|intval} ({$totalPages|intval} {if intval($totalPages) === 1}{l s='Page' mod='beesblog'}{else}{l s='Pages' mod='beesblog'}{/if})</div>
            </div>
        </div>
    {/if}
{/if}
{if isset($customCss)}
    <style>{$customCss|escape:'htmlall':'UTF-8'}</style>
{/if}
{if isset($disqusUsername) && $disqusUsername && $showComments}
    <script id="dsq-count-scr" src="//{$disqusUsername|escape:'htmlall':'UTF-8'}.disqus.com/count.js" async></script>
{/if}
