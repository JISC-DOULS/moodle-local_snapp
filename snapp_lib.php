<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 * Classes used in SNAPP plugin
 *
 * @package    local
 * @subpackage snapp
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');

if (!class_exists('OAuthException')) {
    //Include OAuth library from: http://code.google.com/p/oauth/
    include_once(dirname(__FILE__).'/oauth/oauth.php');
}
/**
 * Main library.
 * Plugin Config settings methods
 * General helper methods (usually static)
 * @author j.platts@open.ac.uk
 *
 */
class snapp_lib {

    /**
     * Main configuration key/value settings from config_plugins table
     * @var array
     */
    public $settings;


    /**
     * Call this function to 'initialise' this class - populates static vars etc
     * Config vals from DB are not automatically populated
     * Send true to get all config vars for this plugin loaded
     * (else they will be loaded individually on call = more DB calls)
     */
    public function __construct($getconfig = false) {
        $this->settings = new stdClass();
        if ($getconfig) {
            $this->settings = get_config('local_snapp');
        }
    }

    /**
     * Populate settings object with contents of local_snapp from config_plugins table
     */
    public function init_setting($name) {
        $this->settings->$name = get_config('local_snapp', $name);
    }

    /**
     * Sets the name/value admin setting locally (does not set to db...)
     * @param string $name
     * @param string $value
     */
    public function set_setting($name, $value) {
        $this->settings->$name = $value;
    }

    /**
     * Returns if the admin setting has turned enabled the plugin
     * returns Boolean
     */
    public function isenabled() {
        if (!isset($this->settings->enabled)) {
            $this->init_setting('enabled');
        }
        if (isset($this->settings->enabled)) {
            return (bool) $this->settings->enabled;
        } else {
            return false;
        }
    }

    /**
     * Returns the accesskey config setting - used as a salt in token generation
     * returns string
     */
    public function get_accesskey() {
        if (!isset($this->settings->accesskey)) {
            $this->init_setting('accesskey');//ensure settings initialised from DB
        }
        if (!empty($this->settings->accesskey)) {
            return $this->settings->accesskey;
        } else {
            return 'AxZXW15Y78UuPL=';//return a default if problem
        }
    }

    /**
     * Returns the secretkey config setting - used as key in hashing
     * returns string
     */
    public function get_secretkey() {
        if (!isset($this->settings->secretkey)) {
            $this->init_setting('secretkey');//ensure settings initialised from DB
        }
        if (!empty($this->settings->secretkey)) {
            return $this->settings->secretkey;
        } else {
            return 'YnI695TfzZbvL';//return a default if problem
        }
    }

    /**
     * Returns the stored id of the external_service_users record used for the web service
     * returns 0 if unset
     */
    public function get_wsuserid() {
        if (!isset($this->settings->wsuser)) {
            $this->init_setting('wsuser');//ensure settings initialised from DB
        }
        if (!empty($this->settings->wsuser)) {
            return $this->settings->wsuser;
        } else {
            return 0;//return a default if not set
        }
    }

    /**
     * Tries to get file from web and will return false if not found
     * (using download_file_content will return errors whne debugging is on if a problem)
     * @param string $url
     * returns string or false if can't download content
     */
    public static function get_from_web($url) {
        $url = clean_param($url, PARAM_URL);
        $content = download_file_content($url, null, null, true);
        //returning ful response to stop moodle errors being printed
        if ($content->status == 200) { //TODO perhaps too simplistic to only check for OK?
            $content = $content->results;
        } else {
            $content = false;
        }
        return $content;
    }

    /**
     * Returns a container record from the container table
     * @param string $name
     */
    public static function get_container_fromname($name) {
        global $DB;
        //should be unique name!...
        //container names can either be name of container in table or consumerkey from key table (which confusingly links to a container!)
        //return $DB->get_record('local_snapp_containers', array('name' => $name));
        $sql = 'SELECT cont.* FROM {local_snapp_containers} as cont
                FULL JOIN {local_snapp_keys} as keys on keys.containerid = cont.id
                WHERE cont.name = ? OR keys.consumerkey = ?';
        return $DB->get_record_sql($sql, array($name, $name));
    }

    /**
     * Returns id of a container based on name
     * @param string $name
     */
    public static function get_containerid_fromname($name) {
        if ($result = self::get_container_fromname($name)) {
            return $result->id;
        } else {
            return false;
        }
    }

