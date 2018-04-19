<?php

/**
 * Задължителен параметър за експорт на ф-ра
 */
defIfNot('ACC_INVOICE_MANDATORY_EXPORT_PARAM', '');

/**
 * Колко дена преди края на месеца да се направи следващия бъдещ период чакащ
 */
defIfNot('ACC_DAYS_BEFORE_MAKE_PERIOD_PENDING', '');

/**
 * Стойност по подразбиране на актуалния ДДС (между 0 и 1)
 * Използва се по време на инициализацията на системата, при създаването на първия период
 */
defIfNot('ACC_DEFAULT_VAT_RATE', 0.20);

/**
 * Стойност по подразбиране на актуалния ДДС (между 0 и 1)
 * Използва се по време на инициализацията на системата, при създаването на първия период
 */
defIfNot('BASE_CURRENCY_CODE', 'BGN');

/**
 * Кои документи могат да са разходни пера
 */
defIfNot('ACC_COST_OBJECT_DOCUMENTS', '');

/**
 * Толеранс за допустимо разминаване на суми
 */
defIfNot('ACC_MONEY_TOLERANCE', '0.05');

/**
 * Колко реда да се показват в детайлния баланс
 */
defIfNot('ACC_DETAILED_BALANCE_ROWS', 500);

/**
 * Основание за неначисляване на ДДС за контрагент контрагент от държава в ЕС (без България)
 */
defIfNot('ACC_VAT_REASON_IN_EU', 'чл.53 от ЗДДС – ВОД');

/**
 * Основание за неначисляване на ДДС за контрагент извън ЕС
 */
defIfNot('ACC_VAT_REASON_OUTSIDE_EU', 'чл.28 от ЗДДС – износ извън ЕС');

/**
 * Роли за всички при филтриране
 */
defIfNot('ACC_SUMMARY_ROLES_FOR_ALL', 'ceo,admin');

/**
 * Роли за екипите при филтриране
 */
defIfNot('ACC_SUMMARY_ROLES_FOR_TEAMS', 'ceo,admin,manager');

/**
 * Ден от месеца за изчисляване на Счетоводна дата на входяща фактура
 */
defIfNot('ACC_DATE_FOR_INVOICE_DATE', '10');

/**
 * class acc_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани със счетоводството
 *
 *
 * @category bgerp
 * @package acc
 * @author Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license GPL 3
 * @since v 0.1
 */
class acc_Setup extends core_ProtoSetup
{

    /**
     * Версия на пакета
     */
    var $version = '0.1';

    /**
     * Необходими пакети
     */
    var $depends = 'currency=0.1';

    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'acc_Lists';

    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';

