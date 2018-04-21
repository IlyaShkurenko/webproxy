# ELK compatible logger

Logger with convenient features (and predefined format)

* indexation
* shared and sharing requestId
* auto-rotation
* timestamps with milliseconds

Example:
```
$logger = Logger::createRotatingFileLogger('file.log', 5, 'Y-m-d');
$logger->notice('Hello world', ['env' => $_SERVER], ['requestIndex' => 'internal-request']);
```
produces file-2017-05-07.log file, which will be removed in 5 days, and adds the NOTICE record to it

## Request and Master request UIDs

Logger automatically sets up predefined format (that is compatible with the most modern ELK config). 
The idea behind this is to track requests by unique id-s. 

To reach this goal to each log record contains request id that unique and same for all records in log for the current request (process execution).
Also it adds master request id that stands for initial request id (initial process). Here is an example of log record:

```
[2017-09-15 20:00:38.695744] dc290a0.INFO: DashboardController#dashboard: OK {"parameters":[],"response":"[html]","request":{"path":"/dashboard/home","method":"GET"},"$index":{"userId":"1344","sessionId":"hvlc3mu5o4g59p6k349dd5n7i1","action":"DashboardController#dashboard","ip":"127.0.0.1"}} {"app":{"name":"proxy-dashboard","env":"prod","owner":"any"},"muid":"sh78ue7"}
```

Where `dc290a0` is request uid (current request), `sh78ue7` is master request id (muid). It works in such way:
1. You executes a script from an application (lets say it's `proxy-billing`). Script's request id is `sh78ue7`, 
so all the records in the log have that request id.
2. The script calls remote api endpoint at another application (lets say it's `proxy-dashboard`). 
At this point request id for that application is `dc290a0`, while master request id remains `sh78ue7`.
3. Ok, let's make this wondering, `proxy-dashboard` calls `proxy-core`, and guess? That app has own request id, 
while master request id remains `sh78ue7`

**Questions?**

* Q: How to pass a request id to remote host?
* A: Just do 
```
$data += $logger->prepareMasterRequestParameter()
``` 
before you sends a call to remote host, where `$data` is your arguments list (GET or POST arrays)


## App environment

To use this feature you should right after creation of instance call
```php
$logger->configureAppEnvProcessor();
```
or
```php
$logger->configureAppEnvProcessor('my/path/to/composer.json');
```

Once logger will have configured it loads app name from `config.logger.appName` or `name` parameter from the `composer.json`.
Then, if it's loaded (if not it throws an exception) it dumps the config into `.logger.env.yml` which is located in the same directory as `composer.json`.
It contains:
```yaml
name: proxy-dashboard
env: prod
owner: any
```
where 
* `name` is application name,
* `env` is application environment (`dev`, `test`, `staging` or `prod`), loads from `config.logger.defaultEnv` if present
* `owner` stands for who is application owner, for example if you have the same dashboard over different resellers servers
it's beneficial to divide them logically to track their logs (and errors) separately
