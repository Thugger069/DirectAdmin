<?php
namespace DirectAdmin;

/**
 * The DirectAdmin API
 */
class DirectAdmin {
    
    private $context;
    
    public $service;
    public $loginKey;
    public $mailQueue;
    
    public $reseller;
    public $user;
    public $package;
    public $transfer;

    public $account;
    public $backup;
    public $database;
    public $ftpAccount;
    public $phpConfig;
    
    public $domainPtr;
    public $subdomain;
    public $redirect;

    public $email;
    public $forwarder;
    public $responder;
    public $vacation;

    public $directory;
    public $file;
    
    
    /**
     * Creates a new DirectAdmin instance
     * @param string  $host
     * @param integer $port
     * @param string  $username
     * @param string  $password
     */
    public function __construct(string $host, int $port, string $username, string $password) {
        $this->context    = new Context($host, $port, $username, $password);
        
        $this->service    = new Admin\Service($this->context);
        $this->loginKey   = new Admin\LoginKey($this->context);
        $this->mailQueue  = new Admin\MailQueue($this->context);
        
        $this->reseller   = new Reseller\Reseller($this->context);
        $this->user       = new Reseller\User($this->context);
        $this->package    = new Reseller\Package($this->context);
        $this->transfer   = new Reseller\Transfer($this->context);
        
        $this->account    = new User\Account($this->context);
        $this->backup     = new User\Backup($this->context);
        $this->database   = new User\Database($this->context);
        $this->ftpAccount = new User\FTPAccount($this->context);
        $this->phpConfig  = new User\PHPConfig($this->context);
        
        $this->domainPtr  = new Domain\DomainPtr($this->context);
        $this->subdomain  = new Domain\Subdomain($this->context);
        $this->redirect   = new Domain\Redirect($this->context);

        $this->email      = new Email\Email($this->context);
        $this->forwarder  = new Email\Forwarder($this->context);
        $this->responder  = new Email\Responder($this->context);
        $this->vacation   = new Email\Vacation($this->context);
        
        $this->directory  = new File\Directory($this->context);
        $this->file       = new File\File($this->context);
    }
    
    
    
    /**
     * Returns the Server IP
     * @return string
     */
    public function getHost(): string {
        return $this->context->getHost();
    }
    
    /**
     * Returns the Server Port
     * @return integer
     */
    public function getPort(): int {
        return $this->context->getPort();
    }

    /**
     * Returns the Public Path
     * @param string $path Optional.
     * @return string
     */
    public function getPublicPath(string $path = ""): string {
        return $this->context->getPublicPath($path);
    }

    

    /**
     * Sets the Reseller for the Context
     * @param string $reseller
     * @return void
     */
    public function setReseller(string $reseller): void {
        $this->context->setReseller($reseller);
    }

    /**
     * Sets the User and Domain for the Context
     * @param string $user
     * @param string $domain
     * @return void
     */
    public function setUser(string $user, string $domain): void {
        $this->context->setUser($user, $domain);
    }
}
