{assign var=postPath value=$post->link}
<article class="beesblog-post-card" itemscope="itemscope" itemtype="https://schema.org/BlogPosting">
    <div class="post-item">
        {if isset($postImage) && $postImage}
            <div class="post-thumbnail">
                <a title="{$post->title|escape:'htmlall':'UTF-8'}"
                   href="{$postPath|escape:'htmlall':'UTF-8'}"
                   itemprop="url">
                    {include file="./responsive_image.tpl" responsiveImage=$postImage responsiveAlt=$post->title responsiveContext='card'}
                </a>
            </div>
        {/if}

        <div class="post-title">
            <h2 itemprop="headline">
                <a title="{$post->title|escape:'htmlall':'UTF-8'}"
                   href="{$postPath|escape:'htmlall':'UTF-8'}">{$post->title|escape:'htmlall':'UTF-8'}</a>
            </h2>
        </div>

        <div class="post-content" itemprop="description">
            <p>{$post->getSummary()|escape:'htmlall':'UTF-8'}</p>
            <div class="post-read-more">
                <a title="{$post->title|escape:'htmlall':'UTF-8'}"
                   href="{$postPath|escape:'htmlall':'UTF-8'}">
                    {l s='Read more' mod='beesblog'} <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                </a>
            </div>
        </div>

        {include file="./post_info.tpl"}
    </div>
</article>
