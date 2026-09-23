{assign var=postPath value=$post->link}
{capture name=path}
    <a href="{$blogHome|escape:'htmlall':'UTF-8'}">{l s='Blog' mod='beesblog'}</a>
    <span class="navigation-pipe">{$navigationPipe|escape:'htmlall':'UTF-8'}</span>{$post->title|escape:'htmlall':'UTF-8'}
{/capture}

<article class="beesblog-warehouse beesblog-single" itemscope="itemscope" itemtype="https://schema.org/BlogPosting">
    <div id="beesblog-before-post">{$displayBeesBlogBeforePost}</div>

    <h1 class="page-heading" itemprop="headline">{$post->title|escape:'htmlall':'UTF-8'}</h1>
    {include file="./post_info.tpl"}

    {if isset($postImage) && $postImage}
        <div class="post-featured-image">
            {include file="./responsive_image.tpl" responsiveImage=$postImage responsiveAlt=$post->title responsiveContext='full'}
        </div>
    {/if}

    <div class="post-content rte" itemprop="articleBody">{$post->content}</div>
    <div id="beesblog-after-post">{$displayBeesBlogAfterPost}</div>

    {if isset($socialSharing) && $socialSharing}
        <div class="post-block beesblog-social-sharing socialsharing_beesblog hidden-print">
            <h4 class="page-subheading">{l s='Share this post' mod='beesblog'}</h4>
            <button data-type="twitter" type="button" class="btn btn-xs btn-twitter"><i class="fa-brands fa-twitter" aria-hidden="true"></i> Tweet</button>
            <button data-type="facebook" type="button" class="btn btn-xs btn-facebook"><i class="fa-brands fa-facebook-f" aria-hidden="true"></i> Share</button>
            <button data-type="pinterest" type="button" class="btn btn-xs btn-pinterest"><i class="fa-brands fa-pinterest-p" aria-hidden="true"></i> Pinterest</button>
        </div>
    {/if}

    {if $showComments && $post->comments_enabled}
        {include file="./disqus.tpl"}
    {/if}
</article>

{if isset($disqusUsername) && $disqusUsername && $showComments}
    <script id="dsq-count-scr" src="//{$disqusUsername|escape:'htmlall':'UTF-8'}.disqus.com/count.js" async></script>
{/if}
