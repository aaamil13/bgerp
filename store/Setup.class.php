<?php


/**
 * Кои сч. сметки ще се използват за синхронизиране със склада
 */
defIfNot('STORE_ACC_ACCOUNTS', '');


/**
 * Групиране на тарифните номера по част от него
 */
defIfNot('STORE_TARIFF_NUMBER_LENGTH', '8');


/**
 * class store_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани със складовете
 *
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ts. Mihaylov <tsvetanm@ep-bags.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class store_Setup extends core_ProtoSetup
{
    /**
     * Систем ид-та на счетоводните сметки за синхронизация
     */
    protected static $accAccount = array('321', '302');
    
    
    /**
     * Версия на компонента
     */
    public $version = '0.1';
    
    
    /**
     * Необходими пакети
     */
    public $depends = 'acc=0.1';
    
    
    /**
     * Стартов контролер за връзката в системното меню
     */
    public $startCtr = 'store_Products';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Управление на складове и складови документи';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'store_Stores',
        'store_Products',
        'store_DocumentPackagingDetail',
        'store_ShipmentOrders',
        'store_ShipmentOrderDetails',
        'store_Receipts',
        'store_ReceiptDetails',
        'store_Transfers',
        'store_TransfersDetails',
        'store_ConsignmentProtocols',
        'store_ConsignmentProtocolDetailsSend',
        'store_ConsignmentProtocolDetailsReceived',
        'store_InventoryNotes',
        'store_InventoryNoteSummary',
        'store_InventoryNoteDetails',
        'store_StockPlanning',
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = array(
        array('storeWorker'),
        array('inventory'),
        array('store', 'storeWorker'),
        array('storeMaster', 'store'),
    );
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(3.2, 'Логистика', 'Склад', 'store_Products', 'default', 'storeWorker,ceo'),
    );
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'STORE_ACC_ACCOUNTS' => array('acc_type_Accounts(regInterfaces=store_AccRegIntf|cat_ProductAccRegIntf)', 'caption=Складова синхронизация със счетоводството->Сметки'),
        'STORE_TARIFF_NUMBER_LENGTH' => array('int', 'caption=Групиране на тарифните номера по част от него->Първите,unit=цифри'),
    );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'store_reports_Documents,store_reports_ChangeQuantity,store_reports_ProductAvailableQuantity,
                          store_iface_ImportShippedProducts,store_reports_DeficitInStores,store_reports_UnfulfilledQuantities,
                          store_reports_ArticlesDepended,store_reports_ProductsInStock';
    
    
    /**
     * Настройки за Cron
     */
    public $cronSettings = array(
        array(
            'systemId' => 'Update Reserved Stocks',
            'description' => 'Обновяване на резервираните наличности',
            'controller' => 'store_Products',
            'action' => 'CalcReservedQuantity',
            'period' => 1,
            'offset' => 0,
            'timeLimit' => 100
        ),
        array(
            'systemId' => 'Update Shipment Document Readiness',
            'description' => 'Обновяване на готовността на складовите документи на заявка',
            'controller' => 'store_Products',
            'action' => 'UpdateShipmentDocumentReadiness',
            'period' => 3,
            'offset' => 0,
            'timeLimit' => 100
        ),
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Закачане на плъгина за прехвърляне на собственотст на системни папки към core_Users
        $Plugins = cls::get('core_Plugins');
        $html .= $Plugins->installPlugin('Синхронизиране на складовите наличности', 'store_plg_BalanceSync', 'acc_Balances', 'private');
        
        return $html;
    }
    
    
    /**
     * Зареждане на данните
     */
    public function loadSetupData($itr = '')
    {
        $res = parent::loadSetupData($itr);
        
        // Ако няма посочени от потребителя сметки за синхронизация
        $config = core_Packs::getConfig('store');
        if (strlen($config->STORE_ACC_ACCOUNTS) === 0) {
            $accArray = array();
            foreach (static::$accAccount as $accSysId) {
                $accId = acc_Accounts::getRecBySystemId($accSysId)->id;
                $accArray[$accId] = $accSysId;
            }
            
            // Записват се ид-та на дефолт сметките за синхронизация
            core_Packs::setConfig('store', array('STORE_ACC_ACCOUNTS' => keylist::fromArray($accArray)));
            $res .= "<li style='color:green'>Дефолт счетодовни сметки за синхронизация на продуктите<b>" . implode(',', $accArray) . '</b></li>';
        }
        
        return $res;
    }
    
    
    /**
     * Изтриване на кеш
     */
    public function truncateCacheProducts1()
    {
        try {
            if (cls::load('store_Products', true)) {
                $Products = cls::get('store_Products');
                
                if ($Products->db->tableExists($Products->dbTableName)) {
                    store_Products::truncate();
                }
            }
        } catch (core_exception_Expect $e) {
            reportException($e);
        }
    }


    public function migratePendings()
    {
        if(!store_Products::count()) return;
        $Stocks = cls::get('store_StockPlanning');
        $Stocks->truncate();

        $stockableClasses = array('store_ShipmentOrders',
                                  'store_Receipts',
                                  'store_Transfers',
                                  'store_ConsignmentProtocols',
                                  'planning_ConsumptionNotes',
                                  'planning_DirectProductionNote',
                                  'pos_Receipts');

        $stocksArr = array();
        foreach ($stockableClasses as $cls){
            $Source = cls::get($cls);
            $Source->setupMvc();

            $query = $Source->getQuery();
            $query->where("#state = 'pending'");
            while($rec = $query->fetch()){
                $arr = $Source->getPlannedStocks($rec);
                $stocksArr = array_merge($stocksArr, $arr);
            }
        }

        $Stocks->saveArray($stocksArr);

        $dealsArr = array();
        $stockableOriginClasses = array('sales_Sales', 'purchase_Purchases', 'planning_Jobs');
        foreach ($stockableOriginClasses as $cls) {
            $Source = cls::get($cls);
            $Source->setupMvc();

            $query = $Source->getQuery();
            $query->in("state", array('pending', 'active', 'stopped', 'wakeup'));

            while ($rec = $query->fetch()) {
                $arr = $Source->getPlannedStocks($rec);
                $dealsArr = array_merge($dealsArr, $arr);
            }
        }

        $Stocks->saveArray($dealsArr);



    }
}
