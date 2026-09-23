<div class="beesblog-post-info post-additional-info post-meta-info">
    {if isset($showDate) && $showDate}
        <span class="post-date">
            <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
            <time datetime="{$post->published|date_format:'c'}">{$post->published|date_format}</time>
        </span>
    {/if}
    <span class="post-category">
        <i class="fa-solid fa-tags" aria-hidden="true"></i>
        <a href="{$post->category->link|escape:'htmlall':'UTF-8'}">{$post->category->title|escape:'htmlall':'UTF-8'}</a>
    </span>
    {if isset($showAuthor) && $showAuthor}
        <span class="post-author">
            <i class="fa-solid fa-user" aria-hidden="true"></i>
            {if $authorStyle}
                {$post->employee->firstname|escape:'htmlall':'UTF-8'} {$post->employee->lastname|escape:'htmlall':'UTF-8'}
            {else}
                {$post->employee->lastname|escape:'htmlall':'UTF-8'} {$post->employee->firstname|escape:'htmlall':'UTF-8'}
            {/if}
        </span>
    {/if}
    {if isset($showComments) && $showComments && $post->comments_enabled}
        <span class="post-comments">
            <i class="fa-solid fa-comments" aria-hidden="true"></i>
            <a title="{l s='0 Comments' mod='beesblog'}"
               href="{$postPath|escape:'htmlall':'UTF-8'}#disqus_thread"
               data-disqus-identifier="{$post->id|intval}">{l s='0 Comments' mod='beesblog'}</a>
        </span>
    {/if}
    {if isset($showViewed) && $showViewed}
        <span class="post-views">
            <i class="fa-solid fa-eye" aria-hidden="true"></i>
            {$post->viewed|intval} {l s='views' mod='beesblog'}
        </span>
    {/if}
</div>
