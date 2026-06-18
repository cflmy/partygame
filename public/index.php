<?php

require __DIR__ . '/includes/layout.php';

pg_render_head(
    pg_site_name(),
    '暮云聚会游戏 — 即开即玩的聚会小游戏，真心话大冒险等轻量互动，朋友相聚欢乐开场。'
);
pg_render_page_open();
pg_render_header('即开即玩的聚会小游戏 · 返回 <a href="https://www.cflmy.cn/" target="_blank" rel="noopener noreferrer">作者个人主页 www.cflmy.cn</a>');
?>

<section class="info-grid">
    <div class="info-card card">
        <div class="info-icon">🎲</div>
        <div>
            <h3>游戏类型</h3>
            <p><strong>聚会破冰</strong><br>五款经典聚会游戏已上线，更多玩法持续更新</p>
        </div>
    </div>
    <div class="info-card card">
        <div class="info-icon">⚡</div>
        <div>
            <h3>即开即玩</h3>
            <p><strong>无需安装</strong><br>手机、平板、电脑浏览器均可直接使用</p>
        </div>
    </div>
    <div class="info-card card">
        <div class="info-icon">🤝</div>
        <div>
            <h3>开源共建</h3>
            <p><strong>欢迎参与</strong><br>有想法的朋友可通过 Issue / PR 一起开发</p>
        </div>
    </div>
</section>

<div class="notice card">
    <strong>暮云聚会游戏</strong> 专为朋友聚会、家庭活动与团建场景设计。
    每款游戏都力求轻量、有趣、立刻能玩——选一款，把气氛热起来。
</div>

<section class="section-panel card">
    <div class="section-panel__head">
        <h2>游戏列表</h2>
        <p>选择一款游戏，立即开始</p>
    </div>
    <div class="game-grid">
        <a class="game-card" href="/games/truth-or-dare/">
            <div class="game-card__icon">真</div>
            <h3 class="game-card__title">真心话大冒险</h3>
            <p class="game-card__desc">聚会破冰经典：转瓶抽人，真心话或大冒险，三档强度题库。</p>
            <span class="game-card__cta">开始游戏</span>
        </a>
        <a class="game-card" href="/games/never-have-i-ever/">
            <div class="game-card__icon game-card__icon--green">没</div>
            <h3 class="game-card__title">从来没有</h3>
            <p class="game-card__desc">做过就放下手指：随机陈述，记录手指数，先放完者接受惩罚。</p>
            <span class="game-card__cta">开始游戏</span>
        </a>
        <a class="game-card" href="/games/would-you-rather/">
            <div class="game-card__icon game-card__icon--blue">宁</div>
            <h3 class="game-card__title">你宁愿</h3>
            <p class="game-card__desc">两难选择题：选 A 还是 B？投票统计，看看大家怎么选。</p>
            <span class="game-card__cta">开始游戏</span>
        </a>
        <a class="game-card" href="/games/number-bomb/">
            <div class="game-card__icon game-card__icon--orange">弹</div>
            <h3 class="game-card__title">数字炸弹</h3>
            <p class="game-card__desc">猜数字踩炸弹：范围逐步缩小，踩中者接受惩罚。</p>
            <span class="game-card__cta">开始游戏</span>
        </a>
        <a class="game-card" href="/games/undercover/">
            <div class="game-card__icon game-card__icon--purple">底</div>
            <h3 class="game-card__title">谁是卧底</h3>
            <p class="game-card__desc">发词、描述、投票：找出隐藏卧底，经典推理聚会游戏。</p>
            <span class="game-card__cta">开始游戏</span>
        </a>
    </div>
</section>

<?php
pg_render_page_close();
