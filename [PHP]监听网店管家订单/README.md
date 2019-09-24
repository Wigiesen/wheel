测试环境：Win10 + PHP 7.3.5 + ODBC Driver 17

运行命令：php .\listener.php

Windows php_sqlsrv 扩展已经在包内(根据自身PHP版本替换dll，并修改php.ini添加扩展模块)

注：出现ODBC Driver相关错误的话需要更换版本

=========Linux生产环境配置SQL SERVER扩展=========

1.加入微软mssql源

`curl https://packages.microsoft.com/config/rhel/7/prod.repo > /etc/yum.repos.d/mssqlrelease.repo`

2.安装ODBC相关驱动

`yum install msodbcsql mssql-tools unixODBC-devel`

3.下载sqlsrv扩展

`wget http://pecl.php.net/get/sqlsrv-5.6.1.tgz`

4.解压并进入目录

`tar -zxvf sqlsrv-5.6.1.tgz`

`cd pdo_sqlsrv-5.3.0`

5.安装并编译

1).执行PHP目录下 `phpize`

2).sqlsrv源码包目录下执行

`./configure --with-php-config=/php所在目录/bin/php-config`

`make && make install`

6.添加扩展

`echo "extension = pdo_sqlsrv.so" >> /php所在目录/etc/php.ini`

7.重启PHP-FPM并查看是否安装成功

`/etc/init.d/php-fpm reload`
