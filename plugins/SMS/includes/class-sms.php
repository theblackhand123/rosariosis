<?php

namespace plugins\SMS\includes;
/**
 * @category   class
 * @package    SMS
 * @version    1.0
 */
abstract class SMS
{

    /**
     * Webservice username
     *
     * @var string
     */
    public $username;

    /**
     * Webservice password
     *
     * @var string
     */
    public $password;

    /**
     * Webservice API/Key
     *
     * @var string
     */
    public $has_key = false;

    /**
     * Validation mobile number
     *
     * @var string
     */
    public $validateNumber = "";

    /**
     * Help to gateway
     *
     * @var string
     */
    public $help = false;

    /**
     * Bulk send
     *
     * @var boolean
     */
    public $bulk_send = true;

    /**
     * SMsS send from number
     *
     * @var string
     */
    public $from;

    /**
     * Send SMS to number
     *
     * @var string
     */
    public $to;

    /**
     * SMS text
     *
     * @var string
     */
    public $msg;

    /**
     * Constructors
     */
    public function __construct()
    {
    }

    public function InsertToDB($sender, $message, $recipient)
    {
        return true; // SMSSave( $message, $recipient );
    }

}
