# 贡献指南

感谢你对 PartyGame 的关注！本项目欢迎所有有想法的朋友一起参与开发。

## 如何参与

### 1. 讨论与建议

- 在 Gitee 上提交 [Issue](https://gitee.com/cflmy/partygame/issues)，描述你想添加的游戏玩法、功能改进或 Bug
- 对于较大的改动，建议先开 Issue 讨论方案，再开始编码

### 2. 提交代码

1. Fork 本仓库到你的 Gitee 账号
2. 克隆 Fork 后的仓库到本地
3. 创建功能分支，例如 `feature/你-undercover` 或 `fix/room-join-bug`
4. 完成修改并确保本地可正常运行
5. 提交 Pull Request 到本仓库的 `master` 分支

### 3. 提交信息规范

请使用简洁、语义清晰的提交信息，推荐格式：

```
<type>: <简短描述>

<可选的详细说明>
```

常用 `type`：

| type     | 说明           |
| -------- | -------------- |
| feat     | 新功能或新游戏 |
| fix      | Bug 修复       |
| docs     | 文档变更       |
| style    | 代码格式调整   |
| refactor | 重构           |
| test     | 测试相关       |
| chore    | 构建或杂项     |

示例：

```
feat: add undercover game room creation
fix: prevent duplicate player join in same room
docs: update deployment guide for virtual host
```

### 4. 新增游戏模块

每个聚会游戏建议放在 `games/` 目录下，以独立子目录组织，例如：

```
games/
└── undercover/
    ├── index.php      # 游戏入口
    ├── api.php        # 游戏相关接口（如有）
    └── assets/        # 游戏专属静态资源
```

请尽量保持模块独立，避免与全局逻辑强耦合，方便后续维护和扩展。

## 代码与协作约定

- 遵循项目现有代码风格与目录结构
- 新增功能请附带必要的说明或注释（仅针对非显而易见的逻辑）
- 不要提交敏感信息（密码、密钥、`.env` 等）
- 保持改动范围聚焦，一个 PR 只做一件事

## 问题反馈

如有疑问或合作意向，欢迎发邮件至 **pingan@cflmy.cn**。

再次感谢你的贡献，一起把聚会变得更有趣！
