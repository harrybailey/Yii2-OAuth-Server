yii2-oauth2-server
==================

Forked from https://github.com/Filsh/yii2-oauth2-server

A wrapper for implementing an OAuth2 Server(https://github.com/bshaffer/oauth2-server-php)

# Installation

`composer require human/yii2-oauth2-server`

# Set up

To use this extension,  simply add the following code in your application configuration:

```php
'bootstrap' => ['log', 'oauth2'],
'modules' => [
    'oauth2' => [
        'class' => 'human\yii2\oauth2server\Module',
        'tokenParamName' => 'accessToken', // The naming convention for token name
        'tokenAccessLifetime' => 3600 * 24 * 365, // How long to tokens last for?
        'storageMap' => [
            'user_credentials' => 'api\models\OAuthUser', // The model used to lookup username / password
        ],
        'grantTypes' => [
            'user_credentials' => [
                'class' => 'OAuth2\GrantType\UserCredentials',
            ],
            'refresh_token' => [
                'class' => 'OAuth2\GrantType\RefreshToken',
                'always_issue_new_refresh_token' => true
            ]
        ]
    ]
],
```

As referenced above, we need an `OAuthUser` model to look up users for auth requests that come in. This is it's simplest form, inheriting from an existing `User` model

```php
namespace api\models;

use Yii;

class OAuthUser extends \common\models\User implements \OAuth2\Storage\UserCredentialsInterface
{
    public static function findIdentityByAccessToken($token, $type = null)
    {
        $module = Yii::$app->getModule('oauth2');
        $token = $module->getServer()->getResourceController()->getToken();
        return !empty($token['user_id']) ? static::findIdentity($token['user_id']) : null;
    }

    public function checkUserCredentials($username, $password)
    {
        $user = static::findByUsername($username);
        
        if (empty($user))
        {
            return false;
        }
        
        return $user->validatePassword($password);
    }

    public function getUserDetails($username)
    {
        $user = static::findByUsername($username);
        return ['user_id' => $user->getId()];
    }
}
```



```common\models\User``` - user model implementing an interface ```\OAuth2\Storage\UserCredentialsInterface```, so the oauth2 credentials data stored in user table

Additional OAuth2 Flags:

```enforceState``` - Flag that switch that state controller should allow to use "state" param in the "Authorization Code" Grant Type

```allowImplicit``` - Flag that switch that controller should allow the "implicit" grant type

The next step your shold run migration

```php
yii migrate --migrationPath=@vendor/human/yii2-oauth2-server/migrations
```

this migration create the oauth2 database scheme and insert test user credentials ```testclient:testpass``` for ```http://fake/```

add url rule to urlManager

```php
'urlManager' => [
    'rules' => [
        'POST oauth2/<action:\w+>' => 'oauth2/rest/<action>',
        ...
    ]
]
```

Usage
-----

To use this extension,  simply add the behaviors for your base controller:

```php
use yii\helpers\ArrayHelper;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use human\yii2\oauth2server\filters\ErrorToExceptionFilter;
use human\yii2\oauth2server\filters\auth\CompositeAuth;

class Controller extends \yii\rest\Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'authenticator' => [
                'class' => CompositeAuth::className(),
                'authMethods' => [
                    ['class' => HttpBearerAuth::className()],
                    ['class' => QueryParamAuth::className(), 'tokenParam' => 'accessToken'],
                ]
            ],
            'exceptionFilter' => [
                'class' => ErrorToExceptionFilter::className()
            ],
        ]);
    }
}
```

Create action authorize in site controller for Authorization Code

`https://api.mysite.com/authorize?response_type=code&client_id=TestClient&redirect_uri=https://fake/`

[see more](http://bshaffer.github.io/oauth2-server-php-docs/grant-types/authorization-code/)

```php
/**
 * SiteController
 */
class SiteController extends Controller
{
    /**
     * @return mixed
     */
    public function actionAuthorize()
    {
        if (Yii::$app->getUser()->getIsGuest())
            return $this->redirect('login');
    
        /** @var $module \human\yii2\oauth2server\Module */
        $module = Yii::$app->getModule('oauth2');
        $response = $module->handleAuthorizeRequest(!Yii::$app->getUser()->getIsGuest(), Yii::$app->getUser()->getId());
    
        /** @var object $response \OAuth2\Response */
        Yii::$app->getResponse()->format = \yii\web\Response::FORMAT_JSON;
    
        return $response->getParameters();
    }
}
```

Also if you set ```allowImplicit => true```  you can use Implicit Grant Type - [see more](http://bshaffer.github.io/oauth2-server-php-docs/grant-types/implicit/)

Request example:

`https://api.mysite.com/authorize?response_type=token&client_id=TestClient&redirect_uri=https://fake/cb`

With redirect response:

`https://fake/cb#access_token=2YotnFZFEjr1zCsicMWpAA&state=xyz&token_type=bearer&expires_in=3600`
### JWT Tokens (2.0.1 branch only)
If you want to get Json Web Token (JWT) instead of convetional token, you will need to set `'useJwtToken' => true` in module and then define two more configurations: 
`'public_key' => 'app\storage\PublicKeyStorage'` which is the class that implements [PublickKeyInterface](https://github.com/bshaffer/oauth2-server-php/blob/develop/src/OAuth2/Storage/PublicKeyInterface.php) and `'access_token' => 'OAuth2\Storage\JwtAccessToken'` which implements [JwtAccessTokenInterface.php](https://github.com/bshaffer/oauth2-server-php/blob/develop/src/OAuth2/Storage/JwtAccessTokenInterface.php)

For Oauth2 base library provides the default [access_token](https://github.com/bshaffer/oauth2-server-php/blob/develop/src/OAuth2/Storage/JwtAccessToken.php) which works great except. Just use it and everything will be fine.

and **public_key**

```php
<?php
namespace app\storage;

class PublicKeyStorage implements \OAuth2\Storage\PublicKeyInterface{


    private $pbk =  null;
    private $pvk =  null; 
    
    public function __construct()
    {
        $this->pvk =  file_get_contents('privkey.pem', true);
        $this->pbk =  file_get_contents('pubkey.pem', true); 
    }

    public function getPublicKey($client_id = null){ 
        return  $this->pbk;
    }

    public function getPrivateKey($client_id = null){ 
        return  $this->pvk;
    }

    public function getEncryptionAlgorithm($client_id = null){
        return 'RS256';
    }

}

``` 


For more, see https://github.com/bshaffer/oauth2-server-php
