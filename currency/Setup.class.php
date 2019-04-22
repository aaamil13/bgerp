<?php


/**
 * Толеранс за валутния курс
 */
defIfNot('EXCHANGE_DEVIATION', '0.05');


/**
 * class currency_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъра Currency
 *
 *
 * @category  bgerp
 * @package   currency
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class currency_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'currency_Currencies';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Необходими пакети
     */
    var $depends = 'drdata=0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "Валути и хронология на техните курсове";
    

    /**
     * Описание на конфигурационните константи за този модул
     */
    var $configDescription = array(
		'EXCHANGE_DEVIATION' => array ('percent', 'mandatory, caption=Толеранс за валутния курс->Процент'),
);
    

    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'currency_Currencies',
            //'currency_CurrencyGroups',
            'currency_CurrencyRates',
            'currency_FinIndexes'
        );
    

    /**
     * Роли за достъп до модула
     */
    var $roles = 'currency';
    

    /**
     * Връзки от менюто, сочещи към модула
     */
//     var $menuItems = array(
//             array(2.2, 'Финанси', 'Валути', 'currency_Currencies', 'default', "ceo,admin,cash,bank,currency,acc"),
//         );

 

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
