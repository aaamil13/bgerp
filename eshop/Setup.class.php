<?php


/**
 * Колко секунди да се кешира съдържанието за не PowerUsers
 */
defIfNot('ESHOP_BROWSER_CACHE_EXPIRES', 3600);


/**
 * Минимален брой групи, необходими за да се покаже страничната навигация
 */
defIfNot('ESHOP_MIN_GROUPS_FOR_NAVIGATION', 4);


/**
 * Име на кошницата във външната част
 */
defIfNot('ESHOP_CART_EXTERNAL_NAME', 'Количка');


/**
 * Текст в магазина ако артикулът не е наличен
 */
defIfNot('ESHOP_NOT_IN_STOCK_TEXT', 'Няма наличност');


/**
 * Дефолтен шаблон за онлайн продажби на български
 */
defIfNot('ESHOP_SALE_DEFAULT_TPL_BG', '');


/**
 * Дефолтен шаблон за онлайн продажби на английски
 */
defIfNot('ESHOP_SALE_DEFAULT_TPL_EN', '');


/**
 * Кое поле да е задължително при изпращане на запитване или поръчка във външната част
 */
defIfNot('ESHOP_MANDATORY_CONTACT_FIELDS', 'person');


/**
 * class cat_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с продуктите
 *
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class eshop_Setup extends core_ProtoSetup
{
    /**
     * Версията на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'eshop_Groups';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Уеб каталог с продукти и услуги за сайта';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'eshop_Groups',
        'eshop_Products',
        'eshop_Settings',
        'eshop_ProductDetails',
        'eshop_Carts',
        'eshop_CartDetails',
        'migrate::updateContactData',
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = 'eshop';
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(3.55, 'Сайт', 'Е-маг', 'eshop_Groups', 'default', 'ceo, eshop'),
    );
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'ESHOP_BROWSER_CACHE_EXPIRES' => array('time', 'caption=Кеширане в браузъра->Време'),
        'ESHOP_MIN_GROUPS_FOR_NAVIGATION' => array('int', 'caption=Минимален брой групи за навигация->Брой'),
        'ESHOP_CART_EXTERNAL_NAME' => array('varchar', 'caption=Стрингове във външната част->Кошница'),
        'ESHOP_NOT_IN_STOCK_TEXT' => array('varchar', 'caption=Стрингове във външната част->Липса на наличност'),
        'ESHOP_SALE_DEFAULT_TPL_BG' => array('key(mvc=doc_TplManager,allowEmpty)', 'caption=Шаблон за онлайн продажба->Български,optionsFunc=sales_Sales::getTemplateBgOptions'),
        'ESHOP_SALE_DEFAULT_TPL_EN' => array('key(mvc=doc_TplManager,allowEmpty)', 'caption=Шаблон за онлайн продажба->Английски,optionsFunc=sales_Sales::getTemplateEnOptions'),
        'ESHOP_MANDATORY_CONTACT_FIELDS' => array('enum(company=Фирма,person=Лице,both=Двете)', 'caption=Задължителни контактни данни за количката->Поле'),
    );
    
    
    /**
     * Настройки за Cron
     */
    public $cronSettings = array(
        array(
            'systemId' => 'Delete Carts',
            'description' => 'Изтриване на старите колички',
            'controller' => 'eshop_Carts',
            'action' => 'DeleteDraftCarts',
            'period' => 60,
            'offset' => 30,
            'timeLimit' => 100
        ),
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('eshopImages', 'Илюстрации в емаг', 'jpg,jpeg,png,bmp,gif,image/*', '10MB', 'user', 'every_one');
        
        $Plugins = cls::get('core_Plugins');
        $html .= $Plugins->installPlugin('Разширяване на външната част за онлайн магазина', 'eshop_plg_External', 'cms_page_External', 'private');
        $html .= $Plugins->installPlugin('Разширяване на потребителите свързана с външната част', 'eshop_plg_Users', 'core_Users', 'private');
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    public function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData($itr = '')
    {
        $res = parent::loadSetupData($itr);
        $config = core_Packs::getConfig('eshop');
        
        $tplArr = array();
        $tplArr[] = array('name' => 'Online sale', 'content' => 'eshop/tpl/OnlineSaleEn.shtml', 'lang' => 'en');
        $tplArr[] = array('name' => 'Онлайн продажба', 'content' => 'eshop/tpl/OnlineSaleBg.shtml', 'lang' => 'bg');
        $res .= doc_TplManager::addOnce('sales_Sales', $tplArr);
        
        // Поставяне на първия намерен шаблон на английски за дефолтен на продажбата
        if (strlen($config->ESHOP_SALE_DEFAULT_TPL_BG) === 0) {
            $templateBgId = doc_TplManager::fetchField("#name = 'Онлайн продажба'");
            core_Packs::setConfig('eshop', array('ESHOP_SALE_DEFAULT_TPL_BG' => $templateBgId));
        }
        
        // Поставяне на първия намерен шаблон на английски за дефолтен на продажбата
        if (strlen($config->ESHOP_SALE_DEFAULT_TPL_EN) === 0) {
            $templateEnId = doc_TplManager::fetchField("#name = 'Online sale'");
            core_Packs::setConfig('eshop', array('ESHOP_SALE_DEFAULT_TPL_EN' => $templateEnId));
        }
        
        return $res;
    }
    
    
    /**
     * Миграция на уеб константа
     */
    function updateContactData()
    {
        $conf = core_Packs::getConfig('bgerp');
        $value = $conf->_data['BGERP_MANDATORY_CONTACT_FIELDS'];
        $exValue = eshop_Setup::get('MANDATORY_CONTACT_FIELDS');
        
        if(!empty($value) && $exValue != $value && in_array($value, array('company', 'person', 'both'))){
            core_Packs::setConfig('eshop', array('ESHOP_MANDATORY_CONTACT_FIELDS' => $value));
        }
    }
}
