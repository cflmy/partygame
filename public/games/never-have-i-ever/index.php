<?php

require dirname(__DIR__, 2) . '/includes/layout.php';

pg_render_head(
    '从来没有',
    '暮云聚会游戏 · 从来没有：做过就放下手指，经典聚会破冰游戏。',
    [
        '/games/truth-or-dare/assets/css/game.css',
        '/games/never-have-i-ever/assets/css/game.css',
        '/assets/css/room.css',
    ]
);
pg_render_page_open();
pg_render_header(
    'Never Have I Ever · 暮云聚会游戏',
    true,
    '/',
    '从来没有'
);
?>

<section class="game-stage card">
<?php
require dirname(__DIR__, 2) . '/includes/room_panels.php';
pg_render_room_mode_picker('同步陈述，做过就放下手指');
pg_render_room_panels(<<<'HTML'
            <div class="game-block">
                <p class="game-block__label">题目强度</p>
                <div class="game-level-group" id="room-create-level-group">
                    <button type="button" class="game-level-btn" data-level="easy">轻松</button>
                    <button type="button" class="game-level-btn is-active" data-level="normal">标准</button>
                    <button type="button" class="game-level-btn" data-level="bold">大胆</button>
                </div>
                <p class="game-block__hint">创建后本局题目均按此强度抽取</p>
            </div>
HTML, <<<'HTML'
            <div class="game-block" id="room-lobby-settings">
                <p class="game-block__label">题目强度</p>
                <div class="game-level-group" id="room-lobby-level-group">
                    <button type="button" class="game-level-btn" data-level="easy">轻松</button>
                    <button type="button" class="game-level-btn is-active" data-level="normal">标准</button>
                    <button type="button" class="game-level-btn" data-level="bold">大胆</button>
                </div>
                <p class="game-block__hint" id="room-lobby-level-hint">本局题目均按此强度抽取</p>
            </div>
HTML);
?>
    <div class="game-panel" id="panel-setup">
        <p class="game-lead">添加玩家昵称并选择强度，系统随机出题。做过的人放下手指，先放完者接受惩罚。</p>

        <div class="game-block">
            <p class="game-block__label">玩家昵称</p>
            <div class="game-player-row">
                <input class="game-input" id="player-input" type="text" maxlength="16" placeholder="输入昵称后回车或点击添加">
                <button type="button" class="btn btn--ghost" id="add-player-btn">添加</button>
            </div>
            <div class="game-chip-list" id="player-list"></div>
            <p class="game-block__hint">不添加昵称也可游玩，仅展示题目；添加后可记录手指数量。</p>
        </div>

        <div class="game-block">
            <p class="game-block__label">初始手指数</p>
            <div class="nhie-finger-picker" id="finger-group">
                <button type="button" class="nhie-finger-btn is-active" data-fingers="3">3 根</button>
                <button type="button" class="nhie-finger-btn" data-fingers="5">5 根</button>
                <button type="button" class="nhie-finger-btn" data-fingers="10">10 根</button>
            </div>
        </div>

        <div class="game-block">
            <p class="game-block__label">题目强度</p>
            <div class="game-level-group" id="level-group">
                <button type="button" class="game-level-btn" data-level="easy">轻松</button>
                <button type="button" class="game-level-btn is-active" data-level="normal">标准</button>
                <button type="button" class="game-level-btn" data-level="bold">大胆</button>
            </div>
        </div>

        <div class="game-actions">
            <button type="button" class="btn" id="start-btn">开始游戏</button>
        </div>

        <details class="game-rules">
            <summary>玩法说明</summary>
            <ul>
                <li>屏幕出现「我从来没有……」的陈述。</li>
                <li>做过这件事的人放下 1 根手指（或喝一口 / 接受小惩罚）。</li>
                <li>没做过的人什么都不做，进入下一条。</li>
                <li>手指先放完的人接受本轮惩罚，游戏可继续或换惩罚规则。</li>
                <li>请自愿参与，尊重彼此边界，避免敏感或危险内容。</li>
            </ul>
        </details>
    </div>

    <div class="game-panel" id="panel-play">
        <div class="nhie-statement-card">
            <span class="nhie-statement-card__label">从来没有</span>
            <p class="nhie-statement-card__text" id="statement-text">题目加载中…</p>
        </div>

        <p class="nhie-round-hint" id="round-hint"></p>

        <div class="nhie-players-section is-hidden" id="players-section">
            <div class="nhie-players-grid" id="players-grid"></div>
        </div>

        <div class="game-actions">
            <button type="button" class="btn" id="next-btn">下一条</button>
            <button type="button" class="btn btn--ghost" id="swap-btn">换一条</button>
            <button type="button" class="btn btn--ghost" id="back-setup-btn">返回设置</button>
        </div>
    </div>

    <?php pg_render_room_action_bar(); ?>
</section>

<?php
pg_render_page_close([
    '/assets/js/room-client.js',
    '/games/never-have-i-ever/assets/js/game.js',
    '/games/never-have-i-ever/assets/js/room.js',
]);
