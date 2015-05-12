<?php

/**
 * Datasource que conecta a um webservice SOAP usando a classe SoapClient do PHP.
 *
 * No arquivo app/Config/database.php fazer a configuração para este data source
 * da seguinte forma:
 * 
 * <pre>
 * <code>
 * var $developer = array(
 *    'datasource' => 'SoapSource',
 *    'localhost' => 'http://192.168.3.123:8080/webservice',
 *    'service' => 'gestaoClient',
 *    'cache' => false,
 *    'login' => 'root'
 *    'password'   => '123456'
 * );
 * </code>
 *
 * Na sua classe modelo adicionar o atributo <code>$useTable = false</code>
 * Na sua action de seu controlador consumir webservice desta maneira:
 * <code>$this->Modelo->query('nomeFuncaoDoWebservice', $arrayParametroDoWebservice); </code>
 *
 * Este método retorna o que for retornado pelo servico irá disparar um InvalidArgumentException.
 *
 * Caso seja necessário para cada action infomar a o serviço do webservice que vai ser 
 * acessado, pode-ser criar o seguinte método no seu appModel.php
 * 
 * <pre>
 * <code>
 * public function setService($service) {
 *   $dataSource = ConnectionManager::getDataSource($this->useDbConfig);
 *   $dataSource->setConfig(array(
 *      'service' => 'listarClientes'
 *      'login' => 'fulano@oi.com.br', // Opcional
 *      'password' => 123456 // Opcional
 *   ));
 * }
 * </code>
 * </pre>
 * 
 * E na sua action antes da chamada do método Entidade::query() executar o método 
 * <code>Entidade::setService()</code> desta maneira: 
 * <code>$this->Modelo->setService('nomeDoServico'); </code>
 * 
 * @author Tayron Miranda
 * @version 1
 */
class SoapSource extends DataSource {

    /**
     * Armazena informações da conexão para ser debugado.
     *
     * @access public
     * @var array
     */
    public $debug = array();

    /**
     * Armazena informações de conexão ao webservice.
     * Estas informações devem ser feitas no arquivo 'app/Config/database.php'
     * e serão mesclados no método "__construct".
     *
     * @access public
     * @var array Parametros de conexão do webservice.
     */
    public $config = array(
        'localhost' => '',
        'function' => '',
        'service' => '',
        'cache' => false,
        'login' => false,
        'password' => false
    );

    /**
     * Armazena a instancia de SoapClient com o objeto da conexao.
     *
     * @access protected
     * @var object SoapClient
     */    
    protected $_connection = null;
        
    /**
     * Construtor da classe
     *
     * @access public
     * @param array $config Uma matriz que define as definições de configuração.
     * @return void
     */
    public function __construct($config) {
        parent::__construct($config);
    }
    
    /**
     * Conecta-se ao servidor SOAP usando o wsdl na configuração.
     *
     * @access public
     * @throws CakeException
     * @throws MissingConnectionException
     * @return mixed boolean Em caso de sucesso retorna verdadeiro
     */
    public function connect() {
    
        if(!class_exists('SoapClient')){
            throw new CakeException(__d('cake_dev', 'The SoapClient module is not installed in server!'));
        }    
    
        error_reporting(0);
        $this->connected = false;
        
        $this->debug['localhost'] = $this->config['localhost'] . '/' . $this->config['service'] . '?wsdl';        

        try{
            $this->_connection = new SoapClient(
                $this->debug['localhost'],
                $this->getConfig()    
            );
            
            $this->connected = true;
        } catch (SoapFault $ex) {
            throw new MissingConnectionException(array(
                'class' => get_class($this),
                'message' => $ex->faultstring
            ));
        }
        
        return $this->connected;
    }

    /**
     * Faz uma requisição ao servidor Soap passando o método e os parametros
     * informados.
     *
     * @access public
     * @param string $function Nome da função dentro do webservice.
     * @param array $params Parâmetros do webservice
     * @throws CakeException
     * @throws InvalidArgumentException
     * @return mixed Retorna o retorno do webservice
     */
    public function query($function, $params = array()) {
        $this->connect();    
        
        if(!$this->verifyExistFunction($function)){
            throw new CakeException(__d('cake_dev', 'The function (%s) is not exist in webservice!', $function));
        }
        
        try{
            return $this->_connection->__soapCall($function, [$params]);
        }  catch (SoapFault $ex){
            throw new InvalidArgumentException($ex->faultstring);            
        }
    }
    
    /**
     * Método que seta trata e seta as configurações que serão usadas na conexão 
     * usando SoapClient.
     * 
     * @access private
     * @return array Array de configurações do SoapClient.
     */
    private function getConfig(){
        $config['compression']  = SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP;
        $config['warning'] = false;
        $config['cache_wsdl'] = ( $this->config['cache'] ) ? WSDL_CACHE_DISK : WSDL_CACHE_NONE;
        
        if($this->config['login'] && $this->config['password']){
            $config = array(
                'login' => $this->config['login'],
                'password' => $this->config['password']
            );
        }
        
        return $config;
    }
    
    /**
     * Método que verifica se a função a ser executada no servidor, existe
     * dentro do serviço.
     * 
     * @access private
     * @param string $function Nome da função
     * @return boolean Retorna true caso a função esteja disponível no webservice.
     */
    private function verifyExistFunction($function){
        $functions = $this->_connection->__getFunctions();
        $methodExist = 0;
        foreach($functions as $item){
            if(strripos($item, $function)){
                $methodExist++;
            }
        }

        return (!$methodExist) ? false : true;
    }
    
    /**
     * Método que verifica se há conexão com banco de dados.
     * 
     * @access public
     * @return booleam Retorna true caso tenha conexao com banco de dados.
     */
    public function isConnected(){
        return $this->connected;
    }    
}