<?php

namespace harrybailey\yii2\oauth2server;

trait BootstrapTrait
{
    /**
     * @var array Model's map
     */
    private $_modelMap = [
        'OauthClients'               => 'harrybailey\yii2\oauth2server\models\OauthClients',
        'OauthAccessTokens'          => 'harrybailey\yii2\oauth2server\models\OauthAccessTokens',
        'OauthAuthorizationCodes'    => 'harrybailey\yii2\oauth2server\models\OauthAuthorizationCodes',
        'OauthRefreshTokens'         => 'harrybailey\yii2\oauth2server\models\OauthRefreshTokens',
        'OauthScopes'                => 'harrybailey\yii2\oauth2server\models\OauthScopes',
    ];
    
    /**
     * @var array Storage's map
     */
    private $_storageMap = [
        'access_token'          => 'harrybailey\yii2\oauth2server\storage\Pdo',
        'authorization_code'    => 'harrybailey\yii2\oauth2server\storage\Pdo',
        'client_credentials'    => 'harrybailey\yii2\oauth2server\storage\Pdo',
        'client'                => 'harrybailey\yii2\oauth2server\storage\Pdo',
        'refresh_token'         => 'harrybailey\yii2\oauth2server\storage\Pdo',
        'user_credentials'      => 'harrybailey\yii2\oauth2server\storage\Pdo',
        'public_key'            => 'harrybailey\yii2\oauth2server\storage\Pdo',
        'jwt_bearer'            => 'harrybailey\yii2\oauth2server\storage\Pdo',
        'scope'                 => 'harrybailey\yii2\oauth2server\storage\Pdo',
    ];
    
    protected function initModule(Module $module)
    {
        $this->_modelMap = array_merge($this->_modelMap, $module->modelMap);
        foreach ($this->_modelMap as $name => $definition) {
            \Yii::$container->set("harrybailey\\Yii2\\Oauth2server\\models\\" . $name, $definition);
            $module->modelMap[$name] = is_array($definition) ? $definition['class'] : $definition;
        }

        $this->_storageMap = array_merge($this->_storageMap, $module->storageMap);
        foreach ($this->_storageMap as $name => $definition) {
            \Yii::$container->set($name, $definition);
            $module->storageMap[$name] = is_array($definition) ? $definition['class'] : $definition;
        }
    }
}