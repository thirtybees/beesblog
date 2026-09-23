{capture name=path}
    <a href="{$blogHome|escape:'htmlall':'UTF-8'}">{l s='Blog' mod='beesblog'}</a>
    {if $category->id}
        <span class="navigation-pipe">{$navigationPipe|escape:'htmlall':'UTF-8'}</span>{$category->title|escape:'htmlall':'UTF-8'}
    {/if}
{/capture}

<section class="beesblog-warehouse beesblog-list">
    <h1 class="page-heading">{$category->title|escape:'htmlall':'UTF-8'}</h1>

    {if isset($showCategoryImage) && $showCategoryImage}
        {if isset($categoryImage) && $categoryImage}
            <div class="beesblog-category-image">
                {include file="./responsive_image.tpl" responsiveImage=$categoryImage responsiveAlt=$category->title responsiveContext='full'}
            </div>
        {/if}
        {if $category->description}
            <div class="beesblog-category-description rte">{$category->description}</div>
        {/if}
    {/if}

    {if $totalPostsOnThisPage <= 0}
        <p class="warning alert alert-warning">{l s='No posts' mod='beesblog'}</p>
    {else}
        <div id="beesblog-category-list" class="row beesblog-post-grid" itemscope="itemscope" itemtype="https://schema.org/Blog">
            {foreach from=$posts item=post}
                {assign var=postImage value=false}
                {if isset($postImages[$post->id])}
                    {assign var=postImage value=$postImages[$post->id]}
                {/if}
                <div class="col-md-6 col-sm-6 col-xs-12 col-ms-12" itemprop="blogPost">
                    {include file="./post_list_item.tpl" post=$post postImage=$postImage}
                </div>
            {/foreach}
        </div>

        {if $totalPages}
            <div class="beesblog-category-pagination row">
                <div class="col-md-6 col-sm-6">
                    <ul class="pagination">
                        {for $k = 1 to $totalPages}
                            {if $k === $pageNumber}
                                <li class="active current"><span>{$k|intval}</span></li>
                            {elseif Validate::isLoadedObject($category)}
                                <li><a class="page-link" href="{BeesBlog::getBeesBlogLink('beesblog_category_pagination', ['page' => $k, 'cat_rewrite' => $category->link_rewrite])|escape:'htmlall':'UTF-8'}">{$k|intval}</a></li>
                            {else}
                                <li><a class="page-link" href="{BeesBlog::getBeesBlogLink('beesblog_list_pagination', ['page' => $k])|escape:'htmlall':'UTF-8'}">{$k|intval}</a></li>
                            {/if}
                        {/for}
                    </ul>
                </div>
                <div class="col-md-6 col-sm-6">
                    <div class="beesblog-results">
                        {l s='Showing' mod='beesblog'} {if $start !== 0}{$start|intval}{else}1{/if}
                        {l s='to' mod='beesblog'} {if $start + $postsPerPage >= $totalPosts}{$totalPosts|intval}{else}{$start + $postsPerPage|intval}{/if}
                        {l s='of' mod='beesblog'} {$totalPosts|intval}
                        ({$totalPages|intval} {if intval($totalPages) === 1}{l s='Page' mod='beesblog'}{else}{l s='Pages' mod='beesblog'}{/if})
                    </div>
                </div>
            </div>
        {/if}
    {/if}
</section>

{if isset($customCss)}
    <style>{$customCss|escape:'htmlall':'UTF-8'}</style>
{/if}
{if isset($disqusUsername) && $disqusUsername && $showComments}
    <script id="dsq-count-scr" src="//{$disqusUsername|escape:'htmlall':'UTF-8'}.disqus.com/count.js" async></script>
{/if}
