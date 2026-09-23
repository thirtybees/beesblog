<?php
/**
 * Temporarily switches the current shop between bundled themes and verifies
 * the packaged Bees Blog overrides through a real local HTTP request.
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$root = isset($argv[1]) ? rtrim($argv[1], '/\\') : '';
$baseUrl = isset($argv[2]) ? rtrim($argv[2], '/') : '';
if (!$root || !$baseUrl || !is_file($root.'/config/config.inc.php')) {
    fwrite(STDERR, "Usage: php run_theme_override_frontend_smoke.php <thirty-bees-root> <base-url>\n");
    exit(1);
}

require $root.'/config/config.inc.php';

function assertThemeFrontend($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo "PASS: {$message}\n";
}

$db = Db::getInstance();
$idShop = (int) Context::getContext()->shop->id;
$originalTheme = (int) $db->getValue(
    'SELECT `id_theme` FROM `'._DB_PREFIX_.'shop` WHERE `id_shop` = '.$idShop
);
$installedForTest = [];

try {
    foreach (['niara', 'community-theme-default', 'warehouse'] as $directory) {
        $theme = Theme::getByDirectory($directory);
        if (!$theme) {
            $theme = Theme::installFromDir(_PS_ALL_THEMES_DIR_.$directory);
            if (
                $directory === 'warehouse'
                && !$theme instanceof Theme
                && is_dir(_PS_ALL_THEMES_DIR_.$directory)
            ) {
                // Warehouse still ships the legacy PrestaShop 1.6 config.xml
                // format, which current thirty bees intentionally cannot import.
                // Register the existing directory only for this smoke run.
                $theme = new Theme();
                $theme->name = 'Bees Blog smoke: '.$directory;
                $theme->directory = $directory;
                $theme->responsive = true;
                $theme->default_left_column = true;
                $theme->default_right_column = false;
                $theme->product_per_page = (int) Configuration::get('PS_PRODUCTS_PER_PAGE');
                if (!$theme->add()) {
                    $theme = false;
                }
            }
            if ($theme instanceof Theme) {
                $installedForTest[] = $theme;
            }
        }
        $idTheme = $theme instanceof Theme ? (int) $theme->id : 0;
        assertThemeFrontend($idTheme > 0, $directory.' is installed');
        assertThemeFrontend(
            $db->update('shop', ['id_theme' => $idTheme], '`id_shop` = '.$idShop),
            $directory.' can be activated for the smoke request'
        );
        Shop::cacheShops(true);
        Tools::clearSmartyCache();

        $html = @file_get_contents($baseUrl.'/index.php?fc=module&module=beesblog&controller=category');
        assertThemeFrontend(is_string($html) && $html !== '', $directory.' blog category returns HTML');
        assertThemeFrontend(strpos($html, 'class="beesblog-picture"') !== false, $directory.' uses the packaged picture override');
        assertThemeFrontend(strpos($html, 'srcset=') !== false && strpos($html, ' 320w') !== false, $directory.' renders responsive width descriptors');
        assertThemeFrontend(strpos($html, 'fallback-') !== false, $directory.' renders the legacy fallback URL');
        assertThemeFrontend(strpos($html, 'fetchpriority="high"') !== false, $directory.' prioritizes one above-the-fold blog image');
    }

    echo "RESULT: bundled storefront override checks passed\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL: '.$e->getMessage()."\n".$e->getTraceAsString()."\n");
    $exitCode = 1;
} finally {
    $db->update('shop', ['id_theme' => $originalTheme], '`id_shop` = '.$idShop);
    Shop::cacheShops(true);
    foreach ($installedForTest as $theme) {
        $theme->delete();
    }
    Tools::clearSmartyCache();
}

exit(isset($exitCode) ? $exitCode : 0);
