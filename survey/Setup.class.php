<?php


/**
 * class survey_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъра Survey
 *
 *
 * @category  bgerp
 * @package   bank
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class survey_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'survey_Surveys';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Анкети и гласувания";
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var  $managers = array(
            'survey_Surveys',
            'survey_Alternatives',
            'survey_Votes',
        	'survey_Options',
        );
    

    /**
     * Роли за достъп до модула
     */
    var $roles = 'survey';
    

    /**
     * Връзки от менюто, сочещи към модула
     */
//     var $menuItems = array(
//             array(2.46, 'Обслужване', 'Анкети', 'survey_Surveys', 'default', "survey, ceo"),
//         );
    
	
    /**
     * Път до js файла
     */
//    var $commonJS = 'survey/js/scripts.js';
    
    
    /**
     * Път до css файла
     */
//    var $commonCSS = 'survey/tpl/css/styles.css';
    
    
	/**
     * Инсталиране на пакета
     */
    function install()
    {
        $html = parent::install();
        
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('survey_Images', 'Снимки', 'jpg,jpeg,image/jpeg,gif,png', '6MB', 'user', 'every_one');
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
}