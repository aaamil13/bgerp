<?php


/**
 * Клас 'store_ConsignmentProtocols'
 *
 * Мениджър на протоколи за отговорно пазене
 *
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov<ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class store_ConsignmentProtocols extends core_Master
{
    /**
     * Заглавие
     *
     * @var string
     */
    public $title = 'Протоколи за отговорно пазене';
    
    
    /**
     * Флаг, който указва, че документа е партньорски
     */
    public $visibleForPartners = true;
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Cpt';
    
    
    /**
     * Кои външни(external) роли могат да създават/редактират документа в споделена папка
     */
    public $canWriteExternal = 'distributor';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, store_iface_DocumentIntf, acc_TransactionSourceIntf=store_transaction_ConsignmentProtocol,colab_CreateDocumentIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, store_plg_StoreFilter, deals_plg_SaveValiorOnActivation, store_Wrapper, doc_plg_BusinessDoc,plg_Sorting, acc_plg_Contable, cond_plg_DefaultValues,
                        plg_Clone, doc_DocumentPlg, plg_Printing, acc_plg_DocumentSummary, trans_plg_LinesPlugin, doc_plg_TplManager, plg_Search, bgerp_plg_Blank, doc_plg_HidePrices, doc_EmailCreatePlg, store_plg_StockPlanning';
    
    
    /**
     * Кой може да го прави документа чакащ/чернова?
     */
    public $canPending = 'ceo,store,distributor';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,store';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,store';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,store';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,store';
    
    
    /**
     * Кой има право да променя?
     */
    public $canChangeline = 'ceo,store,trans';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canConto = 'ceo,store';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'valior, title=Документ, contragentId=Контрагент, lineId, folderId, createdOn, createdBy';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/consignment.png';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'store_ConsignmentProtocolDetailsSend,store_ConsignmentProtocolDetailsReceived';
    
    
    /**
     * Кой детайл да се наглася в зоните
     */
    public $detailToPlaceInZones = 'store_ConsignmentProtocolDetailsSend';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Протокол за отговорно пазене';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '4.7|Логистика';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'valior,folderId,note';


    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn, modifiedOn, valior, readyOn, deliveryTime, shipmentOn, deliveryOn';


    /**
     * На кой ред в тулбара да се показва бутона за принтиране
     */
    public $printBtnToolbarRow = 1;
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;
    
    
    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clone
     */
    public $cloneDetails = 'store_ConsignmentProtocolDetailsSend, store_ConsignmentProtocolDetailsReceived';
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'valior,snapshot,lineId';


    /**
     * Кои полета ще се проверяват при вземане на контрагент данните в имейла
     */
    public $getContragentDataCheckFields = 'locationId';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('valior', 'date', 'caption=Вальор');
        $this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Клиент');
        $this->FLD('contragentId', 'int', 'input=hidden,tdClass=leftCol');
        $this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code,allowEmpty)', 'mandatory,caption=Валута');
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,mandatory');
        $this->FLD('deliveryTime', 'datetime(requireTime)','caption=Товарене');
        $this->FLD('deliveryOn', 'datetime(requireTime)','caption=Доставка');
        $this->FLD('locationId', 'key(mvc=crm_Locations, select=title,allowEmpty)', 'caption=Локация на Контрагента->Обект,silent');
        $this->FLD('productType', 'enum(ours=Наши артикули,other=Чужди артикули)', 'caption=Артикули за предаване/получаване->Избор,mandatory,notNull,default=ours');

        $this->FLD('lineId', 'key(mvc=trans_Lines,select=title, allowEmpty)', 'caption=Транспорт');
        $this->FLD('note', 'richtext(bucket=Notes,rows=3)', 'caption=Допълнително->Бележки');
        $this->FLD('state', 'enum(draft=Чернова, active=Контиран, rejected=Оттеглен,stopped=Спряно,pending=Заявка)', 'caption=Статус, input=none');
        $this->FLD('snapshot', 'blob(serialize, compress)', 'caption=Данни,input=none');
        $this->FLD('responsible', 'varchar', 'caption=Допълнително->Получил');
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (!deals_Helper::canSelectObjectInDocument($action, $rec, 'store_Stores', 'storeId')) {
            $requiredRoles = 'no_one';
        }
        
        // Ако партньор създава, но няма дефолтен скалд в папката да не му се появява бутона
        if($action == 'add' && isset($rec) && (core_Packs::isInstalled('colab') && haveRole('partner', $userId))){
            if(isset($rec->folderId)){
                $cId = doc_Folders::fetchCoverId($rec->folderId);
                $Class = doc_Folders::fetchCoverClassId($rec->folderId);
                
                $defaultColabStore = cond_Parameters::getParameter($Class, $cId, 'defaultStoreSale');
                if(empty($defaultColabStore)){
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (isset($fields['-list'])) {
            $row->contragentId = cls::get($rec->contragentClassId)->getHyperlink($rec->contragentId, true);
            $row->title = $mvc->getLink($rec->id, 0);
        }

        $headerInfo = deals_Helper::getDocumentHeaderInfo($rec->contragentClassId, $rec->contragentId);
        $row = (object) ((array) $row + (array) $headerInfo);
        
        if (isset($fields['-single'])) {
            $row->storeId = store_Stores::getHyperlink($rec->storeId);
            $row->username = core_Users::getVerbal($rec->createdBy, 'names');

            $mvc->pushTemplateLg($rec->template);
            $row->contragentCaption = ($rec->productType == 'ours') ? tr('Довереник') : tr('Доверител');
            $row->ourCompanyCaption = ($rec->productType == 'ours') ? tr('Доверител') : tr('Довереник');
            if(isset($rec->locationId)){
                $row->locationId = crm_Locations::getHyperlink($rec->locationId);
                $row->deliveryAddress = crm_Locations::getAddress($rec->locationId, true);
            }
            core_Lg::pop();
        }
    }
    
    
    /**
     * Функция, която се извиква след активирането на документа
     */
    protected static function on_AfterActivation($mvc, &$rec)
    {
        $rec = $mvc->fetchRec($rec);
        
        if (empty($rec->snapshot)) {
            $rec->snapshot = $mvc->prepareSnapshot($rec, dt::now());
            $mvc->save($rec, 'snapshot');
        }
    }
    
    
    /**
     * След подготовка на сингъла
     */
    protected static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
        // Ако няма 'снимка' на моментното състояние, генерираме го в момента
        if (empty($data->rec->snapshot)) {
            $data->rec->snapshot = $mvc->prepareSnapshot($data->rec, dt::now());
        }
    }
    
    
    /**
     * След рендиране на единичния изглед
     */
    protected static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        // Ако потребителя няма достъп към визитката на лицето, или не може да види сч. справки то визитката, той не може да види справката
        $Contragent = cls::get($data->rec->contragentClassId);
        if (!$Contragent->haveRightFor('single', $data->rec->contragentId)) return;

        if (!haveRole($Contragent->canReports)) return;
        
        $snapshot = $data->rec->snapshot;
        $mvcTable = new core_Mvc;
        $mvcTable->FLD('blQuantity', 'int', 'tdClass=accCell');

        $productCaption = ($data->rec->productType == 'ours') ? 'Наш артикул' : 'Чужд артикул';
        $table = cls::get('core_TableView', array('mvc' => $mvcTable));
        $details = $table->get($snapshot->rows, "count=№,productId={$productCaption},blQuantity=Количество");

        $tpl->replace($details, 'SNAPSHOT');
        $tpl->replace($snapshot->date, 'SNAPSHOT_DATE');
    }
    
    
    /**
     * Подготвя снапшот на моментното представяне на базата
     */
    private function prepareSnapshot($rec, $date)
    {
        $rows = array();
        $accId = ($rec->productType == 'ours') ? '3231' : '3232';

        // Кое е перото на контрагента ?
        $contragentItem = acc_Items::fetchItem($rec->contragentClassId, $rec->contragentId);
        
        // Ако контрагента не е перо, не показваме нищо
        if ($contragentItem) {
            
            // За да покажем моментното състояние на сметката на контрагента, взимаме баланса до края на текущия ден
            $to = dt::addDays(1, $date);
            $Balance = new acc_ActiveShortBalance(array('from' => $to,
                'to' => $to,
                'accs' => $accId,
                'item1' => $contragentItem->id,
                'strict' => true,
                'keepUnique' => true,
                'cacheBalance' => false));

            // Изчлисляваме в момента, какъв би бил крания баланс по сметката в края на деня
            $Balance = $Balance->getBalanceBefore($accId);
            
            $Double = cls::get('type_Double');
            $Double->params['smartRound'] = true;
            $Int = cls::get('type_Int');
            
            $accId = acc_Accounts::getRecBySystemId($accId)->id;
            $count = 1;

            // Подготвяме записите за показване
            foreach ($Balance as $b) {
                if ($b['accountId'] != $accId) {
                    continue;
                }
                if ($b['blQuantity'] == 0) {
                    continue;
                }
                
                $row = new stdClass;
                $row->count = $Int->toVerbal($count);
                $row->productId = acc_Items::getVerbal($b['ent2Id'], 'titleLink');
                $row->blQuantity = $Double->toVerbal($b['blQuantity']);
                $row->blQuantity = ht::styleIfNegative($row->blQuantity, $b['baseQuantity']);
                
                $count++;
                $rows[] = $row;
            }
        }
        
        // Връщаме подготвените записи, и датата към която са подготвени
        return (object) array('rows' => $rows, 'date' => cls::get('type_DateTime')->toVerbal($date));
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;

        // При нов протокол, потребителя ще бъде принуден да избере типа на предаваните/получаваните артикули
        if(empty($rec->id)){
            $form->setOptions('productType', array('' => '', 'ours' => 'Наши артикули', 'other' => 'Чужди артикули'));
            $form->setDefault('productType', '');
        }

        $form->setDefault('storeId', store_Stores::getCurrent('id', false));
        $rec->contragentClassId = doc_Folders::fetchCoverClassId($rec->folderId);
        $rec->contragentId = doc_Folders::fetchCoverId($rec->folderId);
        $form->setDefault('currencyId', acc_Periods::getBaseCurrencyCode());
        $form->setOptions('locationId', array('' => '') + crm_Locations::getContragentOptions($rec->contragentClassId, $rec->contragentId));

        if (isset($rec->id)) {
            if (store_ConsignmentProtocolDetailsSend::fetchField("#protocolId = {$rec->id}")) {
                $form->setReadOnly('currencyId');
            }
        }
        
        // Скриване на определени полета, ако потребителя е партньор
        if(core_Packs::isInstalled('colab') && haveRole('partner')){
            $form->setField('currencyId', 'input=hidden');
            $form->setField('storeId', 'input=none');
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if($form->isSubmitted()){

            // Задаване на дефолтния склад, ако потребителя е партньор
            if(core_Packs::isInstalled('colab') && haveRole('partner')){
                $form->rec->storeId = cond_Parameters::getParameter($form->rec->contragentClassId, $form->rec->contragentId, 'defaultStoreSale');
            }
        }
    }
    
    
    /**
     * @see doc_DocumentIntf::getDocumentRow()
     */
    public function getDocumentRow_($id)
    {
        expect($rec = $this->fetch($id));
        $title = $this->getRecTitle($rec);
        
        $row = (object) array(
            'title' => $title,
            'authorId' => $rec->createdBy,
            'author' => $this->getVerbal($rec, 'createdBy'),
            'state' => $rec->state,
            'recTitle' => $title
        );
        
        return $row;
    }
    
    
    /**
     * В кои корици може да се вкарва документа
     *
     * @return array - интерфейси, които трябва да имат кориците
     */
    public static function getCoversAndInterfacesForNewDoc()
    {
        return array('crm_ContragentAccRegIntf');
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
        $Cover = doc_Folders::getCover($folderId);
        
        return $Cover->haveInterface('crm_ContragentAccRegIntf');
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената нишка
     *
     * @param int $threadId key(mvc=doc_Threads)
     *
     * @return bool
     */
    public static function canAddToThread($threadId)
    {
        $threadRec = doc_Threads::fetch($threadId);
        $coverClass = doc_Folders::fetchCoverClassName($threadRec->folderId);
        
        return cls::haveInterface('crm_ContragentAccRegIntf', $coverClass);
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
        $tplArr = array();
        $tplArr[] = array('name' => 'Протокол за отговорно пазене', 'content' => 'store/tpl/SingleLayoutConsignmentProtocol.shtml',
            'narrowContent' => 'store/tpl/SingleLayoutConsignmentProtocolNarrow.shtml', 'lang' => 'bg');
        

        $res = doc_TplManager::addOnce($this, $tplArr);
        
        return $res;
    }
    
    
    /**
     * Изчисляване на общото тегло и обем на документа
     *
     * @param stdClass $res
     *                        - weight - теглото на реда
     *                        - volume - теглото на реда
     * @param int      $id
     * @param bool     $force
     */
    public function getTotalTransportInfo($id, $force = false)
    {
        $rec = $this->fetchRec($id);
        $res1 = cls::get('store_ConsignmentProtocolDetailsReceived')->getTransportInfo($rec->id, $force);
        $res2 = cls::get('store_ConsignmentProtocolDetailsSend')->getTransportInfo($rec->id, $force);
        
        $count1 = store_ConsignmentProtocolDetailsReceived::count("#protocolId = {$rec->id}");
        $count2 = store_ConsignmentProtocolDetailsSend::count("#protocolId = {$rec->id}");
        
        $nullWeight = ($count1 && is_null($res1->weight)) || ($count2 && is_null($res2->weight)) || (!$count1 && !$count2);
        $weight = ($nullWeight) ? null : $res1->weight + $res2->weight;
        
        $nullVolume = ($count1 && is_null($res1->volume)) || ($count2 && is_null($res2->volume)) || (!$count1 && !$count2);
        $volume = ($nullVolume) ? null : $res1->volume + $res2->volume;
        
        $units = trans_Helper::getCombinedTransUnits($res1->transUnits, $res2->transUnits);
        
        return (object) array('weight' => $weight, 'volume' => $volume, 'transUnits' => $units);
    }
    
    
    /**
     * Обновява данни в мастъра
     *
     * @param int $id първичен ключ на статия
     *
     * @return int $id ид-то на обновения запис
     */
    public function updateMaster_($id)
    {
        $rec = $this->fetchRec($id);
        
        return $this->save($rec);
    }


    /**
     * Информацията на документа, за показване в транспортната линия
     *
     * @param mixed $id
     * @param int $lineId
     *
     * @return array
     *               ['baseAmount']     double|NULL - сумата за инкасиране във базова валута
     *               ['amount']         double|NULL - сумата за инкасиране във валутата на документа
     *               ['amountVerbal']   double|NULL - сумата за инкасиране във валутата на документа
     *               ['currencyId']     string|NULL - валутата на документа
     *               ['notes']          string|NULL - забележки за транспортната линия
     *               ['stores']         array       - склад(ове) в документа
     *               ['cases']          array       - каси в документа
     *               ['zoneId']         array       - ид на зона, в която е нагласен документа
     *               ['zoneReadiness']  int         - готовност в зоната в която е нагласен документа
     *               ['weight']         double|NULL - общо тегло на стоките в документа
     *               ['volume']         double|NULL - общ обем на стоките в документа
     *               ['transportUnits'] array       - използваните ЛЕ в документа, в формата ле -> к-во
     *               ['contragentName'] double|NULL - име на контрагента
     *               ['address']        double|NULL - адрес ба диставка
     *               ['storeMovement']  string|NULL - посока на движението на склада
     *               ['locationId']     string|NULL - ид на локация на доставка (ако има)
     *               ['addressInfo']    string|NULL - информация за адреса
     *               ['countryId']      string|NULL - ид на държава
     *               ['place']          string|NULL - населено място
     *               ['features']       array       - свойства на адреса
     */
    public function getTransportLineInfo_($rec, $lineId)
    {
        $rec = static::fetchRec($rec);
        $row = $this->recToVerbal($rec);
        $res = array('baseAmount' => null, 'amount' => null, 'amountVerbal' => null, 'currencyId' => null, 'notes' => $rec->lineNotes);
        $res['contragentName'] = cls::get($rec->contragentClassId)->getTitleById($rec->contragentId);
        $res['stores'] = array($rec->storeId);

        if(isset($rec->locationId)){
            $locationRec = crm_Locations::fetch($rec->locationId);
            $res['locationId'] = $locationRec->id;
            $res['address'] = crm_Locations::getAddress($locationRec, false, false);
            if(!empty($locationRec->features)){
                $res['features'] = keylist::toArray($locationRec->features);
            }
        } else {
            $res['address'] = str_replace('<br>', '', $row->contragentAddress);
        }

        $res['cases'] = array();

        return $res;
    }
    
    
    /**
     * Какво да е предупреждението на бутона за контиране
     *
     * @param int    $id         - ид
     * @param string $isContable - какво е действието
     *
     * @return NULL|string - текста на предупреждението или NULL ако няма
     */
    public function getContoWarning_($id, $isContable)
    {
        $rec = $this->fetchRec($id);
        $dQuery = store_ConsignmentProtocolDetailsSend::getQuery();
        $dQuery->where("#protocolId = {$id}");
        $dQuery->show('productId, quantity');
        
        $warning = deals_Helper::getWarningForNegativeQuantitiesInStore($dQuery->fetchAll(), $rec->storeId, $rec->state);
        
        return $warning;
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = true)
    {
        $mvc = cls::get(get_called_class());
        
        $rec = static::fetchRec($rec);
        $abbr = $mvc->abbr;
        $abbr[0] = strtoupper($abbr[0]);
        
        if (isset($rec->contragentClassId, $rec->contragentId)) {
            $Crm = cls::get($rec->contragentClassId);
            $cRec = $Crm->getContragentData($rec->contragentId);
            $contragent = str::limitLen($cRec->person ? $cRec->person : $cRec->company, 16);
        } else {
            $contragent = tr('Проблем при показването');
        }
        
        if ($escaped) {
            $contragent = type_Varchar::escape($contragent);
        }
        
        $title = "{$abbr}{$rec->id}";
        if(!empty($rec->valior)){
            $title .= "/" . dt::mysql2verbal($rec->valior, 'd.m.Y');
        }
        $title .= "/{$contragent}";
        
        return $title;
    }


    /**
     * Връща планираните наличности
     *
     * @param stdClass $rec
     * @return array
     *       ['productId']        - ид на артикул
     *       ['storeId']          - ид на склад, или null, ако няма
     *       ['date']             - на коя дата
     *       ['quantityIn']       - к-во очаквано
     *       ['quantityOut']      - к-во за експедиране
     *       ['genericProductId'] - ид на генеричния артикул, ако има
     *       ['reffClassId']      - клас на обект (различен от този на източника)
     *       ['reffId']           - ид на обект (различен от този на източника)
     */
    public function getPlannedStocks($rec)
    {
        $res = array();
        $id = is_object($rec) ? $rec->id : $rec;
        $rec = $this->fetch($id, '*', false);
        $date = $this->getPlannedQuantityDate($rec);

        $dQuery = store_ConsignmentProtocolDetailsSend::getQuery();
        $dQuery->EXT('generic', 'cat_Products', "externalName=generic,externalKey=productId");
        $dQuery->EXT('canConvert', 'cat_Products', "externalName=canConvert,externalKey=productId");
        $dQuery->XPR('totalQuantity', 'double', "SUM(#packQuantity * #quantityInPack)");
        $dQuery->where("#protocolId = {$rec->id}");
        $dQuery->groupBy('productId');

        while ($dRec = $dQuery->fetch()) {
            $genericProductId = null;
            if($dRec->generic == 'yes'){
                $genericProductId = $dRec->productId;
            } elseif($dRec->canConvert == 'yes'){
                $genericProductId = planning_GenericMapper::fetchField("#productId = {$dRec->productId}", 'genericProductId');
            }

            $res[] = (object)array('storeId'          => $rec->storeId,
                                   'productId'        => $dRec->productId,
                                   'date'             => $date,
                                   'quantityIn'       => null,
                                   'quantityOut'      => $dRec->totalQuantity,
                                   'genericProductId' => $genericProductId);
        }

        return $res;
    }


    /**
     * Kои са полетата за датите за експедирането
     *
     * @param mixed $rec     - ид или запис
     * @param boolean $cache - дали да се използват кеширани данни
     * @return array $res    - масив с резултат
     */
    public function getShipmentDateFields($rec = null, $cache = false)
    {
        $res = array('readyOn'      => array('caption' => 'Готовност', 'type' => 'date', 'readOnlyIfActive' => true, "input" => "input=hidden", 'autoCalcFieldName' => 'readyOnCalc', 'displayExternal' => false),
                     'deliveryTime' => array('caption' => 'Товарене', 'type' => 'datetime(requireTime)', 'readOnlyIfActive' => true, "input" => "input", 'autoCalcFieldName' => 'deliveryTimeCalc', 'displayExternal' => false),
                     'shipmentOn'   => array('caption' => 'Експедиране на', 'type' => 'datetime(requireTime)', 'readOnlyIfActive' => false, "input" => "input=hidden", 'autoCalcFieldName' => 'shipmentOnCalc', 'displayExternal' => false),
                     'deliveryOn'   => array('caption' => 'Доставка', 'type' => 'datetime(requireTime)', 'readOnlyIfActive' => false, "input" => "input", 'autoCalcFieldName' => 'deliveryOnCalc', 'displayExternal' => true));

        if(isset($rec)){
            $res['deliveryTime']['placeholder'] = store_Stores::calcLoadingDate($rec->storeId, $rec->deliveryOn);
            $res['readyOn']['placeholder'] = ($cache) ? $rec->readyOnCalc : $this->getEarliestDateAllProductsAreAvailableInStore($rec);
            $res['shipmentOn']['placeholder'] = ($cache) ? $rec->shipmentOnCalc : trans_Helper::calcShippedOnDate($rec->valior, $rec->lineId, $rec->activatedOn);
        }

        return $res;
    }


    /**
     * Коя е най-ранната дата на която са налични всички документи
     *
     * @param $rec
     * @return date|null
     */
    public function getEarliestDateAllProductsAreAvailableInStore($rec)
    {
        $rec = $this->fetchRec($rec);
        $detail = ($rec->productType == 'ours') ? 'store_ConsignmentProtocolDetailsSend' : 'store_ConsignmentProtocolDetailsReceived';
        $products = deals_Helper::sumProductsByQuantity($detail, $rec->id, true);

        return store_StockPlanning::getEarliestDateAllAreAvailable($rec->storeId, $products);
    }


    /**
     * За коя дата се заплануват наличностите
     *
     * @param stdClass $rec - запис
     * @return datetime     - дата, за която се заплануват наличностите
     */
    public function getPlannedQuantityDate_($rec)
    {
        // Ако има ръчно въведена дата на натоварване, връща се тя
        if (!empty($rec->deliveryTime)) return $rec->deliveryTime;

        $preparationTime = store_Stores::getShipmentPreparationTime($rec->storeId);

        return dt::addSecs(-1 * $preparationTime, $rec->deliveryOn);
    }


    /**
     * Връща тялото на имейла генериран от документа
     *
     * @see email_DocumentIntf
     * @param int  $id      - ид на документа
     * @param bool $forward
     * @return string - тялото на имейла
     */
    public function getDefaultEmailBody($id, $forward = false)
    {
        $handle = $this->getHandle($id);
        $tpl = new ET(tr('Моля, запознайте се с нашия протокол за отговорно пазене') . ': #[#handle#]');
        $tpl->append($handle, 'handle');

        return $tpl->getContent();
    }
}
