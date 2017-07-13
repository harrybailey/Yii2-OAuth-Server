yii2-oauth2-server
==================

Forked from https://github.com/Filsh/yii2-oauth2-server which is build on top of https://github.com/bshaffer/oauth2-server-php

A wrapper for implementing an OAuth2 Server(https://github.com/bshaffer/oauth2-server-php)

# Installation

`composer require human/yii2-oauth2-server`

# Set up

To use this extension,  simply add the following code in your application configuration:

```php
'bootstrap' => ['oauth2'],
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
'components' => [
    'user' => [
        'identityClass' => 'api\models\OAuthUser',
        'enableAutoLogin' => false,
        'enableSession' => false,
        'loginUrl' => null,
    ],
    'urlManager' => [
        'enablePrettyUrl' => true,
        'enableStrictParsing' => true,
        'showScriptName' => false,
        'rules' => [
            'POST oauth2/<action:\w+>' => 'oauth2/rest/<action>',
        ],
    ]
],
```

As referenced above, we need an `OAuthUser` model to look up users for auth requests that come in. This is it's simplest form, inheriting from an existing `User` model and implementing `\OAuth2\Storage\UserCredentialsInterface`

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

# Migrations

We need to set up various tables to store our oauth details in, the migration can be found in `vendor/human/yii2-oauth2-server/migrations`

Run `./yii migrate/create add_oauth_tables` to create an empty migration and copy the class functions from `/vendor/human/yii2-oauth2-server/migrations/m140501_075311_add_oauth2_server.php` to the new file. 


# Authentication in API requests

Simply add the behaviors for your base controller:

```php
use yii\helpers\ArrayHelper;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use human\yii2\oauth2server\filters\ErrorToExceptionFilter;
use human\yii2\oauth2server\filters\auth\CompositeAuth;

class ActiveController extends \yii\rest\ActiveController
{
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
