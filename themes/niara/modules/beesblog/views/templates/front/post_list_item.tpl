{assign var=postPath value=$post->link}
<div class="clearfix beesblog-post-list-item">
    <div id="beesblog-post-{$post->id|intval}">
        {if isset($postImage) && $postImage}
            <a title="{$post->title|escape:'htmlall':'UTF-8'}" href="{$postPath|escape:'htmlall':'UTF-8'}">
                {include file="./responsive_image.tpl" responsiveImage=$postImage responsiveAlt=$post->title responsiveContext='padded'}
            </a>
        {/if}
        <h4 class="title_block">
            <a title="{$post->title|escape:'htmlall':'UTF-8'}" href="{$postPath|escape:'htmlall':'UTF-8'}">{$post->title|escape:'htmlall':'UTF-8'}</a>
        </h4>
        {include file="./post_info.tpl"}
        <div class="beesblog-post-list-summary">
            <span class="clearfix">
                {$post->getSummary()|escape:'htmlall':'UTF-8'}&nbsp;
            </span>
            <a title="{$post->title|escape:'htmlall':'UTF-8'}" href="{$postPath|escape:'htmlall':'UTF-8'}" class="beesblog-read-more-link">
                {l s='Read more' mod='beesblog'}
            </a>
        </div>
    </div>
</div>
