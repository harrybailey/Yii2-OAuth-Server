<?php

namespace human\yii2\oauth2server;

trait BootstrapTrait
{
    /**
     * @var array Model's map
     */
    private $_modelMap = [
        'OauthClients'               => 'human\yii2\oauth2server\models\OauthClients',
        'OauthAccessTokens'          => 'human\yii2\oauth2server\models\OauthAccessTokens',
        'OauthAuthorizationCodes'    => 'human\yii2\oauth2server\models\OauthAuthorizationCodes',
        'OauthRefreshTokens'         => 'human\yii2\oauth2server\models\OauthRefreshTokens',
        'OauthScopes'                => 'human\yii2\oauth2server\models\OauthScopes',
    ];
    
    /**
     * @var array Storage's map
     */
    private $_storageMap = [
        'access_token'          => 'human\yii2\oauth2server\storage\Pdo',
        'authorization_code'    => 'human\yii2\oauth2server\storage\Pdo',
        'client_credentials'    => 'human\yii2\oauth2server\storage\Pdo',
        'client'                => 'human\yii2\oauth2server\storage\Pdo',
        'refresh_token'         => 'human\yii2\oauth2server\storage\Pdo',
        'user_credentials'      => 'human\yii2\oauth2server\storage\Pdo',
        'public_key'            => 'human\yii2\oauth2server\storage\Pdo',
        'jwt_bearer'            => 'human\yii2\oauth2server\storage\Pdo',
        'scope'                 => 'human\yii2\oauth2server\storage\Pdo',
    ];
    
    protected function initModule(Module $module)
    {
        $this->_modelMap = array_merge($this->_modelMap, $module->modelMap);
        foreach ($this->_modelMap as $name => $definition) {
            \Yii::$container->set("human\\yii2\\oauth2server\\models\\" . $name, $definition);
            $module->modelMap[$name] = is_array($definition) ? $definition['class'] : $definition;
        }

        $this->_storageMap = array_merge($this->_storageMap, $module->storageMap);
        foreach ($this->_storageMap as $name => $definition) {
            \Yii::$container->set($name, $definition);
            $module->storageMap[$name] = is_array($definition) ? $definition['class'] : $definition;
        }
    }
}