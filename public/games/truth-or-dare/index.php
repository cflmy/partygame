<?php

require dirname(__DIR__, 2) . '/includes/layout.php';

pg_render_head(
    '真心话大冒险',
    '暮云聚会游戏 · 真心话大冒险：转瓶抽人，真心话或大冒险，轻松破冰。',
    [
        '/games/truth-or-dare/assets/css/game.css',
        '/assets/css/room.css',
    ]
);
pg_render_page_open();
pg_render_header(
    'Truth or Dare · 暮云聚会游戏',
    true,
    '/',
    '真心话大冒险'
);
?>

<section class="game-stage card">
<?php
require dirname(__DIR__, 2) . '/includes/room_panels.php';
pg_render_room_mode_picker('转瓶抽人，真心话或大冒险');
pg_render_room_panels();
?>

    <div class="game-panel" id="panel-setup">
        <p class="game-lead">添加玩家昵称，选择强度，即可开始。至少两人更有趣；不添加昵称也可直接游玩。</p>

        <div class="game-block">
            <p class="game-block__label">玩家昵称</p>
            <div class="game-player-row">
                <input class="game-input" id="player-input" type="text" maxlength="16" placeholder="输入昵称后回车或点击添加">
                <button type="button" class="btn btn--ghost" id="add-player-btn">添加</button>
            </div>
            <div class="game-chip-list" id="player-list"></div>
            <p class="game-block__hint">可通过转动瓶子随机选出本轮玩家；也可不填昵称，以「玩家」代称。</p>
        </div>

        <div class="game-block">
            <p class="game-block__label">题目强度</p>
            <div class="game-level-group" id="level-group">
                <button type="button" class="game-level-btn" data-level="easy">轻松</button>
                <button type="button" class="game-level-btn is-active" data-level="normal">标准</button>
                <button type="button" class="game-level-btn" data-level="bold">大胆</button>
            </div>
            <p class="game-block__hint">所有题目均遵循自愿、尊重、安全原则；过火内容有权拒绝。</p>
        </div>

        <div class="game-actions">
            <button type="button" class="btn" id="start-btn">开始游戏</button>
        </div>

        <details class="game-rules">
            <summary>玩法说明</summary>
            <ul>
                <li>两人以上参与，通过转瓶子、抽签等方式随机选出本轮玩家。</li>
                <li>被选中的玩家选择「真心话」或「大冒险」。</li>
                <li>真心话需如实回答；大冒险需完成指定挑战。</li>
                <li>题目过于苛刻时可拒绝，改以唱歌、表演等小惩罚代替。</li>
                <li>请相互尊重，避免人身攻击、隐私侵犯或危险行为。</li>
            </ul>
        </details>
    </div>

    <div class="game-panel" id="panel-spin">
        <p class="game-lead">转动瓶子，看看本轮轮到谁。</p>
        <div class="game-spin-area">
            <p class="game-current-player" id="current-player">准备开始</p>
            <p class="game-current-hint" id="current-hint">点击按钮转动瓶子，随机选出本轮玩家</p>
            <div class="game-bottle-wrap">
                <div class="game-bottle" id="bottle" aria-hidden="true">
                    <div class="game-bottle__neck"></div>
                    <div class="game-bottle__body"></div>
                    <div class="game-bottle__label">云</div>
                </div>
            </div>
        </div>
        <div class="game-actions">
            <button type="button" class="btn" id="spin-btn">转动瓶子</button>
            <button type="button" class="btn btn--ghost" id="back-setup-btn">返回设置</button>
        </div>
    </div>

    <div class="game-panel" id="panel-choose">
        <p class="game-lead" id="choose-player-label">当前玩家：—</p>
        <div class="game-choice-grid">
            <button type="button" class="game-choice-card game-choice-card--truth" id="truth-card">
                <div class="game-choice-card__icon">真</div>
                <span class="game-choice-card__title">真心话</span>
                <span class="game-choice-card__desc">诚实回答一个问题</span>
            </button>
            <button type="button" class="game-choice-card game-choice-card--dare" id="dare-card">
                <div class="game-choice-card__icon">险</div>
                <span class="game-choice-card__title">大冒险</span>
                <span class="game-choice-card__desc">完成一个有趣挑战</span>
            </button>
        </div>
        <div class="game-actions">
            <button type="button" class="btn btn--ghost" id="back-setup-btn-choose">返回设置</button>
        </div>
    </div>

    <div class="game-panel" id="panel-reveal">
        <div class="game-reveal">
            <span class="game-reveal__badge" id="reveal-badge">真心话</span>
            <div class="game-reveal__card">
                <p class="game-reveal__text" id="reveal-text">题目加载中…</p>
            </div>
            <p class="game-reveal__player" id="reveal-player"></p>
        </div>
        <div class="game-actions">
            <button type="button" class="btn" id="next-btn">完成了，下一位</button>
            <button type="button" class="btn btn--ghost" id="swap-btn">换一个题目</button>
        </div>
    </div>
</section>

<?php
pg_render_page_close([
    '/assets/js/room-client.js',
    '/games/truth-or-dare/assets/js/game.js',
    '/games/truth-or-dare/assets/js/room.js',
]);
