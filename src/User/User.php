<?php
namespace DirectAdmin\User;

use DirectAdmin\Adapter;
use DirectAdmin\Response;

/**
 * The User Accounts
 */
class User {
    
    private $adapter;
    
    /**
     * Creates a new User instance
     * @param Adapter $adapter
     */
    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
    
    

    /**
     * Returns the list of Users for the current Reseller
     * @return array
     */
    public function getAll(): array {
        $response = $this->adapter->get("/CMD_API_SHOW_USERS");
        return $response->data;
    }

    /**
     * Returns the Users limits and usage
     * @param string $user
     * @param string $domain
     * @return array
     */
    public function getInfo(string $user, string $domain): array {
        $fields = [ "bandwidth", "quota", "domainptr", "mysql", "nemailf", "nemailr", "nemails", "nsubdomains", "ftp" ];
        $usage  = $this->adapter->get("/CMD_API_SHOW_USER_USAGE", [ "user" => $user ]);
        
        if ($usage->hasError) {
            $usage  = $this->adapter->get("/CMD_API_SHOW_USER_USAGE",  [ "domain" => $domain ]);
            $config = $this->adapter->get("/CMD_API_SHOW_USER_CONFIG", [ "domain" => $domain ]);
        } else {
            $config = $this->adapter->get("/CMD_API_SHOW_USER_CONFIG", [ "user" => $username ]);
        }
        $result = [];
        
        if (!$usage->hasError && !$config->hasError) {
            foreach ($fields as $field) {
                if (isset($usage->data[$field]) && isset($config->data[$field])) {
                    $result[$field] = [
                        "used"   => (int)$usage->data[$field],
                        "total"  => $config->data[$field] == "unlimited" ? -1 : (int)$config->data[$field],
                        "canAdd" => $config->data[$field] == "unlimited" || (int)$usage->data[$field] < (int)$config->data[$field],
                    ];
                }
            }
            
            if (!empty($result)) {
                $result["dbQuota"]    = [ "used" => isset($usage->data["db_quota"])    ? (int)$usage->data["db_quota"]    : 0 ];
                $result["emailQuota"] = [ "used" => isset($usage->data["email_quota"]) ? (int)$usage->data["email_quota"] : 0 ];

                $result["dbQuota"]["used"]    = floor(($result["dbQuota"]["used"]    / (1024 * 1024)) * 100) / 100;
                $result["emailQuota"]["used"] = floor(($result["emailQuota"]["used"] / (1024 * 1024)) * 100) / 100;

                $result["bandwidth"]["additional"] = !empty($config->data["additional_bandwidth"]) ? (int)$config->data["additional_bandwidth"] : 0;
            }
        }
        
        return $result;
    }
    
    /**
     * Returns the users configuration
     * @param string $user
     * @return array
     */
    public function getConfig(string $user) {
        $response = $this->adapter->get("/CMD_API_SHOW_USER_CONFIG", [ "user" => $user ]);
        return $response->data;
    }
    
    /**
     * Returns the main domain for the given user
     * @param string $user
     * @return string
     */
    public function getMainDomain(string $user): string {
        $response = $this->adapter->get("/CMD_API_SHOW_USER_DOMAINS", [ "user" => $user ]);
        foreach ($response->keys as $key) {
            return str_replace("_", ".", $key);
        }
        return "";
    }


    
    /**
     * Creates a new User
     * @param array $data
     * @return Response
     */
    public function create(array $data): Response {
        return $this->adapter->post("/CMD_API_ACCOUNT_USER", [
            "action"   => "create",
            "add"      => "Submit",
            "username" => $data["username"],
            "email"    => $data["email"],
            "passwd"   => $data["password"],
            "passwd2"  => $data["password"],
            "domain"   => $data["domain"],
            "package"  => $data["package"],
            "ip"       => $this->adapter->getHost(),
            "notify"   => "no",
        ]);
    }
    
    /**
     * Deletes the given User
     * @param string $user
     * @return Response
     */
    public function delete(string $user): Response {
        return $this->adapter->post("/CMD_API_SELECT_USERS", [
            "confirmed" => "Confirm",
            "delete"    => "yes",
            "select0"   => $user,
        ]);
    }
    
    

    /**
     * Suspends or Unsuspends the given User Account
     * @param string|string[] $user
     * @param boolean         $suspend Optional.
     * @return Response
     */
    public function suspend($user, bool $suspend = true): Response {
        $users  = is_array($user) ? $user : [ $user ];
        $fields = $suspend ? [ "dosuspend" => "Suspend" ] : [ "dounsuspend" => "Unsuspend" ];
        
        foreach ($users as $index => $value) {
            $fields["select$index"] = $value;
        }
        return $this->adapter->post("/CMD_API_SELECT_USERS", $fields);
    }
    
