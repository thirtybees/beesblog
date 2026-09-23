<?php
/** Apply any pending Bees Blog upgrade files and verify the registered version. */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$root = isset($argv[1]) ? rtrim($argv[1], '/\\') : '';
if (!$root || !is_file($root.'/config/config.inc.php')) {
    fwrite(STDERR, "Usage: php run_upgrade_smoke.php <thirty-bees-root>\n");
    exit(1);
}

require $root.'/config/config.inc.php';
require_once $root.'/modules/beesblog/beesblog.php';

try {
    $module = Module::getInstanceByName('beesblog');
    if (!$module instanceof BeesBlog) {
        throw new RuntimeException('The installed Bees Blog module could not be loaded.');
    }
    $databaseVersion = Db::getInstance()->getValue(
        'SELECT `version` FROM `'._DB_PREFIX_.'module` WHERE `name` = \'beesblog\''
    );
    $upgradeDescriptor = (object) [
        'installed' => true,
        'database_version' => $databaseVersion,
        'name' => $module->name,
        'version' => $module->version,
    ];
    if (Module::initUpgradeModule($upgradeDescriptor)) {
        $result = $module->runUpgradeModule();
        if (empty($result['success']) || !empty($result['number_upgrade_left'])) {
            throw new RuntimeException('Module upgrade did not complete: '.json_encode($result));
        }
    }
    $version = Db::getInstance()->getValue(
        'SELECT `version` FROM `'._DB_PREFIX_.'module` WHERE `name` = \'beesblog\''
    );
    if (version_compare((string) $version, '1.10.0', '<')) {
        throw new RuntimeException('Registered module version is still '.$version.'.');
    }
    echo "PASS: Bees Blog upgrade files are applied\n";
    echo "PASS: registered module version is {$version}\n";
    echo "RESULT: upgrade smoke checks passed\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL: '.$e->getMessage()."\n".$e->getTraceAsString()."\n");
    exit(1);
}
