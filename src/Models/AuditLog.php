<?php
/**
 * Yasmin
 * Copyright 2017-2018 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\Models;

/**
 * Represents a guild audit log.
 *
 * @property \CharlotteDunois\Yasmin\Models\Guild      $guild     Which guild this audit log is for.
 * @property \CharlotteDunois\Yasmin\Utils\Collection  $entries   Holds the entries, mapped by their ID.
 * @property \CharlotteDunois\Yasmin\Utils\Collection  $users     Holds the found users in the audit log, mapped by their ID.
 * @property \CharlotteDunois\Yasmin\Utils\Collection  $webhooks  Holds the found webhooks in the audit log, mapped by their ID.
 */
class AuditLog extends ClientBase {
    protected $guild;
    
    protected $entries;
    protected $users;
    protected $webhooks;
    
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\Models\Guild $guild, array $audit) {
        parent::__construct($client);
        $this->guild = $guild;
        
        $this->entries = new \CharlotteDunois\Yasmin\Utils\Collection();
        $this->users = new \CharlotteDunois\Yasmin\Utils\Collection();
        $this->webhooks = new \CharlotteDunois\Yasmin\Utils\Collection();
        
        foreach($audit['users'] as $user) {
            $usr = $this->client->users->patch($user);
            $this->users->set($usr->id, $usr);
        }
        
        foreach($audit['webhooks'] as $webhook) {
            $hook = new \CharlotteDunois\Yasmin\Models\Webhook($this->client, $webhook);
            $this->webhooks->set($hook->id, $hook);
        }
        
        foreach($audit['audit_log_entries'] as $entry) {
            $log = new \CharlotteDunois\Yasmin\Models\AuditLogEntry($this->client, $this, $entry);
            $this->entries->set($log->id, $log);
        }
    }
    
    /**
     * {@inheritdoc}
     * @internal
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        return parent::__get($name);
    }
}