    /**
     * Moves the user from the current reseller to a new one
     * @param string $user
     * @param string $reseller
     * @return Response
     */
    public function changeReseller(string $user, string $reseller): Response {
        return $this->adapter->post("/CMD_API_MOVE_USERS", [
            "action"  => "moveusers",
            "select1" => $user,
            "creator" => $reseller,
        ]);
    }
    
    /**
     * Changes the User's Email
     * @param string $user
     * @param string $email
     * @return Response
     */
    public function changeEmail(string $user, string $email): Response {
        return $this->adapter->post("/CMD_API_MODIFY_USER", [
            "action" => "single",
            "email"  => "Save",
            "user"   => $user,
            "evalue" => $email,
        ]);
    }
    
    /**
     * Changes the User's Username
     * @param string $user
     * @param string $newName
     * @return Response
     */
    public function changeUsername(string $user, string $newName): Response {
        return $this->adapter->post("/CMD_API_MODIFY_USER", [
            "action" => "single",
            "name"   => "Save",
            "user"   => $user,
            "nvalue" => $newName,
        ]);
    }
    
    /**
     * Changes the User's Package
     * @param string $user
     * @param string $package
     * @return Response
     */
    public function changePackage(string $user, string $package): Response {
        return $this->adapter->post("/CMD_API_MODIFY_USER", [
            "action"  => "package",
            "user"    => $user,
            "package" => $package,
        ]);
    }
    
    /**
     * Changes the old Domain to the new Domain. Requires user login
     * @param string $oldDomain
     * @param string $newDomain
     * @return Response
     */
    public function changeDomain(string $oldDomain, string $newDomain): Response {
        return $this->adapter->post("/CMD_API_CHANGE_DOMAIN", [
            "old_domain" => $oldDomain,
            "new_domain" => $newDomain,
        ]);
    }
    
    /**
     * Resets the given User's Password
     * @param string $user
     * @param string $password
     * @return Response
     */
    public function changePassword(string $user, string $password): Response {
        return $this->adapter->post("/CMD_API_USER_PASSWD", [
            "username" => $user,
            "passwd"   => $password,
            "passwd2"  => $password,
        ]);
    }
    
    /**
     * Sets the User's Additional Bandwidth
     * @param string  $user
     * @param integer $amount
     * @return Response
     */
    public function addBandwidth(string $user, int $amount): Response {
        return $this->adapter->post("/CMD_API_MODIFY_USER", [
            "user"                 => $user,
            "additional_bandwidth" => $amount,
            "additional_bw"        => "add",
            "action"               => "single",
        ]);
    }


    
    /**
     * Sets the Public Stats. Requires user login
     * @return Response
     */
    public function setPublicStats(): Response {
        $domain = $this->adapter->getDomain();
        return $this->adapter->post("/CMD_API_PUBLIC_STATS", [
            "action"  => "public",
            "path"    => "awstats",
            "domain"  => $domain,
            "select0" => $domain,
        ]);
    }
    
    /**
     * Returns the contents of the Error Log File. Requires user login
     * @param integer $lines Optional.
     * @return string
     */
    public function getErrorLog(int $lines = 10) {
        $response = $this->adapter->get("/CMD_SHOW_LOG", [
            "domain" => $this->adapter->getDomain(),
            "type"   => "error",
            "lines"  => $lines,
        ]);
        return $response->raw;
    }

    /**
     * Returns the contents of the Access Log File. Requires user login
     * @param integer $lines Optional.
     * @return string
     */
    public function getAccessLog(int $lines = 10) {
        $response = $this->adapter->get("/CMD_SHOW_LOG", [
            "domain" => $this->adapter->getDomain(),
            "type"   => "access",
            "lines"  => $lines,
        ]);
        return $response->raw;
    }

    /**
     * Returns the Spam Configuration. Requires user login
     * @return Response
     */
    public function getSpamConfig(): Response {
        return $this->adapter->post("/CMD_API_SPAMASSASSIN", [
            "domain" => $this->adapter->getDomain(),
        ]);
    }
    
    /**
     * Sets the Spam Configuration. Requires user login
     * @param array $data
     * @return Response
     */
    public function setSpamConfig(array $data): Response {
        return $this->adapter->post("/CMD_API_SPAMASSASSIN", [
            "action" => "save",
            "domain" => $this->adapter->getDomain(),
            "is_on"  => "yes",
        ] + $data);
    }
}
