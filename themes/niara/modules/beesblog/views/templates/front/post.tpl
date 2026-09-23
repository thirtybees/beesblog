{assign var=postPath value=$post->link}
{capture name=path}
    <a href="{$blogHome|escape:'htmlall':'UTF-8'}">{l s='Blog' mod='beesblog'}</a>
    <span class="navigation-pipe">{$navigationPipe|escape:'htmlall':'UTF-8'}</span>{$post->title|escape:'htmlall':'UTF-8'}
{/capture}
<div id="beesblog-content" class="block">
    <div id="sdsblogArticle" class="blog-post">
        <div id="beesblog-before-pos" class="row">
            {$displayBeesBlogBeforePost}
        </div>
        {if isset($postImage) && $postImage}
            {include file="./responsive_image.tpl" responsiveImage=$postImage responsiveAlt=$post->title responsiveContext='padded'}
        {/if}
        <h4 class="title_block">{$post->title|escape:'htmlall':'UTF-8'}</h4>
        {include file="./post_info.tpl"}
        <div class="">
            {$post->content}
        </div>
        <div id="beesblog-after-post" class="row">
            {$displayBeesBlogAfterPost}
        </div>
    </div>
    {if isset($socialSharing) && $socialSharing}
        <br/>
        <p class="socialsharing_beesblog hidden-print">
            <button data-type="twitter" type="button" class="btn btn-xs btn-twitter"><i class="icon-twitter"></i> Tweet</button>
            <button data-type="facebook" type="button" class="btn btn-xs btn-facebook"><i class="icon-facebook"></i> Share</button>
            <button data-type="pinterest" type="button" class="btn btn-xs btn-pinterest"><i class="icon-pinterest"></i> Pinterest</button>
        </p>
    {/if}
    {if $showComments && $post->comments_enabled}
        {include "./disqus.tpl"}
    {/if}
</div>
