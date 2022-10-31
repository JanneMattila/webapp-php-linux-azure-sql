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
  - `/home/site/ini/extensions.ini` Additional PHP configuration for defining the SQL Server drivers
  - `/home/site/nginx.conf` Nginx custom configuration file
    - Custom error page `error.html`
  - `/home/site/startup.sh` Responsible of retrieving driver `.so` files and setting up the environment
    - Download latest version of [Microsoft Drivers for PHP for Microsoft SQL Server](https://github.com/Microsoft/msphpsql)
    - Updates Nginx configuration

2. Enable `PHP_INI_SCAN_DIR` application setting for the app
  - Instructs PHP to look for additional ini files from `/home/site/ini` folder

```bash
az webapp config appsettings set --name <app-name> --resource-group <resource-group-name> --settings PHP_INI_SCAN_DIR="/usr/local/etc/php/conf.d:/home/site/ini"
```

![Set PHP_INI_SCAN_DIR app setting](https://user-images.githubusercontent.com/2357647/197830683-b0121256-dc2c-4b02-a6d4-d960781a009a.png)


3. Set startup command for app
  - Guarantees that everything is configurated correctly when app is started

```bash
az webapp config set --name <app-name> --resource-group <resource-group-name> --startup-file="/home/site/startup.sh"
```

![Set startup command for app](https://user-images.githubusercontent.com/2357647/197831083-611f47bf-3ba3-42ee-bbe0-906b8dbfe06f.png)

4. Deploy your app to `/home/site/wwwroot`
  - This repo contains example files to test the connectivity from PHP to Azure SQL Database
    - Use `https://<yourapp>.azurewebsites.net/phpinfo.php` to see PHP Info
      - Validate, that `/home/site/ini/extensions.ini` can be found under *Additional .ini files parsed*
      - Validate, that `pdo_sqlsrv` is installed correctly
      - Validate, that `sqlsrv` is installed correctly
    - Use `https://<yourapp>.azurewebsites.net/database.php` to test database drivers and connectivity

Here is the filesystem structure under `/home/site` after the deployment (non-relevant files removed from output):

```
.
|-- ini
|   |-- bin
|   |   |-- php_pdo_sqlsrv_81_nts.so
|   |   |-- <other driver files>
|   |   `-- php_sqlsrv_81_ts.so
|   `-- extensions.ini
|-- nginx.conf
|-- startup.sh
`-- wwwroot
    |-- database.php
    |-- error.html
    |-- index.php
    `-- phpinfo.php
```

## Search for PHP usage using Azure Resource Graph

You can search for PHP usage using Azure Resource Graph query. Here is example query (authored by [pemsft](https://github.com/pemsft)):

```sql
Resources
| join kind=leftouter (ResourceContainers | where type=='microsoft.resources/subscriptions' | project subscriptionName=name, subscriptionId) on subscriptionId
| where type == "microsoft.web/sites"
| extend sites = properties.siteProperties.properties
| mv-expand sites
| extend sitesname = sites.name
| extend sitesvalue = sites.value
| project Subscription = subscriptionName, resourceGroup, appName = name, kind, OS = sitesname, codeVersion = sites.value, location, subscriptionId, tags
```

You can easily continue to filter the data in resource graph queries or then export as CSV and continue filtering in Excel.

You can also use PowerShell to scan your environment:

```powershell
class WebAppData {
    [string] $SubscriptionName
    [string] $SubscriptionID
    [string] $ResourceGroupName
    [string] $Location
    [string] $Name
    [string] $PHPVersion
    [string] $Tags
}

$phpApps = New-Object System.Collections.ArrayList
$subscriptions = Get-AzSubscription

foreach ($subscription in $subscriptions) {
    Select-AzSubscription -SubscriptionID $subscription.Id
    $webApps = Get-AzWebApp
    foreach ($webApp in $webApps) {

        $webAppDetails = Get-AzWebApp -ResourceGroupName $webApp.ResourceGroup -Name $webApp.Name

        $webAppData = [WebAppData]::new()
        $webAppData.SubscriptionName = $subscription.Name
        $webAppData.SubscriptionID = $subscription.Id
        $webAppData.ResourceGroupName = $webApp.ResourceGroup
        $webAppData.Name = $webApp.Name
        $webAppData.Location = $webApp.Location
        $webAppData.Tags = $webApp.Tags | ConvertTo-Json -Compress
        $webAppData.PHPVersion = $webAppDetails.SiteConfig.PhpVersion
        
        $phpApps.Add($webAppData)
    }
}

$phpApps | Format-Table
$phpApps | Export-CSV "phpapps.csv" -Force
Write-Warning "Note: 'PHPVersion' column might report '5.6' but that does not mean that application is necessary using PHP." 
```

## Links

[PHP Support timeline in App Service for Linux](https://github.com/Azure/app-service-linux-docs/blob/master/Runtime_Support/php_support.md#support-timeline)

[Azure App Service Linux - Adding PHP Extensions](https://azureossd.github.io/2019/01/29/azure-app-service-linux-adding-php-extensions/)

[NGINX Rewrite Rules for Azure App Service Linux PHP 8.x](https://azureossd.github.io/2021/09/02/php-8-rewrite-rule/index.html)

[Microsoft Drivers for PHP for Microsoft SQL Server](https://github.com/Microsoft/msphpsql)

[Customize PHP_INI_SYSTEM directives](https://learn.microsoft.com/en-us/azure/app-service/configure-language-php?pivots=platform-linux#customize-php_ini_system-directives)

[Q&A: php 8.1 sqlsrv extensions missing](https://learn.microsoft.com/en-us/answers/questions/1051267/php-81-sqlsrv-extensions-missing.html)
