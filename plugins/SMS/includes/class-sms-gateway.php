<?php

namespace plugins\SMS\includes;
/**
 * SMS gateway class
 *
 * @category   class
 * @package    SMS
 * @version    1.0
 */
class SMS_Gateway
{

    /**
     * @return mixed|void
     */
    public static function gateway()
    {
        $gateways = [
            'global' => [
                'experttexting' => 'experttexting.com',
                'fortytwo' => 'fortytwo.com',
                'smsglobal' => 'smsglobal.com',
                'gatewayapi' => 'gatewayapi.com',
                'easysendsms' => 'easysendsms.com',
                'cheapglobalsms' => 'cheapglobalsms.com',
                'spirius' => 'spirius.com',
                'plugins\SMS\includes\gateways\_1s2u' => '1s2u.com',
            ],
            'united kingdom' => [
                'plugins\SMS\includes\gateways\_textplode' => 'textplode.com',
                'textanywhere' => 'textanywhere.net',
            ],
            'france' => [
                'primotexto' => 'primotexto.com',
                'mtarget' => 'mtarget.fr',
            ],
            'brazil' => [
                'sonoratecnologia' => 'sonoratecnologia.com.br',
                'torpedos' => 'torpedos.pro',
            ],
            'germany' => [
                'sms77' => 'sms77.io',
            ],
            'turkey' => [
                'bulutfon' => 'bulutfon.com',
            ],
            'austria' => [
                'smsgateway' => 'sms-gateway.at',
            ],
            'spain' => [
                'afilnet' => 'afilnet.com',
                'labsmobile' => 'labsmobile.com',
            ],
            'new zealand' => [
                'unisender' => 'unisender.com',
            ],
            'polish' => [
                'smsapi' => 'smsapi.pl',
            ],
            'italy' => [
                // 'dot4all'    => 'dot4all.it',
                'smshosting' => 'smshosting.it',
                'aruba' => 'aruba.it',
            ],
            'denmark' => [
                'suresms' => 'suresms.com',
            ],
            'slovakia' => [
                'eurosms' => 'eurosms.com',
            ],
            'india' => [
                'shreesms' => 'shreesms.net',
                'instantalerts' => 'springedge.com',
                'smsgatewayhub' => 'smsgatewayhub.com',
                // 'smsgatewaycenter' => 'smsgateway.center',
                // 'itfisms'          => 'itfisms.com',
                'pridesms' => 'pridesms.in',
                'smsozone' => 'ozonesms.com',
                'msgwow' => 'msgwow.com',
                'mobtexting' => 'mobtexting.com',
            ],
            /*'pakistan'       => array(
                // 'difaan' => 'difaan',
            ),*/
            'africa' => [
                'plugins\SMS\includes\gateways\_ebulksms' => 'ebulksms.com',
                'africastalking' => 'africastalking.com',
                'alchemymarketinggm' => 'alchemymarketinggm.com',
                'eazismspro' => 'eazismspro.com',
            ],
            'cyprus' => [
                'websmscy' => 'websms.com.cy',
            ],
            'arabic' => [
                'gateway' => 'gateway.sa',
                'resalaty' => 'resalaty.com',
                'asr3sms' => 'asr3sms.com',
                'oursms' => 'oursms.net',
            ],
            'israel' => [
                'smss' => 'smss.co.il',
            ],
            'iran' => [
                // 'iransmspanel'   => 'iransmspanel.ir',
                // 'chaparpanel'    => 'chaparpanel.ir',
                // 'markazpayamak'  => 'markazpayamak.ir',
                // 'adpdigital'     => 'adpdigital.com',
                // 'hostiran'       => 'hostiran.net',
                // 'farapayamak'    => 'farapayamak.com',
                'smsde' => 'smsde.ir',
                // 'payamakde'      => 'payamakde.ir',
                // 'panizsms'       => 'panizsms.com',
                // 'sepehritc'      => 'sepehritc.com',
                // 'payameavval'    => 'payameavval.com',
                // 'smsclick'       => 'smsclick.ir',
                // 'persiansms'     => 'persiansms.com',
                // 'ariaideh'       => 'ariaideh.com',
                // 'sms_s'          => 'modiresms.com',
                // 'sadat24'        => 'sadat24.ir',
                // 'smscall'        => 'smscall.ir',
                // 'tablighsmsi'    => 'tablighsmsi.com',
                // 'paaz'           => 'paaz.ir',
                // 'textsms'        => 'textsms.ir',
                // 'jahanpayamak'   => 'jahanpayamak.info',
                // 'opilo'          => 'opilo.com',
                // 'barzinsms'      => 'barzinsms.ir',
                // 'smsmart'        => 'smsmart.ir',
                // 'loginpanel'     => 'loginpanel.ir',
                // 'imencms'        => 'imencms.com',
                // 'tcisms'         => 'tcisms.com',
                // 'caffeweb'       => 'caffeweb.com',
                // 'nasrpayam'      => 'nasrPayam.ir',
                'smsbartar' => 'sms-bartar.com',
                // 'fayasms'        => 'fayasms.ir',
                'payamresan' => 'payam-resan.com',
                // 'mdpanel'        => 'ippanel.com',
                // 'payameroz'      => 'payameroz.ir',
                'niazpardaz' => 'niazpardaz.com',
                // 'niazpardazcom'  => 'niazpardaz.com - New',
                // 'hisms'          => 'hi-sms.ir',
                // 'joghataysms'    => '051sms.ir',
                // 'mediana'        => 'mediana.ir',
                // 'aradsms'        => 'arad-sms.ir',
                // 'asiapayamak'    => 'payamak.asia',
                // 'sharifpardazan' => '2345.ir',
                // 'sarabsms'       => 'sarabsms.ir',
                // 'ponishasms'     => 'ponishasms.ir',
                // 'payamakalmas'   => 'payamakalmas.ir',
                // 'sms'            => 'sms.ir - Old',
                'plugins\SMS\includes\gateways\sms_new' => 'sms.ir',
                // 'popaksms'       => 'popaksms.ir',
                // 'novin1sms'      => 'novin1sms.ir',
                // '_500sms'        => '500sms.ir',
                // 'matinsms'       => 'smspanel.mat-in.ir',
                // 'iranspk'        => 'iranspk.ir',
                // 'freepayamak'    => 'freepayamak.ir',
                // 'itpayamak'      => 'itpayamak.ir',
                // 'irsmsland'      => 'irsmsland.ir',
                // 'avalpayam'      => 'avalpayam.com',
                // 'smstoos'        => 'smstoos.ir',
                // 'smsmaster'      => 'smsmaster.ir',
                // 'ssmss'          => 'ssmss.ir',
                // 'isun'           => 'isun.company',
                'idehpayam' => 'idehpayam.com',
                // 'smsarak'        => 'smsarak.ir',
                // 'novinpayamak'   => 'novinpayamak.com',
                // 'melipayamak'    => 'melipayamak.ir',
                // 'postgah'        => 'postgah.net',
                // 'smsfa'          => 'smsfa.net',
                // 'rayanbit'       => 'rayanbit.net',
                // 'smsmelli'       => 'smsmelli.com',
                // 'smsban'         => 'smsban.ir',
                // 'smsroo'         => 'smsroo.ir',
                // 'navidsoft'      => 'navid-soft.ir',
                'afe' => 'afe.ir',
                // 'smshooshmand'   => 'smshooshmand.com',
                'asanak' => 'asanak.ir',
                // 'payamakpanel'   => 'payamak-panel.com',
                // 'barmanpayamak'  => 'barmanpayamak.ir',
                // 'farazpayam'     => 'farazpayam.com',
                'plugins\SMS\includes\gateways\_0098sms' => '0098sms.com',
                // 'amansoft'       => 'amansoft.ir',
                // 'faraed'         => 'faraed.com',
                // 'spadbs'         => 'spadsms.ir',
                // 'bandarsms'      => 'bandarit.ir',
                // 'tgfsms'         => 'tgfsms.ir',
                // 'payamgah'       => 'payamgah.net',
                // 'sabasms'        => 'sabasms.biz',
                'chapargah' => 'chapargah.ir',
                // 'yashilsms'      => 'yashil-sms.ir',
                'ismsie' => 'isms.ir',
                // 'wifisms'        => 'wifisms.ir',
                // 'rayansmspanel'     => 'rayansmspanel.ir',
                // 'bestit'         => 'bestit.co',
                // 'pegahpayamak'   => 'pegah-payamak.ir',
                // 'adspanel'       => 'adspanel.ir',
                // 'mydnspanel'     => 'mydnspanel.com',
                // 'esms24'         => 'esms24.ir',
                // 'payamakaria'    => 'payamakaria.ir',
                // 'pichakhost'     => 'sitralweb.com',
                // 'tsms'           => 'tsms.ir',
                // 'parsasms'       => 'parsasms.com',
                // 'modiranweb'     => 'modiranweb.net',
                // 'smsline'        => 'smsline.ir',
                // 'iransms'        => 'iransms.co',
                // 'arkapayamak'    => 'arkapayamak.ir',
                // 'smsservice'     => 'smsservice.ir',
                // 'parsgreen'      => 'api.ir',
                // 'firstpayamak'   => 'firstpayamak.ir',
                // 'kavenegar'      => 'kavenegar.com',
                // '_18sms'         => '18sms.ir',
                // 'parandhost'     => 'parandhost.com',
                // 'eshare'         => 'eshare.com',
                // 'abrestan'       => 'abrestan.com',
            ],
            /*'other'          => array(
                // 'bearsms'  => 'bearsms.com',
            ),*/
        ];

        // Hook.
        do_action('SMS/includes/class-sms-gateway.php|gateway', [&$gateways]);

        return $gateways;
    }

    /**
     * @return bool
     */
    public static function status()
    {
        global $sms, $warning, $note;

        // Get credit
        $result = $sms->GetCredit();

        if (!$result) {

            // Update credit
            update_option('wp_last_credit', 0);

            $warning[] = _('Inactive!');

            return false;
        }

        // Update credit
        update_option('wp_last_credit', $result);

        $note[] = sprintf(_('Active! Account balance: %s'), $result);

        return true;
    }

    /**
     * @return mixed
     */
    public static function help()
    {
        global $sms;

        // Get gateway help
        return $sms->help;
    }

    /**
     * @return mixed
     */
    public static function from()
    {
        global $sms;

        // Get gateway from
        return $sms->from;
    }

    /**
     * @return bool
     */
    public static function bulk_status()
    {
        global $sms, $warning, $note;

        // Get bulk status
        if ($sms->bulk_send == true) {

            $note[] = _('Supported');

            return true;
        }

        $warning[] = _('Not supported!');

        return false;
    }

    /**
     * @return int
     */
    public static function credit()
    {
        global $sms;
        // Get credit
        $result = $sms->GetCredit();

        if (!$result) {
            return 0;
        }

        return $result;
    }
}
