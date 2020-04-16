### Database settings
`public $dbHost = "localhost";`

`public $dbUser = "root";`

`public $dbPass = "";`

`public $dbNameGames = "games";`

`public $dbNameOperators = "operators";`

### Execute the script

_All the exports in one command:_

`$ php Migration.php`

_OR the following commands to run only one export:_

`$ php Migration.php games`

`$ php Migration.php operators`

`$ php Migration.php providers`
