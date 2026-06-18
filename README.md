# 暮云聚会游戏

一个专注于**聚会游戏**的 Web 项目（PartyGame），适合朋友聚会、家庭活动、团建等场景，提供轻量、有趣、即开即玩的互动体验。

## 在线访问

项目部署地址：[https://partygame.cflmy.cn](https://partygame.cflmy.cn)

## 项目简介

PartyGame（暮云聚会游戏）旨在收集和实现各类适合多人参与的聚会小游戏，让线下聚会更有互动性和趣味性。项目采用 PHP 作为后端，便于部署到常见的虚拟主机环境，降低运维成本，方便快速上线与迭代。

## 技术栈

- **后端**：PHP（适配虚拟主机部署）
- **前端**：HTML / CSS / JavaScript（随项目演进补充具体框架）

## 项目结构

```
partygame/
├── public/              # Web 入口（虚拟主机网站根目录指向此处）
│   └── index.php
├── api/                 # 通用 API 接口
├── games/               # 各聚会游戏模块（独立子目录）
├── assets/              # 全局静态资源
│   ├── css/
│   └── js/
├── config/              # 配置文件
├── includes/            # 公共 PHP 工具与引导文件
├── docs/                # 项目文档
├── LICENSE              # MIT 开源协议
├── CONTRIBUTING.md      # 贡献指南
└── README.md
```

## 参与贡献

本项目为**公开仓库**，欢迎有想法的朋友一起参与开发。你可以：

- 提交 Issue 讨论新游戏玩法或功能建议
- 通过 Pull Request 贡献代码
- 完善文档、修复 Bug 或优化体验

详细说明请参阅 [CONTRIBUTING.md](CONTRIBUTING.md)。

如有合作想法或疑问，欢迎联系作者：**pingan@cflmy.cn**

## 本地开发

```bash
# 克隆仓库
git clone https://gitee.com/cflmy/partygame.git
cd partygame

# 复制配置文件（按需修改）
cp config/config.example.php config/config.php

# 使用 PHP 内置服务器进行本地预览（需已安装 PHP）
php -S localhost:8080 -t public
```

浏览器访问 <http://localhost:8080> 即可预览。

### 虚拟主机部署

1. 将仓库代码上传至主机
2. 将网站根目录（Document Root）设置为 `public/`
3. 复制 `config/config.example.php` 为 `config/config.php` 并填写实际配置
4. 绑定域名 `partygame.cflmy.cn`

## 开源协议

本项目采用 [MIT License](LICENSE) 开源，欢迎 Fork 与贡献。

## 仓库地址

- Gitee：<https://gitee.com/cflmy/partygame>

## 联系方式

- 邮箱：pingan@cflmy.cn
- 网站：<https://partygame.cflmy.cn>

---

欢迎 Star、Fork 与贡献，一起把聚会变得更有趣。
