<?php

declare(strict_types=1);

function pg_render_room_mode_picker(string $localDesc = '一台设备轮流操作'): void
{
    ?>
    <div class="game-panel is-active" id="panel-mode">
        <p class="game-lead">选择游戏方式：传手机适合围坐同屏；房间联机适合每人用自己的手机同步进度。</p>
        <div class="pg-mode-grid">
            <button type="button" class="pg-mode-card" id="mode-local-btn">
                <span class="pg-mode-card__icon">📱</span>
                <strong class="pg-mode-card__title">传手机玩</strong>
                <span class="pg-mode-card__desc"><?= htmlspecialchars($localDesc, ENT_QUOTES, 'UTF-8') ?></span>
            </button>
            <button type="button" class="pg-mode-card" id="mode-room-btn">
                <span class="pg-mode-card__icon">🌐</span>
                <strong class="pg-mode-card__title">房间联机</strong>
                <span class="pg-mode-card__desc">创建或加入房间，多设备实时同步</span>
            </button>
        </div>
    </div>
    <?php
}

function pg_render_room_panels(): void
{
    ?>
    <div class="game-panel" id="panel-room-entry">
        <div class="pg-room-tabs">
            <button type="button" class="pg-room-tab is-active" data-tab="create">创建房间</button>
            <button type="button" class="pg-room-tab" data-tab="join">加入房间</button>
        </div>
        <div class="pg-room-tab-panel is-active" id="room-tab-create">
            <div class="game-block">
                <p class="game-block__label">你的昵称</p>
                <input class="game-input" id="room-create-name" type="text" maxlength="16" placeholder="房主昵称">
            </div>
            <div class="game-actions">
                <button type="button" class="btn" id="room-create-btn">创建房间</button>
            </div>
        </div>
        <div class="pg-room-tab-panel" id="room-tab-join">
            <div class="game-block">
                <p class="game-block__label">房间号</p>
                <input class="game-input" id="room-join-code" type="text" maxlength="6" inputmode="numeric" placeholder="6 位数字">
            </div>
            <div class="game-block">
                <p class="game-block__label">你的昵称</p>
                <input class="game-input" id="room-join-name" type="text" maxlength="16" placeholder="输入昵称">
            </div>
            <label class="pg-spectate-check">
                <input type="checkbox" id="room-join-spectate">
                <span>以观战身份加入</span>
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
        <div class="pg-room-code-box">
            <p class="game-block__label">房间号</p>
            <p class="pg-room-code-box__code" id="room-display-code">000000</p>
            <button type="button" class="btn btn--ghost" id="room-copy-code-btn">复制房间号</button>
        </div>
        <p class="game-block__hint" id="room-lobby-hint">等待玩家加入…</p>
        <p class="pg-wait-banner" id="room-lobby-wait" hidden>等待房主开始游戏…</p>
        <ul id="room-player-list"></ul>
        <div class="game-actions">
            <button type="button" class="btn" id="room-start-btn" hidden>开始游戏</button>
            <button type="button" class="btn btn--ghost" id="room-leave-btn">离开房间</button>
        </div>
    </div>

    <div class="game-panel" id="panel-room-spectate">
        <div class="uc-phase-card">
            <h2 class="uc-phase-card__title" id="room-watch-title">观战中</h2>
            <p class="uc-phase-card__text" id="room-watch-text">正在同步…</p>
        </div>
        <ul id="room-watch-list"></ul>
    </div>

    <div class="game-panel" id="panel-room-removed">
        <div class="pg-removed-card">
            <h2 class="pg-removed-card__title">你已离开房间</h2>
            <p class="pg-removed-card__text" id="room-removed-text">你已被房主移出房间。</p>
            <div class="game-actions">
                <button type="button" class="btn" id="room-removed-rejoin-btn">重新加入</button>
                <button type="button" class="btn btn--ghost" id="room-removed-home-btn">返回</button>
            </div>
        </div>
    </div>
    <?php
}

function pg_render_room_tabs_script(): void
{
    ?>
    <script>
    document.querySelectorAll('.pg-room-tab').forEach(function (tab) {
      tab.addEventListener('click', function () {
        var name = tab.getAttribute('data-tab');
        document.querySelectorAll('.pg-room-tab').forEach(function (t) {
          t.classList.toggle('is-active', t === tab);
        });
        document.getElementById('room-tab-create').style.display = name === 'create' ? '' : 'none';
        document.getElementById('room-tab-join').style.display = name === 'join' ? '' : 'none';
      });
    });
    </script>
    <?php
}
