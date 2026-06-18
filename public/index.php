<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PartyGame - 聚会游戏</title>
    <meta name="description" content="PartyGame 聚会游戏平台：真心话大冒险等轻量互动小游戏，即开即玩。">
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>
    <header class="pg-header">
        <div class="pg-header__inner">
            <h1 class="pg-header__title">PartyGame</h1>
            <p class="pg-header__lead">聚会游戏书架 — 每一款都是一段可以立刻开始的欢乐时光</p>
        </div>
    </header>

    <main class="pg-main">
        <header class="pg-shelf-header">
            <h2 class="pg-shelf-title">游戏书架</h2>
            <p class="pg-shelf-lead">选一本，进入完整游戏。更多聚会游戏持续更新中。</p>
        </header>

        <ul class="pg-game-grid" role="list">
            <li class="pg-game-item">
                <a class="pg-game-card" href="/games/truth-or-dare/">
                    <div class="pg-game-card__volume" aria-hidden="true">
                        <div class="pg-game-card__spine">
                            <span class="pg-game-card__spine-title">真心话大冒险</span>
                        </div>
                        <div class="pg-game-card__cover">
                            <span class="pg-game-card__cover-icon">真</span>
                            <div class="pg-game-card__shine"></div>
                        </div>
                    </div>
                    <div class="pg-game-card__info">
                        <h3 class="pg-game-card__title">真心话大冒险</h3>
                        <p class="pg-game-card__summary">聚会破冰经典：转瓶抽人，真心话或大冒险，三档强度题库，即开即玩。</p>
                        <span class="pg-game-card__cta">开始游戏</span>
                    </div>
                </a>
            </li>
        </ul>

        <footer class="pg-footer">
            <p>开源共建 · <a href="https://gitee.com/cflmy/partygame">Gitee 仓库</a> · 联系 <a href="mailto:pingan@cflmy.cn">pingan@cflmy.cn</a></p>
        </footer>
    </main>
</body>
</html>
