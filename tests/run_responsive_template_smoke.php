<?php
/** Render the module and packaged-theme picture partials through Smarty. */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$root = isset($argv[1]) ? rtrim($argv[1], '/\\') : '';
if (!$root || !is_file($root.'/config/config.inc.php')) {
    fwrite(STDERR, "Usage: php run_responsive_template_smoke.php <thirty-bees-root>\n");
    exit(1);
}

require $root.'/config/config.inc.php';
require_once $root.'/modules/beesblog/beesblog.php';

function assertResponsiveTemplate($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo "PASS: {$message}\n";
}

try {
    $data = [
        'modern_type' => 'image/avif',
        'srcset' => '/320.avif 320w, /768.avif 768w',
        'fallback_url' => '/fallback-768.jpg',
        'width' => 768,
        'height' => 480,
        'loading' => 'eager',
        'fetchpriority' => 'high',
    ];
    Context::getContext()->smarty->assign([
        'responsiveImage' => $data,
        'responsiveAlt' => 'Responsive test',
        'responsiveContext' => 'full',
        'beesblogImageLayout' => [
            'rendered_sidebar_count' => 0,
            'enabled_sidebar_count' => 0,
        ],
        'warehouse_vars' => [
            'header_style' => 0,
        ],
    ]);
    $moduleRoot = $root.'/modules/beesblog/';
    $partials = [
        'module' => [
            'path' => $moduleRoot.'views/templates/front/responsive_image.tpl',
            'sizes' => '(min-width: 1200px) 1140px, (min-width: 992px) 940px, (min-width: 768px) 720px, calc(100vw - 30px)',
        ],
        'Niara package' => [
            'path' => $moduleRoot.'themes/niara/modules/beesblog/views/templates/front/responsive_image.tpl',
            'sizes' => '(min-width: 1200px) 1140px, (min-width: 992px) 940px, (min-width: 768px) 720px, calc(100vw - 30px)',
        ],
        'Community package' => [
            'path' => $moduleRoot.'themes/community-theme-default/modules/beesblog/views/templates/front/responsive_image.tpl',
            'sizes' => '(min-width: 1200px) 1140px, (min-width: 992px) 940px, (min-width: 768px) 720px, calc(100vw - 30px)',
        ],
        'Warehouse package' => [
            'path' => $moduleRoot.'themes/warehouse/modules/beesblog/views/templates/front/responsive_image.tpl',
            'sizes' => '(min-width: 1320px) 1240px, (min-width: 768px) min(990px, calc(100vw - 30px)), calc(100vw - 30px)',
        ],
    ];
    foreach ($partials as $label => $partial) {
        $html = Context::getContext()->smarty->fetch($partial['path']);
        assertResponsiveTemplate(strpos($html, '<picture') !== false, $label.' renders a picture element');
        assertResponsiveTemplate(strpos($html, 'srcset="/320.avif 320w, /768.avif 768w"') !== false, $label.' renders width descriptors');
        assertResponsiveTemplate(strpos($html, 'sizes="'.$partial['sizes'].'"') !== false, $label.' renders its theme slot sizes');
        assertResponsiveTemplate(strpos($html, 'src="/fallback-768.jpg"') !== false, $label.' renders the legacy fallback');
        assertResponsiveTemplate(strpos($html, 'width="768"') !== false && strpos($html, 'height="480"') !== false, $label.' renders intrinsic dimensions');
        assertResponsiveTemplate(strpos($html, 'fetchpriority="high"') !== false, $label.' renders priority metadata');
    }

    $data['loading'] = 'lazy';
    $data['fetchpriority'] = 'auto';
    Context::getContext()->smarty->assign('responsiveImage', $data);
    $lazyHtml = Context::getContext()->smarty->fetch($partials['module']['path']);
    assertResponsiveTemplate(
        strpos($lazyHtml, 'sizes="auto, '.$partials['module']['sizes'].'"') !== false,
        'lazy images use automatic slot sizing with the theme calculation as fallback'
    );

    Context::getContext()->smarty->assign([
        'responsiveImage' => array_merge($data, ['loading' => 'eager']),
        'responsiveContext' => 'card',
        'beesblogImageLayout' => [
            'rendered_sidebar_count' => 1,
            'enabled_sidebar_count' => 2,
        ],
        'warehouse_vars' => [
            'header_style' => 1,
        ],
    ]);
    $warehouseHtml = Context::getContext()->smarty->fetch($partials['Warehouse package']['path']);
    $warehouseSizes = '(min-width: 1320px) min(358px, calc(30vw - 107px)), '.
        '(min-width: 1000px) min(358.75px, calc(37.5vw - 128.75px)), '.
        '(min-width: 768px) min(358.75px, calc(37.5vw - 23.75px)), calc(100vw - 30px)';
    assertResponsiveTemplate(
        strpos($warehouseHtml, 'sizes="'.$warehouseSizes.'"') !== false,
        'Warehouse card sizes include its rendered columns and optional sidebar header'
    );

    echo "RESULT: responsive template smoke checks passed\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL: '.$e->getMessage()."\n".$e->getTraceAsString()."\n");
    exit(1);
}
