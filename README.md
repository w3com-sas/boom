# W3COM BOOM : Business One Object Manager

This bundle provides a bridge between an SAP HANA database, via its Service Layer and OData Service connections.

## Installation

#### Download the bundle

Use SATIS to get the bundle through composer by modifying your `composer.json` :

```json
{
    "repositories": [
        {
            "type": "composer",
            "url": "https://satis.w3cloud.fr"
        }
    ]
}
```

Also add the dependency :

```json
{
"require": {
        "w3com-sas/boom": "dev-master"
    }
}
```

#### Configure the bundle

Add this to your `config.yml` file :
````yaml
w3com_boom:
    service_layer:
        base_uri: '%sl_base_uri%'
        path: '%sl_path%'
        verify_https: false
        max_login_attempts: 5
        cookies_storage_path: '%kernel.project_dir%/var/cookies'
        connections: '%sl_connections%'
    odata_service:
        base_uri: '%ods_base_uri%'
        path: '%ods_path%'
        verify_https: false
        login:
            username: '%ods_login%'
            password: '%ods_password%'
    app_namespace: AppBundle
````

Add this to your `paramaters.yml.dist` :
````yaml
# W3com BOOM
    sl_base_uri: https://xxxxxx
    sl_path: /
    sl_connections:
            default:
                username: xxxxx
                password: xxxxx
                database: xxxxx
    ods_base_uri: https://xxxxx
    ods_path: /
    ods_login: xxxxx
    ods_password: xxxxx
````

And adjust your `parameters.yml` on each machine accordingly. You can define as many Service Layer connections as you want :
````yaml
sl_connections:
        default:
            username: default
            password: defaultpass
            database: SBO_DEFAULT
        connection1:
            username: user1
            password: user1pass
            database: SBO_DEFAULT
        connection2:
            username: user2
            password: user2pass
            database: SBO_OTHER
````

#### Register the bundle in `app/AppKernel.php` :

````php
$bundles = [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            [...]
            new W3com\BoomBundle\W3comBoomBundle(),
````

## Usage

#### Write your entities

For each table you want to use, you need to write an entity.  
❗️ Entities should be placed in `AppBundle\HanaEntity` and extend the `W3com\BoomBundle\HanaEntity\AbstractEntity` class.  
❗️ You must use annotations to map class fields to table columns, doctrine-like.

They should look like this :

````php
namespace AppBundle\HanaEntity;

use W3com\BoomBundle\Annotation\EntityColumnMeta;
use W3com\BoomBundle\Annotation\EntityMeta;
use W3com\BoomBundle\HanaEntity\AbstractEntity;

/**
 * @EntityMeta(read="sl", write="sl", aliasSl="U_W3C_TABLE", aliasOds="U_W3C_TABLE")
 */
class MyTable extends AbstractEntity
{
    /**
     * @var int
     * @EntityColumnMeta(column="Code", isKey=true)
     */
    protected $code;

    /**
     * @var int
     * @EntityColumnMeta(column="Name")
     */
    protected $name;

    /**
     * @var string
     * @EntityColumnMeta(column="U_W3C_FIELD")
     */
    protected $field;

    /**
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * @param int $code
     * @return MyTable
     */
    public function setCode(int $code)
    {
        $this->code = $code;

        return $this;
    }

    // lots of getters and setters

}
````


#### Get the manager

Use Symfony autowiring to retreive the manager :

````php
use W3com\BoomBundle\Service\BoomManager;

class DefaultController extends Controller
{
    public function indexAction(BoomManager $manager)
    {
        // $manager is now your Boom Manager
    }
}
````