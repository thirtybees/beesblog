{if !empty($beesblogCategoriesCategories)}
    <div id="beesblogcategories_column" class="block">
        <h4 class="title_block">
            <a href="{$beesblogCategoriesBlogUrl|escape:'htmlall':'UTF-8'}" title="{l s='Blog categories' mod='beesblogcategories'}">{l s='Blog categories' mod='beesblogcategories'}</a>
        </h4>
        <div class="block_content">
            <ul>
                {foreach $beesblogCategoriesCategories as $category}
                    <li class="clearfix">
                        <div class="beesblogcategories-content">
                            <h5>
                                <a class="beesblogcategories-title" href="{$category->link|escape:'htmlall':'UTF-8'}" title="{$category->title|escape:'htmlall':'UTF-8'}">
                                    {$category->title|truncate:'20'|escape:'htmlall':'UTF-8'}
                                </a>
                            </h5>
                        </div>
                    </li>
                {/foreach}
            </ul>
            <br />
            <div>
                <a href="{$beesblogCategoriesBlogUrl|escape:'htmlall':'UTF-8'}" title="{l s='Bees blog' mod='beesblogcategories'}" class="btn btn-primary"><span>{l s='Blog' mod='beesblogcategories'} <i class="icon icon-chevron-right"></i></span></a>
            </div>
        </div>
    </div>
{/if}
