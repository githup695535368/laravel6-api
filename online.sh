#!/usr/bin/env sh
# 线上环境使用！！！
# 重新部署后, 请运行此脚本
server_path=`pwd`
su - nginx <<EOF
cd ${server_path}
#php artisan config:clear
rm -f bootstrap/cache/config.php
#php artisan route:clear #如果生成的路由文件里有控制器找不到 这个命令会报错 所以用硬删
rm -f bootstrap/cache/routes.php
composer dumpautoload
php artisan clear-compiled
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan queue:restart
exit;
EOF
