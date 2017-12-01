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

### Writing entities and repositories

#### Entities

For each table you want to use, you need to write an entity.  
❗️ Entities should be placed in `AppBundle\HanaEntity` and extend the `W3com\BoomBundle\HanaEntity\AbstractEntity` class.  
❗️ You must use annotations to map class fields to table columns, doctrine-like.  
❗️ Setters should use the `set($field, $value)` method from parent class.

Entities should look like this :

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
        return $this->set('code', $code);
    }

    // lots of getters and setters

}
````

Note that it's not mandatory that your entities are placed just in the `HanaEntity` folder. You can use any subfolders you want to keep your entities organized.

#### Custom repositories (optionnal)

If you need to write some methods that you want to use here and there in your app, you should write repositories for your entities.

❗️ Repos should be placed in `AppBundle\HanaRepository` and extend the `W3com\BoomBundle\HanaRepository\AbstractRepository` class.  
❗️ If you placed your entities in subfolders (see above), you must respect the exact same organization for your repos.
 
````php
namespace AppBundle\HanaRepository;


use W3com\BoomBundle\Repository\AbstractRepository;

class MyTableRepository extends AbstractRepository
{
    // this is an example, write whatever is usefull !
    public function findByToken($token)
    {
        //...
    }
}
````

### Start sending requests

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

#### Get the repo

Now that you have retreived the manager, use it to get the repo for your entity.  
You must type the namespace to your entity, minus the `AppBundle\HanaRepository` part :

````php
$repo = $manager->getRepository('MyTable');
$repo2 = $manager->getRepository('Namespace\Entity');
````

If you wrote a custom repo, it will be instanciated, and if you didn't, you will get a `AppBundle\HanaRepository\DefaultRepository` object.

#### Finding objects

You now have access to a few methods to help you find objects.

To find a specific object :
````php
// Boom will know on which column to test key. 
// This will return an AbstractEntity object, or null if not exactly one result was returned.
$object = $repo->find($key);
````

To create a more complex request, use the `Parameters` class.

`````
TODO écrire doc Parameters
`````

#### Creating or updating

`````
TODO écrire doc add/update
`````