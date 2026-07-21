<?php
/**
 * Shared helpers for the standalone Bees Blog integration scripts.
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

use BeesBlogModule\BeesBlogCategory;
use BeesBlogModule\BeesBlogPost;

function assertTest($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo "PASS: {$message}\n";
}

function columnExistsForTest($table, $column)
{
    return (bool) Db::getInstance()->getValue(
        'SELECT 1 FROM `information_schema`.`columns` WHERE `table_schema` = DATABASE()'.
        ' AND `table_name` = \''.pSQL(_DB_PREFIX_.$table).'\''.
        ' AND `column_name` = \''.pSQL($column).'\''
    );
}

function tableExistsForTest($table)
{
    return (bool) Db::getInstance()->getValue(
        'SELECT 1 FROM `information_schema`.`tables` WHERE `table_schema` = DATABASE()'.
        ' AND `table_name` = \''.pSQL(_DB_PREFIX_.$table).'\''
    );
}

function primaryColumnsForTest($table)
{
    $columns = [];
    foreach ((array) Db::getInstance()->executeS(
        'SHOW INDEX FROM `'._DB_PREFIX_.bqSQL($table).'` WHERE `Key_name` = \'PRIMARY\''
    ) as $row) {
        $columns[(int) $row['Seq_in_index']] = $row['Column_name'];
    }
    ksort($columns);

    return array_values($columns);
}

function langValuesForTest($prefix)
{
    $values = [];
    foreach (Language::getLanguages(false, false, true) as $idLang) {
        $values[(int) $idLang] = $prefix.'-'.$idLang;
    }

    return $values;
}

function addCategoryForTest($slug, array $shopIds)
{
    $category = new BeesBlogCategory();
    $category->id_parent = 0;
    $category->position = 0;
    $category->active = true;
    $category->title = langValuesForTest('Category '.$slug);
    $category->description = langValuesForTest('Description '.$slug);
    $category->link_rewrite = langValuesForTest($slug);
    $category->meta_title = langValuesForTest('Meta '.$slug);
    $category->meta_description = langValuesForTest('Description '.$slug);
    $category->meta_keywords = langValuesForTest('keyword');
    $category->id_shop_list = $shopIds;
    assertTest($category->add(), 'category can be created in the requested context');

    return $category;
}

function addPostForTest($slug, $categoryId, array $shopIds)
{
    $post = new BeesBlogPost();
    $post->active = true;
    $post->comments_enabled = true;
    $post->published = date('Y-m-d H:i:s');
    $post->id_category = (int) $categoryId;
    $post->id_employee = (int) Db::getInstance()->getValue(
        'SELECT `id_employee` FROM `'._DB_PREFIX_.'employee` ORDER BY `id_employee` ASC'
    );
    $post->position = 0;
    $post->post_type = '0';
    $post->viewed = 0;
    $post->title = langValuesForTest('Post '.$slug);
    $post->content = langValuesForTest('Content '.$slug);
    $post->link_rewrite = langValuesForTest($slug);
    $post->meta_title = langValuesForTest('Meta '.$slug);
    $post->meta_description = langValuesForTest('Description '.$slug);
    $post->meta_keywords = langValuesForTest('keyword');
    $post->lang_active = array_fill_keys(Language::getLanguages(false, false, true), 1);
    $post->id_shop_list = $shopIds;
    assertTest($post->add(), 'post can be created in the requested context');

    return $post;
}
