{**
 * Responsive format candidates with one legacy fallback.
 * Niara uses the standard Bootstrap grid and adds 20px padding on each side
 * of images rendered in its .block containers.
 **}
{assign var=renderedSidebarCount value=$beesblogImageLayout.rendered_sidebar_count|intval}
{if $responsiveContext === 'full'}
    {if $renderedSidebarCount === 2}
        {assign var=responsiveSizes value='(min-width: 1200px) 555px, (min-width: 992px) 455px, (min-width: 768px) 345px, calc(100vw - 30px)'}
    {elseif $renderedSidebarCount === 1}
        {assign var=responsiveSizes value='(min-width: 1200px) 847.5px, (min-width: 992px) 697.5px, (min-width: 768px) 532.5px, calc(100vw - 30px)'}
    {else}
        {assign var=responsiveSizes value='(min-width: 1200px) 1140px, (min-width: 992px) 940px, (min-width: 768px) 720px, calc(100vw - 30px)'}
    {/if}
{elseif $responsiveContext === 'padded'}
    {if $renderedSidebarCount === 2}
        {assign var=responsiveSizes value='(min-width: 1200px) 515px, (min-width: 992px) 415px, (min-width: 768px) 305px, calc(100vw - 70px)'}
    {elseif $renderedSidebarCount === 1}
        {assign var=responsiveSizes value='(min-width: 1200px) 807.5px, (min-width: 992px) 657.5px, (min-width: 768px) 492.5px, calc(100vw - 70px)'}
    {else}
        {assign var=responsiveSizes value='(min-width: 1200px) 1100px, (min-width: 992px) 900px, (min-width: 768px) 680px, calc(100vw - 70px)'}
    {/if}
{/if}
<picture class="beesblog-picture">
    {if isset($responsiveImage.srcset) && $responsiveImage.srcset}
        <source type="{$responsiveImage.modern_type|escape:'htmlall':'UTF-8'}"
                srcset="{$responsiveImage.srcset|escape:'htmlall':'UTF-8'}"
                sizes="{if $responsiveImage.loading === 'lazy'}auto, {/if}{$responsiveSizes|escape:'htmlall':'UTF-8'}">
    {/if}
    <img class="img-responsive beesblog-responsive-image"
         src="{$responsiveImage.fallback_url|escape:'htmlall':'UTF-8'}"
         alt="{$responsiveAlt|escape:'htmlall':'UTF-8'}"
         width="{$responsiveImage.width|intval}"
         height="{$responsiveImage.height|intval}"
         loading="{$responsiveImage.loading|escape:'htmlall':'UTF-8'}"
         decoding="async"
         fetchpriority="{$responsiveImage.fetchpriority|escape:'htmlall':'UTF-8'}">
</picture>
