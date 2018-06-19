# W3COM BOOM : Business One Object Manager

This bundle provides a bridge between an SAP HANA database, via its Service Layer and OData Service connections.  
Compatible with Symfony 3.3.* and 4.0.*.

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
        "w3com-sas/boom": "^1.0"
    }
}
```

#### Configure the bundle

**❗️ Warning ❗️ ** the config is not the same if you use Symfony Flex. There is no automated Flex recipe since this is a private package.

Not using Flex ? Add the following snippet to the `app/config/config.yml` file  
Using Flex ? Add it to a new `config/packages/boom.yaml` file.
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
    app_namespace: AppBundle # It's App if you're using Flex
````

Non-Flex apps : add this to your `parameters.yml.dist` :
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

Non-Flex apps : adjust your `parameters.yml` on each machine accordingly. You can define as many Service Layer connections as you want :
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
# Other parameters not shown, refer to your parameters.dist.yml
````

Flex apps : add the following snippet to the `parameters` key in the `config/services.yaml` file. You can define as many Service Layer connections as you want. Here we have two connections, `default` and `prod`.
````yaml
parameters:
    sl_base_uri: '%env(boom_sl_baseuri)%'
    sl_path: '%env(boom_sl_path)%'
    sl_connections:
        default:
            username: '%env(boom_sl_default_username)%'
            password: '%env(boom_sl_default_password)%'
            database: '%env(boom_sl_default_database)%'
        prod:
            username: '%env(boom_sl_prod_username)%'
            password: '%env(boom_sl_prod_password)%'
            database: '%env(boom_sl_prod_database)%'
    ods_base_uri: '%env(boom_ods_baseuri)%'
    ods_path: '%env(boom_ods_path)%'
    ods_login: '%env(boom_ods_login)%'
    ods_password: '%env(boom_ods_password)%'
````

Flex apps : set your external parameters, see the [Symfony doc](https://symfony.com/doc/current/configuration/external_parameters.html).  
Feel free to rename your parameters, as long as you modify the `config/services.yaml` file accordingly.

#### Register the bundle in `app/AppKernel.php` :

If you're not using Flex, you should enable the bundle.

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

Note that the required namespace depends on the Symfony version you're using. Use `AppBundle\HanaEntity` for Symfony 3.* and `App\HanaEntity` for Symfony 4.*.

Entities should look like this :

````php
namespace AppBundle\HanaEntity;

use W3com\BoomBundle\Annotation\EntityColumnMeta;
use W3com\BoomBundle\Annotation\EntityMeta;
use W3com\BoomBundle\HanaEntity\AbstractEntity;

/**
 * @EntityMeta(read="ods", write="sl", aliasRead="U_W3C_TABLE", aliasWrite="U_W3C_TABLE")
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
     * @EntityColumnMeta(column="U_W3C_FIELD", readOnly=true)
     * This column is read-only, Boom will never try to update its value in SAP
     */
    protected $field;
    
    /**
     * @var string
     * @EntityColumnMeta(column="U_W3C_FIELD2", readColumn="Field2")
     * This column will be read with Field2 but updated with U_W3C_FIELD2,
     * it's useful when you read with ODS but write with SL
     */
    protected $field2;

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

#### Custom repositories (optional)

If you need to write some methods that you want to use here and there in your app, you should write repositories for your entities.

❗️ Repos should be placed in `AppBundle\HanaRepository` and extend the `W3com\BoomBundle\HanaRepository\AbstractRepository` class.  
❗️ If you placed your entities in subfolders (see above), you must respect the exact same organization for your repos.

Again, Use `AppBundle\HanaRepository` for Symfony 3.* and `App\HanaRepository` for Symfony 4.*.
 
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

Use Symfony autowiring to retrieve the manager :

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

Now that you have retrieved the manager, use it to get the repo for your entity.  
You must type the namespace to your entity, minus the `App[Bundle]\HanaRepository` part :

````php
$repo = $manager->getRepository('MyTable');
$repo2 = $manager->getRepository('Namespace\Entity');
````

If you wrote a custom repo, it will be instanciated, and if you didn't, you will get a `W3com\BoomBundle\HanaRepository\DefaultRepository` object.

#### Finding objects

You now have access to a few methods to help you find objects.

To find a specific object :
````php
// Boom will know which column he should test the key against. 
// This will return an AbstractEntity object, or null if not exactly one result was returned.
$object = $repo->find($key);
````

To create a more complex request, use the `Parameters` class.

````php
use W3com\BoomBundle\Parameters\Clause;
use W3com\BoomBundle\Parameters\Parameters;

$repo = $manager->getRepository('MyTable');
$params = $repo->createParameters()
    ->addFilter('columnName', 'value') // filters on columnName = value
    ->addFilter('columnName', 'value', Clause::GREATER_THAN)
    ->addSelect('columnName') // will only hydrate the requested column, others will be set as null.
    ->addSelect(array('columnName1', 'columnName2))
    ->addOrder('columnName') // will order results on columnName ASC by default
    ->addOrder('columnName', Parameters::ORDER_DESC)
    ->setTop(10);
    
$results = $repo->findAll($params);
````

❗️ You should be careful with the `addSelect` method, as you won't be able to distinguish real `null` values from SAP, and `null` values from non-requested columns !

##### Creating filters

You can use one of these clauses with the `addFilter` method :
* `Clause::EQUALS` (which is the default)
* `Clause::NOT_EQUALS`
* `Clause::STARTS_WITH`
* `Clause::ENDS_WITH`
* `Clause::CONTAINS`
* `Clause::SUBSTRINGOF`
* `Clause::GREATER_THAN`
* `Clause::GREATER_OR_EQUAL`
* `Clause::LOWER_THAN`
* `Clause::LOWER_OR_EQUAL`

You can also use `Clause::OR` and `Clause::AND` in the last argument when adding a filter, so that this filter is prefixed with a logical operator :

````php
$params
    ->addFilter('columnName','value', Clause::CONTAINS)
    ->addFilter('otherColumn, 'otherValue', Clause::EQUALS, Clause::OR);
// columnName contains value OR otherColumn equals otherValue
````

If you just want to find multiple values in the same column, pass an array as second argument :

````php
$params
    ->addFilter('columnName',['value1', 'value2', 'value3']);
// columnName equals value1 OR value2 OR value3
````

You can also search for `null` values if your Calculation View supports it :

````php
$params
    ->addFilter('columnName', null);
// columnName is null (note: this is NOT an empty string !)
````

Want to write a custom filter with "and" and "or" mixed, with parenthesis groups ? Use `addRawFilter()`, but you're on your own on this one :  
(note that in this case the filter will be passed *as is*, so you must use the real column names as they are in SAP B1)
````php
$params
    ->addRawFilter('(U_W3C_FIELD eq "value1" or U_W3C_COL eq "value2") and (U_W3C_ONE eq "value1" and U_W3C_TWO eq "value2")');
````

#### Creating, updating or deleting

````php
$object = new MyTable();
$code = $repo->getNextCode();
$object->setCode($code)->setField('myValue')->setOtherField('someValue');
$repo = $manager->getRepository('MyTable');
$repo->add($object);
````
❗️ For now, the `getNextCode()` method is just for entities which has their key called `code`.

````php
$object->setField('myValue')->setOtherField('someValue');
$repo = $manager->getRepository('MyTable');
$repo->update($object);
`````

````php
$key = $object->getCode() // or any method that gets the object key property
$repo = $manager->getRepository('MyTable');
$repo->delete($key);
````
