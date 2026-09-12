# Flarum Achievement Tree

重要提醒：本项目使用ai生成,安装前请务必备份数据，并在测试环境先行测试验证。

![Release](https://img.shields.io/github/v/release/thefish12357/flarum-achievement-tree)

一个 [Flarum](https://flarum.org) 扩展:为论坛添加用户「成就树」(徽章)功能。已实现:帖子下方成就徽章行、用户资料页成就树、成就申请与审核(含驳回理由)、审核结果通知、自动解锁规则等。

## 仓库结构

| 路径                           | 说明                                                                                         |
| ------------------------------ | -------------------------------------------------------------------------------------------- |
| `extend.php` / `composer.json` | 扩展入口与包定义                                                                             |
| `src/`                         | PHP 后端(模型/控制器/序列化/规则引擎/监听器)                                                 |
| `js/`                          | 前端源码与构建产物(`js/dist` 为编译输出)                                                     |
| `migrations/`                  | 数据库迁移                                                                                   |
| `less/` `locale/`              | 样式与语言包                                                                                 |
| `flarum/`                      | 本地运行的 Flarum 站点(由 composer 安装,**不纳入版本控制**,含 vendor / storage / 配置与密钥) |

## 本地开发

1. 安装 Flarum 站点到 `flarum/`(标准 `composer create-project flarum/flarum flarum`),通过 composer 的 path 仓库或 `workbench` 挂载本扩展后启用。
2. 前端构建:`cd js && npm install && npm run build`(产物输出到 `js/dist`)。
3. 后端刷新:`php flarum migrate && php flarum cache:clear`。

## 安装

```bash
composer require thefish12357/flarum-achievement-tree
```

然后在后台启用扩展。图片统一保存在站点 `storage/achievement-tree/` 下,子目录可在扩展设置中调整;图片通过 `/achievement-images/{id}` 服务路由对外提供,不经 public 直接暴露。

## License

[MIT](LICENSE)
