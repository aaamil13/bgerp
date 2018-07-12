<?php


/**
 * Инсталаотор на плъгин за добавяне на бутона за преглед на документи в zoho.com
 * Разширения: pps,odt,ods,odp,sxw,sxc,sxi,wpd,rtf,csv,tsv
 *
 *
 * @category  vendors
 * @package   zoho
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class zoho_Setup extends core_ProtoSetup
{
    public $deprecated = true;
    
    
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = '';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = '';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Преглед на документи с zoho.com';
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме
        $html .= $Plugins->forcePlugin('Преглед на документи с Zoho', 'zoho_Plugin', 'fileman_Files', 'private');
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    public function deinstall()
    {
        $html = parent::deinstall();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Премахваме от type_Keylist полета
        $Plugins->deinstallPlugin('zoho_Plugin');
        $html .= "<li>Премахнати са всички инсталации на 'zoho_Plugin'";
        
        return $html;
    }
}
