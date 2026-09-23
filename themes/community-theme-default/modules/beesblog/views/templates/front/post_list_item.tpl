{assign var=postPath value=$post->link}
<article>
    <div class="clearfix beesblog-post-list-item">
        <div id="beesblog-post-{$post->id|intval}">
            <h4 class="title_block">
                <a title="{$post->title|escape:'htmlall':'UTF-8'}" href="{$postPath|escape:'htmlall':'UTF-8'}">{$post->title|escape:'htmlall':'UTF-8'}</a>
            </h4>
            <div class="beesblog-post-list-summary">
                {if isset($postImage) && $postImage}
                    <a title="{$post->title|escape:'htmlall':'UTF-8'}" href="{$postPath|escape:'htmlall':'UTF-8'}">
                        {include file="./responsive_image.tpl" responsiveImage=$postImage responsiveAlt=$post->title responsiveContext='full'}
                    </a>
                {/if}
                <span class="clearfix">{$post->getSummary()|escape:'htmlall':'UTF-8'}&nbsp;</span>
                <a title="{$post->title|escape:'htmlall':'UTF-8'}" href="{$postPath|escape:'htmlall':'UTF-8'}" class="beesblog-read-more-link btn btn-primary">
                    {l s='Read more' mod='beesblog'} {'>'|escape:'htmlall':'UTF-8'}
                </a>
            </div>
            {include file="./post_info.tpl"}
        </div>
    </div>
</article>
