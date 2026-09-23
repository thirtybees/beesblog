{assign var=postPath value=$post->link}
{capture name=path}
    <a href="{$blogHome|escape:'htmlall':'UTF-8'}">{l s='Blog' mod='beesblog'}</a>
    <span class="navigation-pipe">{$navigationPipe|escape:'htmlall':'UTF-8'}</span>{$post->title|escape:'htmlall':'UTF-8'}
{/capture}
<article>
    <div id="sdsblogArticle" class="clearfix beesblog-post-list-item">
        <div id="beesblog-before-pos">{$displayBeesBlogBeforePost}</div>
        <div class="block">
            <h1 class="title_block">{$post->title|escape:'htmlall':'UTF-8'}</h1>
            {if isset($postImage) && $postImage}
                {include file="./responsive_image.tpl" responsiveImage=$postImage responsiveAlt=$post->title responsiveContext='full'}
            {/if}
        </div>
        <div class="block">{$post->content}</div>
        {include file="./post_info.tpl"}
        <div id="beesblog-after-post" class="row">{$displayBeesBlogAfterPost}</div>
    </div>
    {if isset($socialSharing) && $socialSharing}
        <br>
        <section>
            <p class="socialsharing_beesblog hidden-print">
                <button data-type="twitter" type="button" class="btn btn-xs btn-twitter"><i class="icon-twitter"></i> Tweet</button>
                <button data-type="facebook" type="button" class="btn btn-xs btn-facebook"><i class="icon-facebook"></i> Share</button>
                <button data-type="pinterest" type="button" class="btn btn-xs btn-pinterest"><i class="icon-pinterest"></i> Pinterest</button>
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
