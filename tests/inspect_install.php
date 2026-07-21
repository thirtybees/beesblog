<?php

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$root = isset($argv[1]) ? rtrim($argv[1], '/\\') : '';
if (!$root || !is_file($root.'/config/config.inc.php')) {
    fwrite(STDERR, "Usage: php inspect_install.php <thirty-bees-root>\n");
    exit(1);
}

require $root.'/config/config.inc.php';

echo 'thirty bees: '._TB_VERSION_.PHP_EOL;
echo 'database: '.Db::getInstance()->getValue('SELECT VERSION()').PHP_EOL;

foreach (['shop', 'shop_group', 'lang'] as $table) {
    echo 'TABLE '.$table.PHP_EOL;
    print_r(Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.bqSQL($table).'`'));
}

echo 'TABLE module'.PHP_EOL;
print_r(Db::getInstance()->executeS(
    'SELECT `id_module`, `name`, `version`, `active` FROM `'._DB_PREFIX_.'module` WHERE `name` = \'beesblog\''
));

foreach ([
    'bees_blog_post',
    'bees_blog_post_shop',
    'bees_blog_post_lang',
    'bees_blog_category',
    'bees_blog_category_shop',
    'bees_blog_category_lang',
    'bees_blog_image',
    'bees_blog_image_type',
    'bees_blog_image_type_shop',
] as $table) {
    echo 'SCHEMA '.$table.PHP_EOL;
    $rows = Db::getInstance()->executeS('SHOW CREATE TABLE `'._DB_PREFIX_.bqSQL($table).'`');
    $row = reset($rows);
    echo ($row['Create Table'] ?? 'missing').PHP_EOL;
    echo 'COUNT '.(int) Db::getInstance()->getValue(
        'SELECT COUNT(*) FROM `'._DB_PREFIX_.bqSQL($table).'`'
    ).PHP_EOL;
}
