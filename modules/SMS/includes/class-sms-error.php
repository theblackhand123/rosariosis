<?php

namespace plugins\SMS\includes;
/**
 * SMS Error
 */
class SMS_Error
{
    private $message;

    public function __construct($context, $message)
    {
        global $error;

        $this->message = $message;

        // Register same error only once.
        foreach ($error as $error_msg) {
            if ($error_msg === $this->message) {
                return false;
            }
        }

        $error[] = $this->message;

        return false;
    }

    public function get_error_message()
    {
        return $this->message;
    }
}
