<?php

declare(strict_types=1);

function pg_site_name(): string
{
    return '暮云聚会游戏';
}

function pg_site_url(): string
{
    return 'https://partygame.cflmy.cn';
}

function pg_logo_url(): string
{
    return '/Logo.png';
}

/**
 * @param list<string> $extraCss Absolute paths e.g. ['/assets/css/game.css']
 */
function pg_render_head(string $title, string $description = '', array $extraCss = []): void
{
    $fullTitle = $title === pg_site_name() ? $title : $title . ' · ' . pg_site_name();
    $desc = $description !== '' ? $description : '暮云聚会游戏 — 即开即玩的聚会小游戏平台。';
    ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($fullTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="<?= htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="icon" href="<?= pg_logo_url() ?>" type="image/png">
    <link rel="shortcut icon" href="<?= pg_logo_url() ?>" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+SC:wght@500;700&family=Noto+Sans+SC:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/theme.css">
    <?php foreach ($extraCss as $css): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($css, ENT_QUOTES, 'UTF-8') ?>">
    <?php endforeach; ?>
    <style>div[id^="maxkb-"] .maxkb-chat-button{bottom:auto!important;top:50%!important;transform:translateY(-50%)!important;right:10px!important;z-index:9999!important}div[id^="maxkb-"] #maxkb-chat-container{bottom:auto!important;top:50%!important;transform:translateY(-50%)!important;right:80px!important}div[id^="maxkb-"] .maxkb-tips{right:80px!important;bottom:auto!important;top:50%!important;transform:translateY(-50%)!important}div[id^="maxkb-"] .maxkb-tips .maxkb-arrow{right:-5px!important;left:auto!important;bottom:auto!important;top:50%!important;transform:translateY(-50%) rotate(45deg)!important}@media only screen and (max-width:768px){div[id^="maxkb-"] .maxkb-chat-button{top:auto!important;bottom:20px!important;right:20px!important;transform:none!important}div[id^="maxkb-"] #maxkb-chat-container{top:auto!important;bottom:0!important;right:0!important;transform:none!important}}</style>
    <script async defer src="https://ai.cflmy.cn/chat/api/embed?protocol=https&host=ai.cflmy.cn&token=1921199f49ee8ac7"></script>
</head>
<body>
    <canvas id="bgCanvas" aria-hidden="true"></canvas>
    <?php
}

function pg_render_page_open(): void
{
    echo '<div class="page">' . "\n";
}

function pg_render_page_close(array $extraScripts = []): void
{
    pg_render_footer();
    echo "</div>\n";
    echo '<script src="/assets/js/bg.js"></script>' . "\n";
    echo '<script src="/assets/js/room-guard.js"></script>' . "\n";
    foreach ($extraScripts as $src) {
        echo '<script src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '"></script>' . "\n";
    }
    echo "</body>\n</html>\n";
}

function pg_render_logo_img(): void
{
    $alt = htmlspecialchars(pg_site_name(), ENT_QUOTES, 'UTF-8');
    ?>
    <img class="site-logo-img"
         src="<?= pg_logo_url() ?>"
         alt="<?= $alt ?>"
         width="68"
         height="68"
         decoding="async">
    <?php
}

function pg_render_header(
    string $subtitle = '',
    bool $showBack = false,
    string $backHref = '/',
    ?string $title = null
): void {
    if ($subtitle === '') {
        $subtitle = '即开即玩的聚会小游戏 · 朋友相聚，欢乐开场';
    }

    $heading = $title ?? pg_site_name();
    $headerClass = 'header card' . ($showBack ? ' header--subpage' : '');
    ?>
    <header class="<?= $headerClass ?>">
        <?php if ($showBack): ?>
        <div class="header-nav">
            <a class="header-back" href="<?= htmlspecialchars($backHref, ENT_QUOTES, 'UTF-8') ?>">
                <span class="header-back__icon" aria-hidden="true">←</span>
                <span class="header-back__text">返回首页</span>
            </a>
        </div>
        <?php endif; ?>
        <div class="header-main">
            <?php pg_render_logo_img(); ?>
            <div class="header-text">
                <h1 class="brand-title"><?= htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="brand-sub"><?= $subtitle ?></p>
            </div>
        </div>
    </header>
    <?php
}

function pg_render_links_footer(): void
{
    $links = [
        ['name' => '作者个人主页', 'url' => 'www.cflmy.cn', 'href' => 'https://www.cflmy.cn/', 'class' => 'c1'],
        ['name' => '长风测速站点', 'url' => 'speedtest.cflmy.cn', 'href' => 'https://speedtest.cflmy.cn/', 'class' => 'c2'],
        ['name' => '暗恋见君论坛', 'url' => 'www.anlian.cyou', 'href' => 'https://www.anlian.cyou/', 'class' => 'c3'],
        ['name' => '创明笔记', 'url' => 'www.cflmy.com', 'href' => 'https://www.cflmy.com/', 'class' => 'c4'],
        ['name' => '长风工具箱', 'url' => 'www.cflmy.top', 'href' => 'https://www.cflmy.top/', 'class' => 'c5'],
        ['name' => '开源仓库', 'url' => 'gitee.com/cflmy/partygame', 'href' => 'https://gitee.com/cflmy/partygame', 'class' => 'c6'],
    ];
    ?>
    <footer class="footer card">
        <h2>友情链接</h2>
        <div class="link-grid">
            <?php foreach ($links as $link): ?>
            <a class="link-card <?= htmlspecialchars($link['class'], ENT_QUOTES, 'UTF-8') ?>"
               href="<?= htmlspecialchars($link['href'], ENT_QUOTES, 'UTF-8') ?>"
               target="_blank" rel="noopener noreferrer">
                <div class="name"><?= htmlspecialchars($link['name'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="url"><?= htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8') ?></div>
            </a>
            <?php endforeach; ?>
        </div>
        <div class="footer-meta">
            开源共建 · 联系 <a href="mailto:pingan@cflmy.cn">pingan@cflmy.cn</a><br>
            © <?= date('Y') ?> <?= htmlspecialchars(pg_site_name(), ENT_QUOTES, 'UTF-8') ?>
            · <a href="<?= htmlspecialchars(pg_site_url(), ENT_QUOTES, 'UTF-8') ?>">partygame.cflmy.cn</a>
        </div>
    </footer>
    <?php
}

function pg_render_footer(): void
{
    pg_render_links_footer();
}
