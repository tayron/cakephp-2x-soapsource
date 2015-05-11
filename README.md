# SoapSource #

Datasource que conecta a um webservice SOAP usando a classe SoapClient do PHP.

### Tutorial ###

Adicionar o arquivo dentro do diretório: **app/Model/Datasource/**

No arquivo **app/Config/database.php** fazer a configuração para este data source da seguinte forma:

```
#!php
var $developer = array(
   'datasource' => 'SoapSource',
   'localhost' => 'http://192.168.3.123:8080/webservice',
   'service' => 'gestaoClient',
   'cache' => true, // Caso queira desabilitar o cache, basta não informar este parâmetro ou setar como false.
   'login' => 'root', // Caso não necessite de login, basta não informar este parâmetro ou setar como false.
   'password' => '123' // Caso não necessite de senha, basta não informar este parâmetro ou setar como false.
);
```

Na sua classe modelo adicionar o atributo **$useTable = false**.
Na sua action de seu controlador consumir webservice desta maneira:

```
#!php
$this->Modelo->query('nomeFuncaoDoWebservice', $arrayParametroDoWebservice);
```


Este método retorna o que for retornado pelo servico, em caso de erro irá retornar
um array no seguinte formato:

```
#!php
array(
   'error' => $requestSoap->faultstring,
   'host' => $this->debug['localhost']
)
```

Caso seja necessário para cada action infomar a o serviço do webservice que vai ser 
acessado, pode-ser criar o seguinte método no seu **appModel.php**

```
#!php
public function setService($service) {
  $dataSource = ConnectionManager::getDataSource($this->useDbConfig);
  $dataSource->setConfig(array(
     'service' => 'listarClientes'
     'login' => 'fulano@oi.com.br', // Opcional
     'password' => 123456 // Opcional
  ));
}
```


E na sua action antes da chamada do método **Entidade::query()** executar o método 
**Entidade::setService()** desta maneira: 

```
#!php

$this->Modelo->setService('nomeDoServico');
```
