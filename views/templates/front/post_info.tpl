<div class="beesblog-post-info">
    {if isset($showAuthor) && $showAuthor}
        <i class="icon icon-user"></i>&nbsp;
        {l s='Posted by' mod='beesblog'}
        <span itemprop="author">
            {if $authorStyle}
                {$post->employee->firstname|escape:'htmlall':'UTF-8'} {$post->employee->lastname|escape:'htmlall':'UTF-8'}
            {else}
                 {$post->employee->lastname|escape:'htmlall':'UTF-8'} {$post->employee->firstname|escape:'htmlall':'UTF-8'}
            {/if}
        </span>
    {/if}
    {if isset($showDate) && $showDate}
        <i class="icon icon-calendar"></i>&nbsp;
        {$post->published|date_format}
    {/if}

    <i class="icon icon-object-group"></i>&nbsp;
    <span itemprop="articleSection">
        <a href="{BeesBlog::GetBeesBlogLink('beesblog_category', ['cat_rewrite' => $post->category->link_rewrite])}">
            {$post->category->title|escape:'htmlall':'UTF-8'}
        </a>
    </span>
    {if isset($showComments) && $showComments}
        <span class="beesblog-comment-counter">
            <i class="icon icon-comments"></i>&nbsp;
            {*<a title="{$post.totalcomment|escape:'htmlall':'UTF-8'} Comments" href="{beesblog::GetBeesBlogLink('beesblog_post', $options)|escape:'htmlall':'UTF-8'}#disqus_thread">*}
            {*{$post.totalcomment|escape:'htmlall':'UTF-8'} {l s=' Comments' mod='beesblog'}*}
            {*</a>*}
        </span>
    {/if}
    {if isset($showViewed) && $showViewed}
        <i class="icon icon-eye-open"></i>
        {l s=' views' mod='beesblog'} ({$post->viewed|escape:'htmlall':'UTF-8'})
    {/if}
</div>
