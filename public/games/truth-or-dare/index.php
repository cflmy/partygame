<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>真心话大冒险 — PartyGame</title>
    <meta name="description" content="PartyGame 真心话大冒险：聚会必备，随机抽人、真心话或大冒险，轻松破冰。">
    <link rel="stylesheet" href="assets/css/game.css">
</head>
<body class="tod-app">
    <div class="tod-ambient" aria-hidden="true">
        <div class="tod-glow tod-glow--a"></div>
        <div class="tod-glow tod-glow--b"></div>
    </div>

    <header class="tod-header">
        <div class="tod-header__inner">
            <a class="tod-header__back" href="/" title="返回游戏库">←</a>
            <div class="tod-header__brand">
                <h1 class="tod-header__title">真心话大冒险</h1>
                <p class="tod-header__subtitle">Truth or Dare · 聚会破冰经典</p>
            </div>
        </div>
    </header>

    <main class="tod-main">
        <section class="tod-stage">
            <!-- 设置 -->
            <div class="tod-panel is-active" id="panel-setup">
                <p class="tod-lead">添加玩家昵称，选择强度，即可开始。至少两人更有趣；不添加昵称也可直接游玩。</p>

                <div class="tod-block">
                    <p class="tod-block__label">玩家昵称</p>
                    <div class="tod-player-row">
                        <input class="tod-input" id="player-input" type="text" maxlength="16" placeholder="输入昵称后回车或点击添加">
                        <button type="button" class="tod-btn tod-btn--ghost" id="add-player-btn">添加</button>
                    </div>
                    <div class="tod-chip-list" id="player-list"></div>
                    <p class="tod-block__hint">可通过转动瓶子随机选出本轮玩家；也可不填昵称，以「玩家」代称。</p>
                </div>

                <div class="tod-block">
                    <p class="tod-block__label">题目强度</p>
                    <div class="tod-level-group" id="level-group">
                        <button type="button" class="tod-level-btn" data-level="easy">轻松</button>
                        <button type="button" class="tod-level-btn is-active" data-level="normal">标准</button>
                        <button type="button" class="tod-level-btn" data-level="bold">大胆</button>
                    </div>
                    <p class="tod-block__hint">所有题目均遵循自愿、尊重、安全原则；过火内容有权拒绝。</p>
                </div>

                <div class="tod-actions">
                    <button type="button" class="tod-btn tod-btn--primary" id="start-btn">开始游戏</button>
                </div>

                <details class="tod-rules">
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

            <!-- 转瓶 -->
            <div class="tod-panel" id="panel-spin">
                <p class="tod-lead">转动瓶子，看看本轮轮到谁。</p>
                <div class="tod-spin-area">
                    <p class="tod-current-player" id="current-player">准备开始</p>
                    <p class="tod-current-hint" id="current-hint">点击按钮转动瓶子，随机选出本轮玩家</p>
                    <div class="tod-bottle-wrap">
                        <div class="tod-bottle" id="bottle" aria-hidden="true">
                            <div class="tod-bottle__neck"></div>
                            <div class="tod-bottle__body"></div>
                            <div class="tod-bottle__label">PG</div>
                        </div>
                    </div>
                </div>
                <div class="tod-actions">
                    <button type="button" class="tod-btn tod-btn--primary" id="spin-btn">转动瓶子</button>
                    <button type="button" class="tod-btn tod-btn--ghost" id="back-setup-btn">返回设置</button>
                </div>
            </div>

            <!-- 选择 -->
            <div class="tod-panel" id="panel-choose">
                <p class="tod-lead" id="choose-player-label">当前玩家：—</p>
                <div class="tod-choice-grid">
                    <button type="button" class="tod-choice-card tod-choice-card--truth" id="truth-card">
                        <div class="tod-choice-card__volume" aria-hidden="true">
                            <div class="tod-choice-card__spine">真心话</div>
                            <div class="tod-choice-card__cover">真</div>
                        </div>
                        <span class="tod-choice-card__title">真心话</span>
                        <span class="tod-choice-card__desc">诚实回答一个问题</span>
                    </button>
                    <button type="button" class="tod-choice-card tod-choice-card--dare" id="dare-card">
                        <div class="tod-choice-card__volume" aria-hidden="true">
                            <div class="tod-choice-card__spine">大冒险</div>
                            <div class="tod-choice-card__cover">冒险</div>
                        </div>
                        <span class="tod-choice-card__title">大冒险</span>
                        <span class="tod-choice-card__desc">完成一个有趣挑战</span>
                    </button>
                </div>
                <div class="tod-actions">
                    <button type="button" class="tod-btn tod-btn--ghost" id="back-setup-btn-choose">返回设置</button>
                </div>
            </div>

            <!-- 揭晓 -->
            <div class="tod-panel" id="panel-reveal">
                <div class="tod-reveal">
                    <span class="tod-reveal__badge" id="reveal-badge">真心话</span>
                    <div class="tod-reveal__card">
                        <p class="tod-reveal__text" id="reveal-text">题目加载中…</p>
                    </div>
                    <p class="tod-reveal__player" id="reveal-player"></p>
                </div>
                <div class="tod-actions">
                    <button type="button" class="tod-btn tod-btn--primary" id="next-btn">完成了，下一位</button>
                    <button type="button" class="tod-btn tod-btn--ghost" id="swap-btn">换一个题目</button>
                </div>
            </div>
        </section>

        <p class="tod-footer-note">PartyGame · 自愿 · 尊重 · 安全 · 联系作者 pingan@cflmy.cn</p>
    </main>

    <script src="assets/js/game.js"></script>
</body>
</html>
