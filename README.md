# Webapp PHP Linux Azure SQL

Webapp for PHP 8.1 running in App Service for Linux and using Azure SQL Database

## Potential issues

### Issue `could not find driver`

```php
try {
    $conn = new PDO("sqlsrv:server = tcp:".$server.".database.windows.net,1433; Database = ".$database, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch (PDOException $e) {
    print_r($e);
}
catch (Exception $error) {
    print_r($error);
}
```

Above code might cause following exception if you don't have drivers installed:

```
PDOException Object
(
    [message:protected] => could not find driver
    [string:Exception:private] => 
    [code:protected] => 0
    [file:protected] => /home/site/wwwroot/database.php
    [line:protected] => 16
    [trace:Exception:private] => Array
        (
            [0] => Array
                (
                    [file] => /home/site/wwwroot/database.php
                    [line] => 16
                    [function] => __construct
                    [class] => PDO
                    [type] => ->
                    [args] => Array
                        (
                            [0] => sqlsrv:server = tcp:<your_server_here>.database.windows.net,1433; Database = <your_database_here>
                            [1] => <your_username_here>
                            [2] => <your_password_here>
                        )

                )

        )

    [previous:Exception:private] => 
    [errorInfo] => 
)
```

If driver is installed but you don't have access to target database,
then you might get following error message:

```
PDOException Object
(
    [message:protected] => SQLSTATE[HYT00]: [Microsoft][ODBC Driver 18 for SQL Server]Login timeout expired
    [string:Exception:private] => 
    [code:protected] => HYT00
    [file:protected] => /home/site/wwwroot/database.php
    [line:protected] => 16
    [trace:Exception:private] => Array
        (
            [0] => Array
                (
                    [file] => /home/site/wwwroot/database.php
                    [line] => 16
                    [function] => __construct
                    [class] => PDO
                    [type] => ->
                    [args] => Array
                        (
                            [0] => sqlsrv:server = tcp:<your_server_here>.database.windows.net,1433; Database = <your_database_here>
                            [1] => <your_username_here>
                            [2] => <your_password_here>
                        )

                )

        )

    [previous:Exception:private] => 
    [errorInfo] => Array
        (
            [0] => HYT00
            [1] => 0
            [2] => [Microsoft][ODBC Driver 18 for SQL Server]Login timeout expired
            [3] => 08001
            [4] => 11001
            [5] => [Microsoft][ODBC Driver 18 for SQL Server]TCP Provider: Error code 0x2AF9
            [6] => 08001
            [7] => 11001
            [8] => [Microsoft][ODBC Driver 18 for SQL Server]A network-related or instance-specific error has occurred while establishing a connection to SQL Server. Server is not found or not accessible. Check if instance name is correct and if SQL Server is configured to allow remote connections. For more information see SQL Server Books Online.
        )

)
```

### Issue `Call to undefined function sqlsrv_connect`

```php
try {
    $connectionInfo = array("UID" => $username, "pwd" => $password, "Database" => $database, "LoginTimeout" => 30, "Encrypt" => 1, "TrustServerCertificate" => 0);
    $serverName = "tcp:".$server.".database.windows.net,1433";
    $conn = sqlsrv_connect($serverName, $connectionInfo);
    if( $conn === false )
    {
        echo "SQL Server Extension code failed in connection";
    }
}
catch (Exception $error) {
    print_r($error);
}
```

Above code might cause following exception if you don't have drivers installed:

```
Hello from Database<br />
<b>Fatal error</b>:  Uncaught Error: Call to undefined function sqlsrv_connect() in /home/site/wwwroot/database.php:30
Stack trace:
#0 {main}
  thrown in <b>/home/site/wwwroot/database.php</b> on line <b>30</b><br />
```

## Install `pdo_sqlsrv` and `sqlsrv` drivers

Deployment steps:

1. Deploy these files to App Service for Linux
  - `/home/site/ini/extensions.ini`: Additional PHP configurations defining the enable drivers
  - `/home/site/startup.sh`: Responsible of retrieving driver `.so` files and setting up the environment
  - `/home/site/default`: Nginx custom configuration file

2. Enable `PHP_INI_SCAN_DIR` application setting for the app
  - Instructs PHP to look for additional ini files from `/home/site/ini` folder

```bash
az webapp config appsettings set --name <app-name> --resource-group <resource-group-name> --settings PHP_INI_SCAN_DIR="/usr/local/etc/php/conf.d:/home/site/ini"
```

3. Set startup command for app
  - Guarantees that everything is configurated correctly when app is started

```bash
az webapp config set --name <app-name> --resource-group <resource-group-name> --startup-file="/home/site/startup.sh"
```

4. Deploy your app to `/home/site/wwwroot`
  - This repo contains example files to test the connectivity from PHP to Azure SQL Database

## Links

[PHP Support timeline in App Service for Linux](https://github.com/Azure/app-service-linux-docs/blob/master/Runtime_Support/php_support.md#support-timeline)

[Azure App Service Linux - Adding PHP Extensions](https://azureossd.github.io/2019/01/29/azure-app-service-linux-adding-php-extensions/)

[NGINX Rewrite Rules for Azure App Service Linux PHP 8.x](https://azureossd.github.io/2021/09/02/php-8-rewrite-rule/index.html)

[Microsoft Drivers for PHP for Microsoft SQL Server](https://github.com/Microsoft/msphpsql)
