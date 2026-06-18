<?php

require dirname(__DIR__, 2) . '/includes/layout.php';

pg_render_head(
    '谁是卧底',
    '暮云聚会游戏 · 谁是卧底：传手机或房间联机，发词、描述、投票，找出隐藏卧底。',
    [
        '/games/truth-or-dare/assets/css/game.css',
        '/games/undercover/assets/css/game.css',
        '/assets/css/room.css',
    ]
);
pg_render_page_open();
pg_render_header(
    'Undercover · 暮云聚会游戏',
    true,
    '/',
    '谁是卧底'
);
?>

<section class="game-stage card">
    <div class="game-panel is-active" id="panel-mode">
        <p class="game-lead">选择游戏方式：只有一台设备可以传手机玩；每人有自己的手机可以创建或加入房间联机。</p>
        <div class="uc-mode-grid">
            <button type="button" class="uc-mode-card" id="mode-local-btn">
                <span class="uc-mode-card__icon">📱</span>
                <strong class="uc-mode-card__title">传手机玩</strong>
                <span class="uc-mode-card__desc">一台设备轮流查看词语，适合线下围坐</span>
            </button>
            <button type="button" class="uc-mode-card" id="mode-room-btn">
                <span class="uc-mode-card__icon">🌐</span>
                <strong class="uc-mode-card__title">房间联机</strong>
                <span class="uc-mode-card__desc">输入房间号加入，各看各的词，多设备同步</span>
            </button>
        </div>
    </div>

    <div class="game-panel" id="panel-setup">
        <p class="game-lead">至少 4 人参与。系统秘密分配身份，查看词语时不会显示身份——卧底也只会看到自己的词。</p>

        <div class="game-block">
            <p class="game-block__label">玩家昵称</p>
            <div class="game-player-row">
                <input class="game-input" id="player-input" type="text" maxlength="16" placeholder="输入昵称后回车或点击添加">
                <button type="button" class="btn btn--ghost" id="add-player-btn">添加</button>
            </div>
            <div class="game-chip-list" id="player-list"></div>
            <p class="game-block__hint">建议 4~12 人；8 人及以上可开启 2 个卧底。</p>
        </div>

        <div class="game-block">
            <p class="game-block__label">卧底人数</p>
            <div class="uc-role-picker" id="undercover-group">
                <button type="button" class="uc-role-btn is-active" data-count="1">1 个卧底</button>
                <button type="button" class="uc-role-btn" data-count="2">2 个卧底</button>
            </div>
        </div>

        <div class="game-actions">
            <button type="button" class="btn" id="start-btn">开始游戏</button>
            <button type="button" class="btn btn--ghost" id="back-mode-local-btn">返回选择</button>
        </div>

        <details class="game-rules">
            <summary>玩法说明</summary>
            <ul>
                <li>查看词语时界面相同，不会显示身份；卧底也只会看到自己的词，不知道自己是不是卧底。</li>
                <li>大多数人拿到「平民词」，少数人拿到相近的「卧底词」——只有出局或结束时才会公开身份。</li>
                <li>轮流用一句话描述自己的词，不能直接说出词语本身。</li>
                <li>每轮描述结束后投票，点选你认为最可疑的玩家出局。</li>
                <li>卧底全部出局则平民胜；存活卧底数 ≥ 存活平民数则卧底胜。</li>
                <li>传手机查看词语时，务必防止其他人偷看。</li>
            </ul>
        </details>
    </div>

    <div class="game-panel" id="panel-reveal">
        <div class="uc-word-card">
            <p class="uc-word-card__handoff" id="reveal-handoff">请把设备交给玩家</p>
            <p class="uc-word-card__name" id="reveal-name">—</p>
            <div id="reveal-hidden" class="uc-word-hidden">🎭</div>
            <div id="reveal-word-block" hidden>
                <p class="uc-word-card__word" id="reveal-word">—</p>
                <p class="uc-word-card__hint">请记住词语，勿让其他人看到</p>
            </div>
        </div>
        <div class="game-actions">
            <button type="button" class="btn btn--ghost" id="show-word-btn">查看词语</button>
            <button type="button" class="btn" id="confirm-reveal-btn" disabled>记住了，下一位</button>
        </div>
    </div>

    <div class="game-panel" id="panel-describe">
        <div class="uc-phase-card">
            <h2 class="uc-phase-card__title">描述阶段 · 第 <span id="describe-round">1</span> 轮</h2>
            <p class="uc-phase-card__text">存活 <strong id="describe-alive">0</strong> 人。请按顺序用一句话描述你的词语，不要直接说出词语。描述完成后开始投票。</p>
        </div>
        <div class="uc-eliminated" id="describe-eliminated" hidden></div>
        <div class="game-actions">
            <button type="button" class="btn" id="begin-vote-btn">描述完成，开始投票</button>
            <button type="button" class="btn btn--ghost" id="back-setup-describe-btn">返回设置</button>
        </div>
    </div>

    <div class="game-panel" id="panel-vote">
        <div class="uc-phase-card">
            <h2 class="uc-phase-card__title">投票阶段</h2>
            <p class="uc-phase-card__text">点击你认为最像卧底的玩家，将其投票出局。</p>
        </div>
        <div class="uc-eliminated" id="eliminated-box" hidden></div>
        <div class="uc-player-grid" id="vote-grid"></div>
        <div class="game-actions">
            <button type="button" class="btn btn--ghost" id="back-setup-vote-btn">返回设置</button>
        </div>
    </div>

    <div class="game-panel" id="panel-ended">
        <div class="uc-result-card" id="ended-card">
            <h2 class="uc-result-card__title" id="ended-title">游戏结束</h2>
            <p class="uc-result-card__text" id="ended-text"></p>
        </div>
        <div class="game-actions">
            <button type="button" class="btn" id="restart-btn">再来一局</button>
            <button type="button" class="btn btn--ghost" id="back-setup-ended-btn">返回设置</button>
        </div>
    </div>

    <!-- Room mode panels -->
    <div class="game-panel" id="panel-room-entry">
        <div class="uc-room-tabs">
            <button type="button" class="uc-room-tab is-active" data-tab="create">创建房间</button>
            <button type="button" class="uc-room-tab" data-tab="join">加入房间</button>
        </div>

        <div class="uc-room-tab-panel is-active" id="room-tab-create">
            <div class="game-block">
                <p class="game-block__label">你的昵称</p>
                <input class="game-input" id="room-create-name" type="text" maxlength="16" placeholder="房主昵称">
            </div>
            <div class="game-block">
                <p class="game-block__label">卧底人数</p>
                <div class="uc-role-picker" id="room-undercover-group">
                    <button type="button" class="uc-role-btn is-active" data-count="1">1 个卧底</button>
                    <button type="button" class="uc-role-btn" data-count="2">2 个卧底</button>
                </div>
            </div>
            <div class="game-actions">
                <button type="button" class="btn" id="room-create-btn">创建房间</button>
            </div>
        </div>

        <div class="uc-room-tab-panel" id="room-tab-join">
            <div class="game-block">
                <p class="game-block__label">房间号</p>
                <input class="game-input uc-room-code-input" id="room-join-code" type="text" maxlength="6" inputmode="numeric" placeholder="6 位数字">
            </div>
            <div class="game-block">
                <p class="game-block__label">你的昵称</p>
                <input class="game-input" id="room-join-name" type="text" maxlength="16" placeholder="输入昵称">
            </div>
            <label class="uc-spectate-check">
                <input type="checkbox" id="room-join-spectate">
                <span>以观战身份加入（不参与游戏，仅观看）</span>
            </label>
            <div class="game-actions">
                <button type="button" class="btn" id="room-join-btn">加入房间</button>
            </div>
        </div>

        <div class="game-actions">
            <button type="button" class="btn btn--ghost" id="back-mode-room-btn">返回选择</button>
        </div>
    </div>

    <div class="game-panel" id="panel-room-lobby">
        <div class="pg-room-role-banner pg-room-role-banner--host" id="room-lobby-banner-host" hidden>
            <span class="pg-room-role-banner__icon" aria-hidden="true">👑</span>
            <div class="pg-room-role-banner__body">
                <strong class="pg-room-role-banner__title">你是房主</strong>
                <span class="pg-room-role-banner__desc">分享房间号邀请玩家，至少 4 人后可开始</span>
            </div>
        </div>
        <div class="pg-room-role-banner pg-room-role-banner--player" id="room-lobby-banner-player" hidden>
            <span class="pg-room-role-banner__icon" aria-hidden="true">🎮</span>
            <div class="pg-room-role-banner__body">
                <strong class="pg-room-role-banner__title">你已加入</strong>
                <span class="pg-room-role-banner__desc">请等待房主开始游戏</span>
            </div>
        </div>
        <div class="uc-room-code-box">
            <p class="uc-room-code-box__label">房间号</p>
            <p class="uc-room-code-box__code" id="room-display-code">000000</p>
            <button type="button" class="btn btn--ghost btn--sm" id="room-copy-code-btn">复制房间号</button>
        </div>
        <p class="uc-room-hint" id="room-lobby-hint">等待玩家加入… 至少 4 人后可开始</p>
        <ul class="uc-room-player-list" id="room-player-list"></ul>
        <div class="game-block" id="room-host-settings" hidden>
            <p class="game-block__label">卧底人数</p>
            <div class="uc-role-picker" id="room-lobby-undercover-group">
                <button type="button" class="uc-role-btn is-active" data-count="1">1 个卧底</button>
                <button type="button" class="uc-role-btn" data-count="2">2 个卧底</button>
            </div>
        </div>
        <div class="game-actions pg-room-host-actions" id="room-host-start-wrap" hidden>
            <button type="button" class="btn" id="room-start-btn">开始游戏</button>
        </div>
    </div>

    <div class="game-panel" id="panel-room-word">
        <div class="uc-word-card">
            <p class="uc-word-card__handoff">请查看你的词语</p>
            <p class="uc-word-card__name" id="room-my-name">—</p>
            <div id="room-word-hidden" class="uc-word-hidden">🎭</div>
            <div id="room-word-block" hidden>
                <p class="uc-word-card__word" id="room-my-word">—</p>
                <p class="uc-word-card__hint">请记住词语，勿让其他人看到</p>
            </div>
        </div>
        <p class="uc-room-sync" id="room-word-sync">等待其他玩家查看词语…</p>
        <div class="game-actions">
            <button type="button" class="btn btn--ghost" id="room-show-word-btn">查看词语</button>
            <button type="button" class="btn btn--ghost" id="room-rec-word-btn" hidden>再次查看词语</button>
        </div>
    </div>

    <div class="game-panel" id="panel-room-describe">
        <div class="uc-phase-card">
            <h2 class="uc-phase-card__title">描述阶段 · 第 <span id="room-describe-round">1</span> 轮</h2>
            <p class="uc-phase-card__text">存活 <strong id="room-describe-alive">0</strong> 人。请口头描述你的词语，不要直接说出词语。</p>
        </div>
        <div class="uc-eliminated" id="room-describe-eliminated" hidden></div>
        <div class="game-actions">
            <button type="button" class="btn" id="room-begin-vote-btn" hidden>描述完成，开始投票</button>
            <button type="button" class="btn btn--ghost" id="room-rec-word-describe-btn">查看我的词语</button>
        </div>
    </div>

    <div class="game-panel" id="panel-room-vote">
        <div class="uc-phase-card">
            <h2 class="uc-phase-card__title">投票阶段</h2>
            <p class="uc-phase-card__text" id="room-vote-hint">点击你认为最可疑的玩家</p>
            <p class="uc-vote-timer" id="room-vote-timer" hidden>剩余 <strong id="room-vote-seconds">60</strong> 秒</p>
        </div>
        <div class="uc-eliminated" id="room-vote-status" hidden></div>
        <div class="uc-player-grid" id="room-vote-grid"></div>
    </div>

    <div class="game-panel" id="panel-room-spectate">
        <div class="uc-phase-card">
            <h2 class="uc-phase-card__title" id="room-watch-title">观战中</h2>
            <p class="uc-phase-card__text" id="room-watch-text">正在同步房间状态…</p>
            <p class="uc-vote-timer" id="room-watch-vote-timer" hidden>投票剩余 <strong id="room-watch-vote-seconds">60</strong> 秒</p>
        </div>
        <ul class="uc-room-player-list" id="room-watch-list"></ul>
        <div class="uc-eliminated" id="room-watch-eliminated" hidden></div>
        <div class="game-actions">
            <button type="button" class="btn btn--ghost" id="room-watch-leave-btn" hidden>离开观战</button>
        </div>
    </div>

    <div class="game-panel" id="panel-room-removed">
        <div class="uc-removed-card">
            <h2 class="uc-removed-card__title">你已离开房间</h2>
            <p class="uc-removed-card__text" id="room-removed-text">你已被房主移出房间。</p>
            <div class="game-actions">
                <button type="button" class="btn" id="room-removed-rejoin-btn">重新加入</button>
                <button type="button" class="btn btn--ghost" id="room-removed-home-btn">返回模式选择</button>
            </div>
        </div>
    </div>

    <div class="game-panel" id="panel-room-ended">
        <div class="uc-result-card" id="room-ended-card">
            <h2 class="uc-result-card__title" id="room-ended-title">游戏结束</h2>
            <p class="uc-result-card__text" id="room-ended-text"></p>
        </div>
    </div>

    <?php
    require dirname(__DIR__, 2) . '/includes/room_panels.php';
    pg_render_room_action_bar();
    ?>
</section>

<?php
pg_render_page_close([
    '/games/undercover/assets/js/game.js',
    '/games/undercover/assets/js/room.js',
]);
