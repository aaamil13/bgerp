<?php


/**
 * Имейл от който да се изпрати нотифициращ имейл че е направено запитване
 */
defIfNot('MARKETING_INQUIRE_FROM_EMAIL', '');


/**
 * Имейл на който да се изпрати нотифициращ имейл че е направено запитване
 */
defIfNot('MARKETING_INQUIRE_TO_EMAIL', '');


/**
 * Колко количества да се показват по дефолт във запитването
 */
defIfNot('MARKETING_INQUIRY_QUANTITIES', 3);





/**
 * Дали да се показва бюлетина
 * @deprecated
 */
defIfNot('MARKETING_USE_BULLETIN', 'no');


/**
 * След колко време формата да може да се показва повторно
 * 3 часа
 * @deprecated
 */
defIfNot('MARKETING_SHOW_AGAIN_AFTER', 10800);


/**
 * След колко време на бездействие да се покаже формата
 * @deprecated
 */
defIfNot('MARKETING_IDLE_TIME_FOR_SHOW', 20);


/**
 * След колко секунди да може да се стартира
 * @deprecated
 */
defIfNot('MARKETING_WAIT_BEFORE_START', 5);


/**
 * Заглавие на формата
 * @deprecated
 */
defIfNot('MARKETING_BULLETIN_FORM_TITLE', 'Искате ли да научавате всички новости за нас?');


/**
 * Съобщение при абониране
 * @deprecated
 */
defIfNot('MARKETING_BULLETIN_FORM_SUCCESS', 'Благодарим за абонамента за нашите новости');


/**
 * URL от където ще се взема JS файла
 * @deprecated
 */
defIfNot('MARKETING_BULLETIN_URL', '');


/**
 * Дали да се показва цялата форма или само имейла
 * @deprecated
 */
defIfNot('MARKETING_SHOW_ALL_FORM', 'no');


/**
 * Дали да се показва цялата форма или само имейла
 * @deprecated
 */
defIfNot('MARKETING_BULLETIN_IMG', '');


/**
 * Фон на прозореца на бюлетина
 * @deprecated
 */
defIfNot('MARKETING_BULLETIN_BACKGROUND', '');


/**
 * Цвят на текста от бюлетина
 * @deprecated
 */
defIfNot('MARKETING_BULLETIN_TEXTCOLOR', '');


/**
 * Основен цвят за бутона на бюлетина
 * @deprecated
 */
defIfNot('MARKETING_BULLETIN_BUTTONCOLOR', '');

/**
 * Маркетинг - инсталиране / деинсталиране
 *
 *
 * @category  bgerp
 * @package   marketing
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg> и Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class marketing_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'marketing_Inquiries2';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Маркетинг и реклама";
    
    
    /**
	 * Описание на конфигурационните константи
	 */
	var $configDescription = array(
			'MARKETING_INQUIRE_FROM_EMAIL'  => array('key(mvc=email_Inboxes,select=email,allowEmpty)', 'caption=Изпращане на запитването по имейл->Имейл \'От\''),
			'MARKETING_INQUIRE_TO_EMAIL'    => array('emails', 'caption=Изпращане на запитването по имейл->Имейл \'Към\''),
			'MARKETING_INQUIRY_QUANTITIES'          => array('int', 'caption=Брой количества във запитването'),
	        
	        //@deprecated
	        'MARKETING_USE_BULLETIN' => array('enum(yes=Да, no=Не)', 'caption=Дали да се показва бюлетина->Избор'),
	        'MARKETING_SHOW_ALL_FORM' => array('enum(yes=Да, no=Не)', 'caption=Дали да се показва цялата форма или само имейла->Избор'),
	        'MARKETING_BULLETIN_URL' => array('url', 'caption=От къде да се взема JS файла->URL'),
	        'MARKETING_BULLETIN_FORM_TITLE' => array('varchar(128)', 'caption=Заглавие на формата на бюлетина->Текст'),
	        'MARKETING_BULLETIN_FORM_SUCCESS' => array('varchar(128)', 'caption=Съобщение при абониране->Текст'),
	        'MARKETING_SHOW_AGAIN_AFTER' => array('time(suggestions=3 часа|12 часа|1 ден)', 'caption=Изчакване преди ново отваряне->Време'),
	        'MARKETING_IDLE_TIME_FOR_SHOW' => array('time(suggestions=5 секунди|20 секунди|1 мин)', 'caption=Период за бездействие преди активиране->Време'),
	        'MARKETING_WAIT_BEFORE_START' => array('time(suggestions=3 секунди|5 секунди|10 секунди)', 'caption=След колко време да може да стартира бюлетина->Време'),
	        'MARKETING_BULLETIN_IMG' => array('fileman_FileType(bucket=pictures)', 'caption=Картинка за успешна регистрация->Изображение'),
            'MARKETING_BULLETIN_BACKGROUND' => array('color_Type', 'caption=Цветове за бюлетина->Цвят на фона'),
            'MARKETING_BULLETIN_TEXTCOLOR' => array('color_Type', 'caption=Цветове за бюлетина->Цвят на текста'),
            'MARKETING_BULLETIN_BUTTONCOLOR' => array('color_Type', 'caption=Цветове за бюлетина->Цвят на бутона')
	);
	
	
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
    		'marketing_Inquiries2',
            'marketing_Bulletins',
            'marketing_BulletinSubscribers'
        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = 'marketing';

    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.1, 'Търговия', 'Маркетинг', 'marketing_Inquiries2', 'default', "ceo, marketing"),
        );

    
	/**
     * Инсталиране на пакета
     */
    function install()
    {
    	$html = parent::install();
    	
    	// Добавяне на кофа за файлове свързани със задаията
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('InquiryBucket', 'Снимки', 'jpg,jpeg,image/jpeg,gif,png', '10MB', 'user', 'every_one');
        
        $Plugins = cls::get('core_Plugins');
        $html .= $Plugins->forcePlugin('Бюлетин за външната част', 'marketing_BulletinPlg', 'cms_page_External', 'private');
        
        return $html;
    }
}