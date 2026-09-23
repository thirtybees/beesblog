{**
 * Warehouse uses 20px grid gutters, 1020/1270px container caps, custom
 * 1000/1320px desktop breakpoints, and optional 280px sidebar navigation.
 * Its wide content columns use enabled-sidebars while smaller columns use
 * the sidebars that actually render hook content.
 **}
{assign var=renderedSidebarCount value=$beesblogImageLayout.rendered_sidebar_count|intval}
{assign var=enabledSidebarCount value=$beesblogImageLayout.enabled_sidebar_count|intval}
{assign var=warehouseSidebarHeader value=false}
{if isset($warehouse_vars.header_style) && $warehouse_vars.header_style == 1}
    {assign var=warehouseSidebarHeader value=true}
{/if}

{if $responsiveContext === 'full'}
    {if $enabledSidebarCount === 2}
        {assign var=warehouseWideSize value='736px'}
        {assign var=warehouseSidebarHeaderWideSize value='min(736px, calc(60vw - 194px))'}
    {elseif $enabledSidebarCount === 1}
        {assign var=warehouseWideSize value='988px'}
        {assign var=warehouseSidebarHeaderWideSize value='min(988px, calc(80vw - 252px))'}
    {else}
        {assign var=warehouseWideSize value='1240px'}
        {assign var=warehouseSidebarHeaderWideSize value='min(1240px, calc(100vw - 310px))'}
    {/if}

    {if $renderedSidebarCount === 2}
        {assign var=warehouseTabletSize value='min(485px, calc(50vw - 25px))'}
        {assign var=warehouseSidebarHeaderMediumSize value='min(485px, calc(50vw - 165px))'}
    {elseif $renderedSidebarCount === 1}
        {assign var=warehouseTabletSize value='min(737.5px, calc(75vw - 27.5px))'}
        {assign var=warehouseSidebarHeaderMediumSize value='min(737.5px, calc(75vw - 237.5px))'}
    {else}
        {assign var=warehouseTabletSize value='min(990px, calc(100vw - 30px))'}
        {assign var=warehouseSidebarHeaderMediumSize value='min(990px, calc(100vw - 310px))'}
    {/if}
{elseif $responsiveContext === 'card'}
    {if $enabledSidebarCount === 2}
        {assign var=warehouseWideSize value='358px'}
        {assign var=warehouseSidebarHeaderWideSize value='min(358px, calc(30vw - 107px))'}
    {elseif $enabledSidebarCount === 1}
        {assign var=warehouseWideSize value='484px'}
        {assign var=warehouseSidebarHeaderWideSize value='min(484px, calc(40vw - 136px))'}
    {else}
        {assign var=warehouseWideSize value='610px'}
        {assign var=warehouseSidebarHeaderWideSize value='min(610px, calc(50vw - 165px))'}
    {/if}

    {if $renderedSidebarCount === 2}
        {assign var=warehouseTabletSize value='min(232.5px, calc(25vw - 22.5px))'}
        {assign var=warehouseSidebarHeaderMediumSize value='min(232.5px, calc(25vw - 92.5px))'}
    {elseif $renderedSidebarCount === 1}
        {assign var=warehouseTabletSize value='min(358.75px, calc(37.5vw - 23.75px))'}
        {assign var=warehouseSidebarHeaderMediumSize value='min(358.75px, calc(37.5vw - 128.75px))'}
    {else}
        {assign var=warehouseTabletSize value='min(485px, calc(50vw - 25px))'}
        {assign var=warehouseSidebarHeaderMediumSize value='min(485px, calc(50vw - 165px))'}
    {/if}
{/if}

<picture class="beesblog-picture">
    {if isset($responsiveImage.srcset) && $responsiveImage.srcset}
        <source type="{$responsiveImage.modern_type|escape:'htmlall':'UTF-8'}"
                srcset="{$responsiveImage.srcset|escape:'htmlall':'UTF-8'}"
                sizes="{if $responsiveImage.loading === 'lazy'}auto, {/if}{if $warehouseSidebarHeader}(min-width: 1320px) {$warehouseSidebarHeaderWideSize|escape:'htmlall':'UTF-8'}, (min-width: 1000px) {$warehouseSidebarHeaderMediumSize|escape:'htmlall':'UTF-8'}, {else}(min-width: 1320px) {$warehouseWideSize|escape:'htmlall':'UTF-8'}, {/if}(min-width: 768px) {$warehouseTabletSize|escape:'htmlall':'UTF-8'}, calc(100vw - 30px)">
    {/if}
    <img class="img-responsive beesblog-responsive-image"
         src="{$responsiveImage.fallback_url|escape:'htmlall':'UTF-8'}"
         alt="{$responsiveAlt|escape:'htmlall':'UTF-8'}"
         width="{$responsiveImage.width|intval}"
         height="{$responsiveImage.height|intval}"
         loading="{$responsiveImage.loading|escape:'htmlall':'UTF-8'}"
         decoding="async"
         fetchpriority="{$responsiveImage.fetchpriority|escape:'htmlall':'UTF-8'}"
         itemprop="image">
</picture>
