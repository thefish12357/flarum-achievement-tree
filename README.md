# Flarum Achievement Tree

![Release](https://img.shields.io/github/v/release/thefish12357/flarum-achievement-tree)

一个 [Flarum](https://flarum.org) 扩展:为论坛添加用户「成就树」(徽章)功能。已实现:帖子下方成就徽章行、用户资料页成就树、成就申请与审核(含驳回理由)、审核结果通知、自动解锁规则等。

## 仓库结构

| 路径                                     | 说明                                                                                         |
| ---------------------------------------- | -------------------------------------------------------------------------------------------- |
| `extension/flarum-achievement-tree/`     | 扩展源码(PHP 后端 + JS 前端 + 迁移/语言包)                                                   |
| `docker-compose.yml` / `nginx/` / `php/` | 本地开发环境(Docker)                                                                         |
| `flarum/`                                | 本地运行的 Flarum 站点(由 composer 安装,**不纳入版本控制**,含 vendor / storage / 配置与密钥) |

## 本地开发

1. 复制环境变量:`cp .env.example .env`(按需修改数据库密码等)。
2. 安装 Flarum 站点到 `flarum/`(标准 `composer create-project flarum/flarum flarum`),并把本扩展挂载/软链进容器的 `workbench/flarum-achievement-tree`(见 `docker-compose.yml` 的 `php` 服务挂载)。
3. 启动环境:`docker compose up -d`(站点地址 `http://localhost:8080`)。
4. 前端构建:`cd extension/flarum-achievement-tree/js && npm install && npm run build`(产物输出到 `js/dist`)。
5. 后端刷新:`docker exec flarum-php bash -c "cd /var/www/flarum && php flarum migrate && php flarum cache:clear"`。

> ⚠️ `docker-compose.yml` / `.env.example` 中的数据库默认密码 `flarum` **仅用于本地开发,严禁用于生产环境**;正式部署前请改为强密码。

## License

[MIT](LICENSE)