    /**
     * Adds a new container to DB (names are unique)
     */
    public static function insert_container($name) {
        global $DB;
        //should be unique name...
        $record = new stdClass();
        $record->name = $name;
        $record->enabled = 1;
        return $DB->insert_record('local_snapp_containers', $record, true);
    }

    /**
     * Returns all conatiner records in db
     */
    public static function get_containers() {
        global $DB;
        return $DB->get_records('local_snapp_containers');
    }

    /**
     * Returns true if container in db has been disabled
     * @param string $name
     */
    public static function iscontainer_name_disabled($name) {
        if ($result = self::get_container_fromname($name)) {
            if ($result->enabled == 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Set container enabled state
     * @param INT $id
     * @param INT $enabled (0 or 1)
     */
    public static function set_container_enabled($id, $enabled) {
        global $DB;
        return $DB->set_field('local_snapp_containers', 'enabled', $enabled, array('id' => $id));
    }

    /**
     * Deletes a container record and all other records
     * in the plugin that refer to it
     * @param INT $id - id of the container record
     */
    public static function delete_container($id) {
        global $DB;
        $trans = $DB->start_delegated_transaction();
        //delete users
        $DB->delete_records('local_snapp_users', array('containerid' => $id));
        //delete certs
        $DB->delete_records('local_snapp_certs', array('containerid' => $id));
        //delete keys
        $DB->delete_records('local_snapp_keys', array('containerid' => $id));
        //delete container
        $DB->delete_records('local_snapp_containers', array('id' => $id));
        $trans->allow_commit();
    }

    /**
     * Returns array (value=>label) of external services users that can be used in config drop-down etc
     * Only uses services with restricted users set (thefore not expecting that many records)
     * Returns array
     */
    public static function return_external_services_users() {
        global $DB;
        $sql = 'SELECT esu.id, es.name, u.username from {external_services_users} esu
                JOIN {external_services} es on esu.externalserviceid = es.id
                JOIN {user} u on esu.userid = u.id
                WHERE es.enabled = 1 and es.restrictedusers = 1';
        $users = $DB->get_records_sql($sql);
        $returnarray = array(0 => '');
        foreach ($users as $result => $resultval) {
            $returnarray[$resultval->id] = get_string('externalservice', 'webservice') . ':'
                . $resultval->name . ' - ' . get_string('username') . ':' . $resultval->username;
        }
        return $returnarray;
    }
}

/**
 * Static library class handling security when connecting to snapp
 * Also handles db table containing security certificates
 * @author j.platts@open.ac.uk
 *
 */
class snapp_security {
    /**
     * Uses Oauth lib to verify whether an opensocial signed request is genuine
     * Supports requests signed using:
     * RSA_SHA1
     * HMAC-SHA1
     * @param array $req request array (e.g. $_REQUEST)
     * returns OBJECT
     * ->success BOOLEAN
     * ->error STRING Any errors generated
     * ->debug STRING Debug information
     */
    public static function verify_opensocial_request($req = array('empty')) {
        //set param default (can't set to global in func declaration)
        if (isset($req[0]) && $req[0] == 'empty') {
            $req = $_REQUEST;
        }

        $response = new stdClass();
        $response->success = false;
        $response->error = '';
        $response->debug = 'Verifying request.';

        $sigtype = '';
        //Check $_REQUEST for a supported OAuth signature
        if (!empty($req['oauth_signature_method'])) {
            if (strtolower($req['oauth_signature_method']) == 'rsa-sha1') {
                $sigtype = 'RSA-SHA1';
            } else if (strtolower($req['oauth_signature_method']) == 'hmac-sha1') {
                $sigtype = 'HMAC-SHA1';
            }
        }

        //Build a request object from the current request
        $request = OAuthRequest::from_request(null, null, $req);

        //Initialize the new signature method
        switch ($sigtype) {
            case 'RSA-SHA1':
                $response->debug .= "\rRSA-SHA1 method detected.";
                $signaturemethod = new snapp_sig_rsasha1();
                //Check the request signature (db interaction is in cert class)
                try {
                    $signaturevalid = $signaturemethod->check_signature($request, null, null, $request->get_parameter('oauth_signature'));
                } catch (moodle_exception $e) {
                    $response->error = $e->errorcode;
                    return $response;
                }
                break;
            case 'HMAC-SHA1':
                $response->debug .= "\rHMAC-SHA1 method detected.";
                $signaturemethod = new OAuthSignatureMethod_HMAC_SHA1();
                //Check that we have consumer key/secret the request signature
                try {
                    //get request consumer key
                    $consumerkey = $request->get_parameter('oauth_consumer_key');
                    //check if match in db and get consumer secret
                    $consumer = new stdClass();
                    if (!$consumer->secret = self::get_consumer_secret($consumerkey)) {
                        throw new moodle_exception('notregistered', 'local_snapp');
                    }
                    //check conatiner not disabled
                    if (snapp_lib::iscontainer_name_disabled($consumerkey)) {
                        throw new moodle_exception('disabledcont', 'local_snapp');//Container disabled so don't verify
                    }
                    //check signature
                    $token = new stdClass();
                    $token->secret = '';// no token secret

                    $signaturevalid = $signaturemethod->check_signature($request, $consumer, $token,
                        $request->get_parameter('oauth_signature'));
                } catch (moodle_exception $e) {
                    $response->error = $e->errorcode;
                    return $response;
                }
                break;
            default:
                $response->debug .= "\rNo compatible Oauth signature method detected.";
                $response->error = "notsigned";
                return $response;
        }

        //Test success
        if ($signaturevalid == true) {
            $response->debug .= "\rValidation successfull.";
            $response->success = true;
        } else {
            $response->error = "validation_failed";
        }

        return $response;
    }

    /**
     * Returns the openscocial container name based on signed request param (RSA-SHA1)
     * @param $req (see get_val_from_request)
     * returns string of container name or empty
     */
    public static function get_os_container($req = array('empty')) {
        if (self::get_val_from_request('oauth_consumer_key', $req)) {
            return self::get_val_from_request('oauth_consumer_key', $req);
        } else if (self::get_val_from_request('opensocial_container', $req)) {
            return self::get_val_from_request('opensocial_container', $req);
        } else {
            return '';
        }
    }

    /**
     * Returns the openscocial container name based on opensocial_container
     * @param $req (see get_val_from_request)
     */
    public static function get_os_container_name($req = array('empty')) {
        if (self::get_val_from_request('opensocial_container', $req)) {
            return self::get_val_from_request('opensocial_container', $req);
        } else {
            return self::get_os_container($req);
        }
    }

    /**
     * Returns the openscocial public key name based on signed request param
     * @param $req (see get_val_from_request)
     * returns string of key name or empty
     */
    public static function get_os_pubkey($req = array('empty')) {
        if (self::get_val_from_request('xoauth_signature_publickey', $req)) {
            return self::get_val_from_request('xoauth_signature_publickey', $req);
        } else if (self::get_val_from_request('xoauth_publickey', $req)) {
            return self::get_val_from_request('xoauth_publickey', $req);
        } else {
            return '';
        }
    }

    /**
     * Get owner id from an opensocial signed request
     * @param unknown_type $req
     */
    public static function get_os_ownerid($req = array('empty')) {
        return self::get_val_from_request('opensocial_owner_id', $req);
    }
    /**
     * Get viewer id from an opensocial signed request
     * @param unknown_type $req
     */
    public static function get_os_viewerid($req = array('empty')) {
        return self::get_val_from_request('opensocial_viewer_id', $req);
    }

    /**
     * Returns the value from a request
     * @param $req can be array, oauth request obj or empty array ($_REQUEST will be used)
     * @param string $val
     * returns value or false if not found
     */
    private static function get_val_from_request($val, $req = array('empty')) {
        if (is_array($req)) {
            if (isset($req[0]) && $req[0] == 'empty') {
                $req = $_REQUEST;
            }
            if (isset($req[$val])) {
                return $req[$val];
            }
        } else if ($req instanceof OAuthRequest) {
            if ($req->get_parameter($val) !== null) {
                return $req->get_parameter($val);
            }
        }
        return false;
    }


    /**
     * Gets certificate contents from db
     * @param string $container The container name
     * @param string $keyname
     * returns string (or false if not in db)
     */
    public static function get_cert($container, $keyname) {
        global $DB;

        if ($result = $DB->get_record_sql('SELECT cert FROM {local_snapp_certs} AS certtab
            INNER JOIN {local_snapp_containers} AS conttab ON conttab.id = certtab.containerid
            WHERE conttab.name = :container AND certtab.keyname = :keyname',
                array('container' => $container, 'keyname' => $keyname))) {
            return $result->cert;
        } else {
            return false;
        }
    }

    /**
     * Inserts certificate record (along with associated container) into db table
     * Checks validity of data before inserting
     * You can still insert certs in disabled containers
     * @param string $container name of the container
     * @param string $keyname
     * @param string $cert
     * returns boolean success
     */
    public static function insert_cert($container, $keyname, $cert) {
        global $DB;
        //data verification etc

        //certs should begin with -----BEGIN CERTIFICATE-----
        if (strpos($cert, '-----BEGIN CERTIFICATE-----') !== 0) {
            //expected format of cert check failed
            return false;
        }

        //see if container exists, and get id number
        $containerid = 0;
        if (!$result = snapp_lib::get_container_fromname($container)) {
            $containerid = snapp_lib::insert_container($container);
        } else {
            $containerid = $result->id;
        }

        //check for existing record, if so don't do anything
        if ($DB->record_exists('local_snapp_certs', array('containerid' => $containerid, 'keyname' => $keyname))) {
            return false;
        }
        $certrec = new stdClass();
        $certrec->keyname = $keyname;
        $certrec->containerid = $containerid;
        $certrec->cert = $cert;
        //Try and save to db

        $result = $DB->insert_record('local_snapp_certs', $certrec);
        return $result;
    }

    /**
     * Returns results of get_records for all certs table
     * Container id is 'resolved' to container name
     */
    public static function get_certs() {
        global $DB;
        return $DB->get_records_sql('SELECT certtab.*,conttab.name as container
        FROM {local_snapp_certs} AS certtab INNER JOIN {local_snapp_containers}
        AS conttab ON conttab.id = certtab.containerid');
        //return $DB->get_records('local_snapp_certs', null, 'container');
    }

    /**
     * Deletes certificate record
     * @param INT $id
     * returns false on error
     */
    public static function delete_cert($id) {
        global $DB;
        if (!$DB->record_exists('local_snapp_certs', array('id' => $id))) {
            return false;
        }
        return $DB->delete_records('local_snapp_certs', array('id' => $id));
    }

    /**
     * Returns the value of the consumer secret against consumer key
     * @param $key string
     * returns string
     */
    public static function get_consumer_secret($key) {
        global $DB;
        return $DB->get_field('local_snapp_keys', 'secret', array('consumerkey' => $key));
    }

    /**
     * Insert a consumer key/secret record into the database
     * @param string $consumerkey
     * @param string $secretkey
     * @param int $containerid
     * @param string $info
     */
    public static function insert_consumer_key($consumerkey, $secretkey, $containerid, $info) {
        global $DB;
        //check a valid containerid selected
        if (!$DB->record_exists('local_snapp_containers', array('id' => $containerid))) {
            return false;
        }
        $record = new stdClass();
        $record->consumerkey = $consumerkey;
        $record->secret = $secretkey;
        $record->containerid = $containerid;
        $record->info = $info;
        return $DB->insert_record('local_snapp_keys', $record);
    }

    /**
     * Returns all the consumer keys, including the name of their conatiner
     */
    public static function get_consumer_keys() {
        global $DB;
        return $DB->get_records_sql('SELECT keytab.*,conttab.name as container
        FROM {local_snapp_keys} AS keytab INNER JOIN {local_snapp_containers}
        AS conttab ON conttab.id = keytab.containerid');
    }

    /**
     * Deletes a consumer key record from db
     * @param int $id id of the record to delete
     */
    public static function delete_consumer_key($id) {
        global $DB;
        if (!$DB->record_exists('local_snapp_keys', array('id' => $id))) {
            return false;
        }
        return $DB->delete_records('local_snapp_keys', array('id' => $id));
    }

    /**
     * Create hmac hash using secret key from snapp config
     * @param string $string
     * @param snapp_lib $snapplibinstance
     * returns string
     */
    public static function hash($string, snapp_lib $snapplibinstance = null) {
        if ($snapplibinstance == null) {
            $snapplibinstance = new snapp_lib();
        }
        $secret = $snapplibinstance->get_secretkey();

        return hash_hmac('sha256', $string, $secret);
    }
}

/**
 * Extends OAuth RSA-SHA1 signature methods to handle getting certificates stored by snapp
 * public keys can be downloaded and added to db
 * @author j.platts@open.ac.uk
 *
 */
class snapp_sig_rsasha1 extends OAuthSignatureMethod_RSA_SHA1 {

    //Override so that certificate can be found in db or downloaded
    //Throws exceptions on error
    protected function fetch_public_cert(&$request) {
        global $CFG;
        require_once($CFG->libdir.'/filelib.php');
        //work out what cert we want
        $container = snapp_security::get_os_container($request);
        $keyname = snapp_security::get_os_pubkey($request);
        if ($container == '' || $keyname == '') {
            throw new moodle_exception('notsigned', 'local_snapp');//no cert details found...
        }

        //Check that the specified container is not disabled
        if (snapp_lib::iscontainer_name_disabled($container)) {
            throw new moodle_exception('disabledcont', 'local_snapp');//Container disabled so don't verify
        }

        //try and get from db
        $cert = snapp_security::get_cert($container, $keyname);
        if (!$cert) {
            //if not see if we can download cert (sometimes the provider will not give a valid url as you need to add to the db manually)
            //Are we allowed to download?
            $snapplib = new snapp_lib(true);
            if ($snapplib->settings->anycert != 1) {
                throw new moodle_exception('cert_error', 'local_snapp');
            }
            //if successfull add to db
            $content = snapp_lib::get_from_web($keyname);
            if (!$content) {
                throw new moodle_exception('cert_error', 'local_snapp');//not valid url for cert
            }

            if (snapp_security::insert_cert($container, $keyname, $content)) {
                return $content;
            } else {
                throw new moodle_exception('cert_error', 'local_snapp');//problem with cert e.g. not actual cert content
            }
        } else {
            return $cert;
        }

    }
    protected function fetch_private_cert(&$request) {
        //NOT SUPPORTED - FOR SIGNING REQUESTS
    }
}

/**
 * Static class handling output of gadget xml from moodle
 * @author j.platts@open.ac.uk
 *
 */
class snapp_gadgetxml {

    /**
     * Replaces known placeholders (see comments) in a string
     * @param string $string
     * returns string
     */
    public static function replace_placeholders($string) {
        //parse string for our identifier: %%something%%
        //get value of something and do something depending on what it is - these must be known so:
        //moodleurl ($CFG->wwwroot)
        global $CFG;

        //FIRST: get easy to swap identifiers with straight string replace
        $finds = array();
        $replaces = array();
        $finds[] = '%%moodleurl%%';
        $replaces[] = $CFG->wwwroot;

        if (stripos($string, '%%js%%') !== false) {
            $finds[] = '%%js%%';
            $pm = new snapp_gadgetxml_output_extend();
            $url = new moodle_url('/local/snapp/gadgetserv/lib.js');
            $gadgetjs = '<script type="text/javascript" src="' . $url . '"></script>';
            $replaces[] = $pm->do_yui3_head() . $gadgetjs;
        }

        $content = str_ireplace($finds, $replaces , $string);

        //SECOND: go through looking for any %%something%% left over
        //TODO use example at http://uk2.php.net/manual/en/function.sprintf.php#94608

        return $content;
    }

    /**
     * Outputs string to the browser as xml mime type
     * @param string $content
     * @param string $file filename that will be used
     */
    public static function output_xml($content, $file) {
        //work out the file name (always set mime to xml regardless)
        $filename = substr($file, strrpos($file, '/'));

        send_file($content, $filename, 'default', 0, true, false, 'application/xml');
    }
}

/**
 * Class that extends page_requirements manager so that we can get YUI tags
 * @author j.platts@open.ac.uk
 *
 */
class snapp_gadgetxml_output_extend extends page_requirements_manager {
    /**
     * Add a new function that will return the protected get_yui3lib_headcode function
     */
    public function do_yui3_head() {
        return $this->get_yui3lib_headcode();
    }
}

/**
 * Static library handling the mapping of opensocial and moodle users using the snapp_users table
 * @author j.platts@open.ac.uk
 *
 */
class snapp_mapuser {

    /**
     * Checks for record of user mapping in the database table
     * @param string $ownerid
     * @param string $containerid
     * returns boolean
     */
    public static function user_map_exists($ownerid, $containerid) {
        global $DB;
        return $DB->record_exists('local_snapp_users', array('ownerid' => $ownerid, 'containerid' => $containerid));
    }

    /**
     * Returns the moodle user id of a user mapping
     * @param string $ownerid
     * @param string $containerid
     * returns id or false if no record found
     */
    public static function get_user_map_userid($ownerid, $containerid) {
        global $DB;
        return $DB->get_field('local_snapp_users', 'userid', array('ownerid' => $ownerid, 'containerid' => $containerid));
    }

    /**
     * Creates a user entry in the local_snapp_users table
     * Can check for existance (optional)
     * @param string $ownerid
     * @param INT $containerid must match a valid container id
     * @param INT $userid must be a valid user id
     * @param BOOL $checkexists - set to true to check a matching record exists
     * Returns boolean - true if OK or false if record exists or write error
     */
    public static function create_user_map($ownerid, $containerid, $userid, $checkexists = true) {
        global $DB;
        if ($checkexists) {
            //check that an existing record does not already exist (for this or another used)
            if ($record = $DB->get_record('local_snapp_users', array('ownerid' => $ownerid, 'containerid' => $containerid), 'userid')) {
                if ($record->userid == $userid) {
                    return true;//fine, already exists
                } else {
                    return false;//different moodle user is trying to map to an existing mapping
                }
            }
        }
        $newrec = new stdClass();
        $newrec->ownerid = $ownerid;
        $newrec->containerid = $containerid;
        $newrec->userid = $userid;
        $newrec->added = time();
        return $DB->insert_record('local_snapp_users', $newrec);
    }

    /**
     * Deletes a record from local_snapp_users
     * @param int $recid - record id to delete
     * @param int $checkuserid - user id to check record belongs to
     * returns result
     */
    public static function delete_user_map($recid, $checkuserid = -1) {
        global $DB;
        if ($checkuserid != -1) {
            //Check record requested to delete belongs to this user
            if (!$DB->record_exists('local_snapp_users', array('id' => $recid, 'userid' => $checkuserid))) {
                return false;
            }
        }
        return $DB->delete_records('local_snapp_users', array('id' => $recid));
    }

    /**
     * Returns all know user mappings for this user
     * Container name is also added
     * @param INT $userid
     * returns DB result
     */
    public static function get_users_map($userid) {
        global $DB;
        $sql = 'SELECT users.*, conttab.name as container FROM {local_snapp_users} AS users
                INNER JOIN {local_snapp_containers} AS conttab
                ON conttab.id = users.containerid
                WHERE users.userid = ?';
        return $DB->get_records_sql($sql, array($userid));
    }

    /**
     * Returns all users that have a user mapping
     * @param int $count : set by this func - number of records total
     * @param int $from: limit from
     * @param int $limit
     */
    public static function get_all_users_map(&$count, $from = 0, $limit = 25) {
        global $DB;
        $count = $DB->count_records_sql('SELECT COUNT(DISTINCT userid) FROM {local_snapp_users}');
        $sql = 'SELECT DISTINCT users.id, users.username FROM {local_snapp_users} AS snappusers
                JOIN {user} AS users
                ON snappusers.userid = users.id
                ORDER BY users.username';

        return $DB->get_records_sql($sql, array(), $from, $limit);
    }

    /**
     * Creates the token used to verify user mapping of OS details
     * @param string $ownerid
     * @param string $container
     * @param snapp_lib $snapplibinstance
     * returns string (hash token)
     */
    public static function create_map_token($ownerid, $container, snapp_lib $snapplibinstance = null) {
        if ($snapplibinstance == null) {
            $snapplibinstance = new snapp_lib();
        }
        $secret = $snapplibinstance->get_accesskey();

        $tokenstr = $ownerid.$secret.$container;

        return snapp_security::hash($tokenstr, $snapplibinstance);
    }

    /**
     * Checks token
     * @param string $token
     * @param string $ownerid
     * @param string $container
     * @param snapp_lib $snapplibinstance
     */
    public static function check_map_token($token, $ownerid, $container, snapp_lib $snapplibinstance = null) {
        if ($token != self::create_map_token($ownerid, $container, $snapplibinstance)) {
            return false;
        }
        return true;
    }
}
