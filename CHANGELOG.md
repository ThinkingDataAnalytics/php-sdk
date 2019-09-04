**v1.0.3** (2019/08/30)
-  新增按文件大小切分文件功能.
**v1.0.4** (2019/09/04)
-  修改BatchConsumer的错误返回码
-  demo案例的修改
-  添加BatchConsumer析构函数，当没有调用flush的时候，程序结束后会主动上报数据，防止BatchConsumer数据未上报