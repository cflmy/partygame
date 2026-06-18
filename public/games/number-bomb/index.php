<?php

require dirname(__DIR__, 2) . '/includes/layout.php';

pg_render_head(
    '数字炸弹',
    '暮云聚会游戏 · 数字炸弹：猜数字踩炸弹，紧张刺激的聚会小游戏。',
    [
        '/games/truth-or-dare/assets/css/game.css',
        '/games/number-bomb/assets/css/game.css',
        '/assets/css/room.css',
    ]
);
pg_render_page_open();
pg_render_header(
    'Number Bomb · 暮云聚会游戏',
    true,
    '/',
    '数字炸弹'
);
?>

<section class="game-stage card">
<?php
require dirname(__DIR__, 2) . '/includes/room_panels.php';
pg_render_room_mode_picker('轮流猜数，踩中炸弹者出局');
pg_render_room_panels();
?>
    <div class="game-panel" id="panel-setup">
        <p class="game-lead">设定数字范围，系统随机藏一颗炸弹。轮流猜数，缩小范围，踩中炸弹者接受惩罚。</p>

        <div class="game-block">
            <p class="game-block__label">数字范围</p>
            <div class="nb-range-picker" id="range-group">
                <button type="button" class="nb-range-btn" data-min="1" data-max="50">1 ~ 50</button>
                <button type="button" class="nb-range-btn is-active" data-min="1" data-max="100">1 ~ 100</button>
                <button type="button" class="nb-range-btn" data-min="1" data-max="200">1 ~ 200</button>
            </div>
        </div>

        <div class="game-block">
            <p class="game-block__label">玩家昵称（可选）</p>
            <div class="game-player-row">
                <input class="game-input" id="player-input" type="text" maxlength="16" placeholder="输入昵称后回车或点击添加">
                <button type="button" class="btn btn--ghost" id="add-player-btn">添加</button>
            </div>
            <div class="game-chip-list" id="player-list"></div>
            <p class="game-block__hint">添加玩家后按顺序轮流猜数；不添加则默认为「玩家」轮流。</p>
        </div>

        <div class="game-actions">
            <button type="button" class="btn" id="start-btn">开始游戏</button>
        </div>

        <details class="game-rules">
            <summary>玩法说明</summary>
            <ul>
                <li>系统在设定范围内随机藏一个「炸弹数字」。</li>
                <li>玩家轮流猜一个数字，主持人/屏幕提示「太大」或「太小」。</li>
                <li>根据提示缩小范围，继续猜，直到有人踩中炸弹数字。</li>
                <li>踩中炸弹的人接受惩罚，然后可重新开始一局。</li>
                <li>范围越小越紧张——小心别成为那个「幸运儿」！</li>
            </ul>
        </details>
    </div>

    <div class="game-panel" id="panel-play">
        <div class="nb-range-display">
            <p class="nb-range-display__label">当前安全范围</p>
            <p class="nb-range-display__values"><span id="range-low">1</span> ~ <span id="range-high">100</span></p>
        </div>

        <div class="nb-range-bar" aria-hidden="true">
            <div class="nb-range-bar__active" id="range-bar" style="left: 0; width: 100%"></div>
        </div>

        <p class="nb-current-player" id="current-player">轮到 <strong>玩家</strong> 猜数字</p>

        <div class="nb-guess-row">
            <input class="game-input" id="guess-input" type="number" inputmode="numeric" placeholder="输入数字">
            <button type="button" class="btn" id="guess-btn">猜！</button>
        </div>

        <p class="nb-feedback" id="feedback">在范围内猜一个数字，小心炸弹！</p>
        <div class="nb-history" id="history"></div>

        <div class="game-actions">
            <button type="button" class="btn btn--ghost" id="back-setup-btn">返回设置</button>
        </div>
    </div>

    <div class="game-panel" id="panel-result">
        <div class="nb-result-card">
            <div class="nb-result-card__icon">💥</div>
            <h2 class="nb-result-card__title" id="result-title">踩到炸弹！</h2>
            <p class="nb-result-card__text" id="result-text"></p>
        </div>
        <div class="game-actions">
            <button type="button" class="btn" id="restart-btn">再来一局</button>
            <button type="button" class="btn btn--ghost" id="back-setup-result-btn">返回设置</button>
        </div>
    </div>

    <?php pg_render_room_action_bar(); ?>
</section>

<?php
pg_render_page_close([
    '/assets/js/room-client.js',
    '/games/number-bomb/assets/js/game.js',
    '/games/number-bomb/assets/js/room.js',
]);