    /**
     * Описание на модула
     */
    var $info = "Двустранно счетоводство: Настройки, Журнали";

    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
        'acc_Lists',
        'acc_Items',
        'acc_Periods',
        'acc_Accounts',
        'acc_Limits',
        'acc_Balances',
        'acc_BalanceDetails',
        'acc_Articles',
        'acc_ArticleDetails',
        'acc_Journal',
        'acc_JournalDetails',
        'acc_Features',
        'acc_VatGroups',
        'acc_ClosePeriods',
        'acc_Operations',
        'acc_BalanceRepairs',
        'acc_BalanceRepairDetails',
        'acc_BalanceTransfers',
        'acc_ValueCorrections',
        'acc_FeatureTitles',
        'acc_CostAllocations',
        'migrate::removeUnusedRole',
        'migrate::recalcAllGlobalRole'
    );
    

    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
        'ACC_MONEY_TOLERANCE' => array(
            "double(decimals=2)",
            'caption=Толеранс за допустимо разминаване на суми в основна валута->Сума'
        ),
        'ACC_DETAILED_BALANCE_ROWS' => array(
            "int",
            'caption=Редове в страница от детайлния баланс->Брой редове,unit=бр.'
        ),
        'ACC_DAYS_BEFORE_MAKE_PERIOD_PENDING' => array(
            "time(suggestions= 1 ден|2 дена|7 Дена)",
            'caption=Колко дни преди края на месеца да се направи следващия бъдещ период чакащ->Дни'
        ),
        'ACC_VAT_REASON_OUTSIDE_EU' => array(
            'varchar',
            'caption=Основание за неначисляване на ДДС за контрагент->Извън ЕС'
        ),
        'ACC_VAT_REASON_IN_EU' => array(
            'varchar',
            'caption=Основание за неначисляване на ДДС за контрагент->От ЕС'
        ),
        'ACC_COST_OBJECT_DOCUMENTS' => array(
            'keylist(mvc=core_Classes,select=name)',
            "caption=Кои документи могат да бъдат разходни обекти->Документи,optionsFunc=acc_Setup::getDocumentOptions"
        ),
        'ACC_SUMMARY_ROLES_FOR_TEAMS' => array(
            'varchar',
            'caption=Роли за екипите при филтриране->Роли'
        ),
        'ACC_SUMMARY_ROLES_FOR_ALL' => array(
            'varchar',
            'caption=Роли за всички при филтриране->Роли'
        ),
        'ACC_DATE_FOR_INVOICE_DATE' => array(
            'int(min=1,max=31)',
            'caption=Ден от месеца за изчисляване на Счетоводна дата на входяща фактура->Ден'
        ),
        'ACC_INVOICE_MANDATORY_EXPORT_PARAM' => array(
            "key(mvc=cat_Params,select=name,allowEmpty)",
            'caption=Артикул за експорт на данъчна фактура->Параметър'
        )
    );

    /**
     * Роли за достъп до модула
     */
    var $roles = array(
        array(
            'seePrice'
        ),
        array(
            'invoicer'
        ),
        array(
            'accJournal'
        ),
        array(
            'accLimits'
        ),
        array(
            'allGlobal'
        ),
        array(
            'invoiceAll'
        ),
        array(
            'invoiceAllGlobal',
            'invoiceAll'
        ),
        array(
            'storeAll'
        ),
        array(
            'storeAllGlobal',
            'storeAll'
        ),
        array(
            'bankAll'
        ),
        array(
            'bankAllGlobal',
            'bankAll'
        ),
        array(
            'cashAll'
        ),
        array(
            'cashAllGlobal',
            'cashAll'
        ),
        array(
            'saleAll'
        ),
        array(
            'saleAllGlobal',
            'saleAll'
        ),
        array(
            'purchaseAll'
        ),
        array(
            'purchaseAllGlobal',
            'purchaseAll'
        ),
        array(
            'planningAll'
        ),
        array(
            'planningAllGlobal',
            'planningAll'
        ),
        array(
            'acc',
            'accJournal, invoicer, seePrice, invoiceAll, storeAll, bankAll, cashAll, saleAll, purchaseAll, planningAll'
        ),
        array(
            'accMaster',
            'acc, invoiceAllGlobal, storeAllGlobal, bankAllGlobal, cashAllGlobal, saleAllGlobal, purchaseAllGlobal, planningAllGlobal'
        ),
        array(
            'repAll'
        ),
        array(
            'repAllGlobal',
            'repAll'
        )
    );
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
        array(
            2.1,
            'Счетоводство',
            'Книги',
            'acc_Balances',
            'default',
            "acc, ceo"
        ),
        array(
            2.3,
            'Счетоводство',
            'Настройки',
            'acc_Periods',
            'default',
            "acc, ceo, admin"
        )
    );

    /**
     * Описание на системните действия
     */
    var $systemActions = array(
        array(
            'title' => 'Реконтиране',
            'url' => array(
                'acc_Journal',
                'reconto',
                'ret_url' => TRUE
            ),
            'params' => array(
                'title' => 'Реконтиране на документите',
                'ef_icon' => 'img/16/arrow_refresh.png'
            )
        )
    );

    /**
     * Настройки за Cron
     */
    var $cronSettings = array(
        array(
            'systemId' => "Delete Items",
            'description' => "Изтриване на неизползвани затворени пера",
            'controller' => "acc_Items",
            'action' => "DeleteUnusedItems",
            'period' => 1440,
            'offset' => 60,
            'timeLimit' => 100
        ),
        array(
            'systemId' => "Create Periods",
            'description' => "Създаване на нови счетоводни периоди",
            'controller' => "acc_Periods",
            'action' => "createFuturePeriods",
            'period' => 1440,
            'offset' => 60
        ),
        array(
            'systemId' => 'RecalcBalances',
            'description' => 'Преизчисляване на баланси',
            'controller' => 'acc_Balances',
            'action' => 'Recalc',
            'period' => 1,
            'timeLimit' => 55
        ),
        array(
            'systemId' => "SyncAccFeatures",
            'description' => "Синхронизиране на счетоводните свойства",
            'controller' => "acc_Features",
            'action' => "SyncFeatures",
            'period' => 1440,
            'offset' => 60,
            'timeLimit' => 600
        ),
        array(
            'systemId' => "CheckAccLimits",
            'description' => "Проверка на счетоводните лимити",
            'controller' => "acc_Limits",
            'action' => "CheckAccLimits",
            'period' => 480,
            'offset' => 1,
            'timeLimit' => 60
        )
    );

    /**
     * Дефинирани класове, които имат интерфейси
     */
    var $defClasses = "acc_ReportDetails, acc_reports_BalanceImpl, acc_BalanceHistory, acc_reports_HistoryImpl, acc_reports_PeriodHistoryImpl,
    					acc_reports_CorespondingImpl,acc_reports_SaleArticles,acc_reports_SaleContractors,acc_reports_OweProviders,
    					acc_reports_ProfitArticles,acc_reports_ProfitContractors,acc_reports_MovementContractors,acc_reports_TakingCustomers,
    					acc_reports_ManufacturedProducts,acc_reports_PurchasedProducts,acc_reports_BalancePeriodImpl, acc_reports_ProfitSales,
                        acc_reports_MovementsBetweenAccounts,acc_reports_MovementArtRep,acc_reports_TotalRep,acc_reports_UnpaidInvoices";

    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }

    /**
     * Зареждане на данни
     */
    function loadSetupData($itr = '')
    {
        $res = parent::loadSetupData($itr);
        $docs = core_Packs::getConfigValue('acc', 'ACC_COST_OBJECT_DOCUMENTS');
        
        // Ако потребителя не е избрал документи, които могат да са разходни пера
        if (strlen($docs) === 0) {
            $this->getCostObjectDocuments();
            $res .= "<li style='color:green'>Добавени са дефолт документи за разходни пера</li>";
        }
        
        $res .= $this->callMigrate('fixRepRoles', 'acc');
        
        return $res;
    }

    /**
     *
     * @param core_Type $type            
     * @param array $otherParams            
     *
     * @return array
     */
    public static function getAccessClassOptions($type, $otherParams)
    {
        return core_Classes::getOptionsByInterface('acc_TransactionSourceIntf', 'title');
    }

    /**
     * Кои документи по дефолт да са разходни обекти
     */
    function getCostObjectDocuments()
    {
        $docArr = array();
        foreach (array(
            'cal_Tasks',
            'sales_Sales',
            'purchase_Purchases',
            'accda_Da',
            'findeals_Deals',
            'findeals_AdvanceDeals',
            'planning_DirectProductionNote',
            'store_Transfers'
        ) as $doc) {
            if (core_Classes::add($doc)) {
                $id = $doc::getClassId();
                $docArr[$id] = $id;
            }
        }
        
        // Записват се ид-та на дефолт сметките за синхронизация
        core_Packs::setConfig('acc', array(
            'ACC_COST_OBJECT_DOCUMENTS' => keylist::fromArray($docArr)
        ));
    }

    /**
     * Помощна функция връщаща всички класове, които са документи
     */
    public static function getDocumentOptions()
    {
        $options = core_Classes::getOptionsByInterface('doc_DocumentIntf', 'title');
        
        return $options;
    }

    /**
     * Миграция за премахване на грешно изписана роля
     */
    public static function removeUnusedRole()
    {
        $rId = core_Roles::fetchByName('storeaAllGlobal');
        if ($rId) {
            core_Roles::removeRoles(array(
                $rId
            ));
        }
    }

    /**
     * Миграция за премахване на грешно изписана роля
     */
    public static function recalcAllGlobalRole()
    {
        $rId = core_Roles::fetchByName('allGlobal');
        if ($rId) {
            core_Roles::removeRoles(array(
                $rId
            ));
        }
        
        core_Roles::rebuildRoles();
        core_Users::rebuildRoles();
        
        core_Roles::addOnce('allGlobal');
    }
    
    
    /**
     * Миграция за заместване на старите роли "rep_" на потребителите
     */
    public static function fixRepRoles()
    {
        foreach (array('rep_cat' => 'repAllGlobal', 'rep_acc' => 'repAll') as $oRole => $nRole) {
            $rRec = core_Roles::fetch("#role = '{$oRole}'");
            $nRec = core_Roles::fetch("#role = '{$nRole}'");
            
            expect($nRec);
            
            if ($rRec) {
                $uQuery = core_Users::getQuery();
                $uQuery->likeKeylist('rolesInput', $rRec->id);
                $uQuery->likeKeylist('roles', $rRec->id);
                
                while ($uRec = $uQuery->fetch()) {
                    $uRec->roles = type_Keylist::removeKey($uRec->roles, $rRec->id);
                    $uRec->rolesInput = type_Keylist::removeKey($uRec->rolesInput, $rRec->id);
                    
                    $uRec->roles = type_Keylist::addKey($uRec->roles, $nRec->id);
                    $uRec->rolesInput = type_Keylist::addKey($uRec->rolesInput, $nRec->id);
                    
                    core_Users::save($uRec, 'roles, rolesInput');
                }
                
                // Затваряме ролите
                $rRec->state = 'closed';
                core_Roles::save($rRec, 'state');
            }
        }
    }
}
