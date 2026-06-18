<?php

require dirname(__DIR__, 2) . '/includes/layout.php';

pg_render_head(
    '你宁愿',
    '暮云聚会游戏 · 你宁愿：两难选择题，看看大家会怎么选。',
    [
        '/games/truth-or-dare/assets/css/game.css',
        '/games/would-you-rather/assets/css/game.css',
        '/assets/css/room.css',
    ]
);
pg_render_page_open();
pg_render_header(
    'Would You Rather · 暮云聚会游戏',
    true,
    '/',
    '你宁愿'
);
?>

<section class="game-stage card">
<?php
require dirname(__DIR__, 2) . '/includes/room_panels.php';
pg_render_room_mode_picker('同步题目，各自投票统计');
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
        <p class="game-lead">选择题目强度，系统随机给出两个选项。大家讨论后投票，看看哪边更受欢迎。</p>

        <div class="game-block">
            <p class="game-block__label">题目强度</p>
            <div class="game-level-group" id="level-group">
                <button type="button" class="game-level-btn" data-level="easy">轻松</button>
                <button type="button" class="game-level-btn is-active" data-level="normal">标准</button>
                <button type="button" class="game-level-btn" data-level="bold">大胆</button>
            </div>
            <p class="game-block__hint">轻松适合暖场，标准适合朋友聚会，大胆请确保大家都能接受。</p>
        </div>

        <div class="game-actions">
            <button type="button" class="btn" id="start-btn">开始游戏</button>
        </div>

        <details class="game-rules">
            <summary>玩法说明</summary>
            <ul>
                <li>房间联机：每人用自己的手机点击 A 或 B 投出选择，实时统计票数。</li>
                <li>传手机玩：围坐同屏，可点击选项累计本机票数，或纯讨论不投票。</li>
                <li>每人思考后说出自己的选择，并简要说明理由。</li>
                <li>尊重不同选择，不强求一致，热闹就好。</li>
            </ul>
        </details>
    </div>

    <div class="game-panel" id="panel-play">
        <p class="wyr-vs-label">你宁愿……</p>

        <div class="wyr-choice-grid">
            <button type="button" class="wyr-choice wyr-choice--a" id="choice-a">
                <span class="wyr-choice__badge">选项 A</span>
                <span class="wyr-choice__text" id="option-a">加载中…</span>
                <span class="wyr-choice__picked">✓ 已选此项</span>
            </button>
            <button type="button" class="wyr-choice wyr-choice--b" id="choice-b">
                <span class="wyr-choice__badge">选项 B</span>
                <span class="wyr-choice__text" id="option-b">加载中…</span>
                <span class="wyr-choice__picked">✓ 已选此项</span>
            </button>
        </div>

        <p class="wyr-round-hint" id="round-hint">点击选项记录一票，可用于统计在场人数的选择倾向。</p>

        <div class="wyr-votes">
            <p class="wyr-votes__title">本局投票</p>
            <p class="wyr-votes__progress" id="vote-progress" hidden></p>
            <div class="wyr-votes__bar" aria-hidden="true">
                <div class="wyr-votes__bar-a" id="vote-bar-a" style="width: 50%"></div>
                <div class="wyr-votes__bar-b" id="vote-bar-b" style="width: 50%"></div>
            </div>
            <div class="wyr-votes__counts">
                <span>A：<strong id="vote-count-a">0</strong> 票</span>
                <span>B：<strong id="vote-count-b">0</strong> 票</span>
            </div>
        </div>

        <div class="game-actions">
            <button type="button" class="btn" id="next-btn">下一题</button>
            <button type="button" class="btn btn--ghost" id="swap-btn">换一题</button>
            <button type="button" class="btn btn--ghost" id="reset-votes-btn">清零投票</button>
            <button type="button" class="btn btn--ghost" id="back-setup-btn">返回设置</button>
        </div>
    </div>

    <?php pg_render_room_action_bar(); ?>
</section>

<?php
pg_render_page_close([
    '/assets/js/room-client.js',
    '/games/would-you-rather/assets/js/game.js',
    '/games/would-you-rather/assets/js/room.js',
]);
