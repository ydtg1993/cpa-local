
# linux下定时任务
    # 晚上12点过1分后执行
    php think TodayInstall
    # 每隔十分钟执行一次
    php think PullInstallLog
    
# php代码配置
    1.根目录建立 install.lock 锁文件
    2.复制config/database.example.php    到 config/database.php  并修改mysql配置信息
    3.修改app.php 中 qqc_server_url的链接，并且修改对应服务器对应的.env配置代码：如下
        AGENT_CAP_ACCESS_TOKEN=97yzi45bgp0h80go7weiqugeq1upkarq
        AGENT_CAP_SECRET_TOKEN=5jn2l63e19y71ji6xif015b53ylyn8t6
    4.更目录建立 runtime 缓存文件   
        
# mysql数据清除
  TRUNCATE hisi_example_install_log
  TRUNCATE hisi_example_install_total_log
  TRUNCATE hisi_example_links
  TRUNCATE hisi_example_news